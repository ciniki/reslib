<?php
//
// Description
// -----------
// This function will process the request for a section of the resource library.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_reslib_wng_categoryProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.reslib']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.reslib.95', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.96', 'msg'=>"No section specified"));
    }
    $s = $section['settings'];
    $blocks = [];
    $base_url = $s['base_url'];

    //
    // Load the sections the web visitor has access to
    //
    $section_perms_sql = '';
    $category_perms_sql = '';
    $subcat_perms_sql = '';
    $item_perms_sql = '';

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'perms');
    $rc = ciniki_customers_wng_perms($ciniki, $tnid, $request, []);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['perms_sql']) && $rc['perms_sql'] != '' ) {
        $section_perms_sql = 'AND ' . str_replace('customer_perms ', 'sections.customer_perms ', $rc['perms_sql']);
        $category_perms_sql = 'AND ' . str_replace('customer_perms ', 'categories.customer_perms ', $rc['perms_sql']);
        $subcat_perms_sql = 'AND ' . str_replace('customer_perms ', 'subcats.customer_perms ', $rc['perms_sql']);
        $item_perms_sql = 'AND ' . str_replace('customer_perms ', 'items.customer_perms ', $rc['perms_sql']);
    }

    //
    // Check permissions for section
    //
    $strsql = "SELECT categories.id, "
        . "categories.name, "
        . "categories.permalink, "
        . "categories.flags, "
        . "categories.description, "
        . "sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "sections.permalink AS section_permalink "
        . "FROM ciniki_reslib_categories AS categories "
        . "INNER JOIN ciniki_reslib_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . $section_perms_sql
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . " )";
    //
    // When permalink specified, the higher level must then also be specified by id or permalink.
    //
    if( isset($s['category-id']) ) {
        $strsql .= "WHERE categories.id = '" . ciniki_core_dbQuote($ciniki, $s['category-id']) . "' ";
    } elseif( isset($s['category-permalink']) ) {
        $strsql .= "WHERE categories.permalink = '" . ciniki_core_dbQuote($ciniki, $s['category-permalink']) . "' ";
        if( isset($s['section-id']) ) {
            $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $s['section-id']) . "' ";
        } elseif( isset($s['section-permalink']) ) {
            $strsql .= "AND sections.permalink = '" . ciniki_core_dbQuote($ciniki, $s['section-permalink']) . "' ";
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.97', 'msg'=>'No section specified'));
        }
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.98', 'msg'=>'No category specified'));
    }
    $strsql .= $category_perms_sql        // Make sure customer has permission to this category
        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'category');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.99', 'msg'=>'Unable to load category', 'err'=>$rc['err']));
    }
    if( !isset($rc['category']) ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'error', 
            'content' => 'Not found',
            ];
        return array('stat'=>'404', 'blocks'=>$blocks);
    }
    $category_id = $rc['category']['id'];
    $reslib_category = $rc['category'];

   
    if( isset($s['section-permalink']) ) {
        $s['title'] .= ' - ' . $reslib_category['section_name'];
    }
    if( isset($s['category-permalink']) ) {
        $s['title'] .= ' - ' . $reslib_category['category_name'];
    }

    //
    // Get the list of items in the subcategories
    //
    $strsql = "SELECT subcats.id, "
        . "subcats.name, "
        . "subcats.permalink, "
        . "subcats.image_id, "
        . "subcats.synopsis "
        . "FROM ciniki_reslib_subcategories AS subcats "
        . "WHERE subcats.category_id = '" . ciniki_core_dbQuote($ciniki, $category_id) . "' "
        . $subcat_perms_sql
        . "AND subcats.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY subcats.sequence, subcats.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'subcats', 'fname'=>'id', 
            'fields'=>array('id', 'title'=>'name', 'permalink', 'flags', 'image-id'=>'image_id', 'synopsis'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.100', 'msg'=>'Unable to load subcategories', 'err'=>$rc['err']));
    }
    $subcats = isset($rc['subcats']) ? $rc['subcats'] : array();
    foreach($subcats as $sid => $subcat) {
        $subcats[$sid]['url'] = "{$base_url}/{$subcat['permalink']}";
    }

    if( count($subcats) < 1 ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'error', 
            'content' => 'Nothing found',
            ];
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    if( isset($s['title']) && $s['title'] != '' 
        && isset($s['category-permalink']) 
        && isset($reslib_category['description']) && $reslib_category['description'] != '' 
        ) {
        $blocks[] = [
            'type' => 'text',
            'title' => $s['title'],
            'content' => $reslib_category['description'],
            ];
    } 
    elseif( isset($s['title']) && $s['title'] != '' && isset($s['category-id']) && isset($s['content']) && $s['content'] != '' ) {
        $blocks[] = [
            'type' => 'text',
            'title' => $s['title'],
            'content' => $s['content'],
            ];
    } 
    elseif( isset($s['title']) && $s['title'] != '' ) {
        $blocks[] = [
            'type' => 'title',
            'title' => $s['title'],
            ];
    }

    //
    // Check if search enabled
    //
    if( isset($s['category-search']) && $s['category-search'] == 'yes' ) {
        $api_args = [
            'category_id' => $category_id,
            'image_ratio' => isset($s['category-image-ratio']) ? $s['category-image-ratio'] : '1-1',
            'format' => isset($s['category-search-layout']) ? $s['category-search-layout'] : 'table',
            'base_url' => $base_url,
            ];
        $blocks[] = [
            'type' => 'livesearch',
            'label' => 'Search',
            'id' => $section['sequence'],
            'api-search-url' => $request['api_url'] . '/ciniki/reslib/search',
            'api-args' => $api_args,
            ];
    }

    //
    // Display the list of subcategories
    //
    $blocks[] = [
        'type' => 'flexcards',
        'class' => 'reslib-category',
        'image-ratio' => isset($s['category-image-ratio']) && $s['category-image-ratio'] != '' ? $s['category-image-ratio'] : '1-1',
        'items' => $subcats,
        ];

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
