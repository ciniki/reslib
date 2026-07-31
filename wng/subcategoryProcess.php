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
function ciniki_reslib_wng_subcategoryProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.reslib']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.reslib.76', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.77', 'msg'=>"No section specified"));
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
    $strsql = "SELECT subcats.id, "
        . "subcats.name, "
        . "subcats.permalink, "
        . "subcats.flags, "
        . "subcats.description, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.permalink AS category_permalink, "
        . "sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "sections.permalink AS section_permalink "
        . "FROM ciniki_reslib_subcategories AS subcats "
        . "INNER JOIN ciniki_reslib_categories AS categories ON ("
            . "subcats.category_id = categories.id "
            . $category_perms_sql
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . " )"
        . "INNER JOIN ciniki_reslib_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . $section_perms_sql
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . " )";
    //
    // When permalink specified, the higher level must then also be specified by id or permalink.
    //
    if( isset($s['subcategory-id']) ) {
        $strsql .= "WHERE subcats.id = '" . ciniki_core_dbQuote($ciniki, $s['subcategory-id']) . "' ";
    } elseif( isset($s['subcategory-permalink']) ) {
        $strsql .= "WHERE subcats.permalink = '" . ciniki_core_dbQuote($ciniki, $s['subcategory-permalink']) . "' ";
        if( isset($s['category-id']) ) {
            $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $s['category-id']) . "' ";
        } elseif( isset($s['category-permalink']) ) {
            $strsql .= "AND categories.permalink = '" . ciniki_core_dbQuote($ciniki, $s['category-permalink']) . "' ";
            if( isset($s['section-id']) ) {
                $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $s['section-id']) . "' ";
            } elseif( isset($s['section-permalink']) ) {
                $strsql .= "AND sections.permalink = '" . ciniki_core_dbQuote($ciniki, $s['section-permalink']) . "' ";
            } else {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.82', 'msg'=>'No section specified'));
            }
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.81', 'msg'=>'No category specified'));
        }
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.78', 'msg'=>'No subcategory specified'));
    }
    $strsql .= $subcat_perms_sql        // Make sure customer has permission to this subcategory
        . "AND subcats.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'subcategory');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.79', 'msg'=>'Unable to load subcategory', 'err'=>$rc['err']));
    }
    if( !isset($rc['subcategory']) ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'error', 
            'content' => 'Not found',
            ];
        return array('stat'=>'404', 'blocks'=>$blocks);
    }
    $subcategory_id = $rc['subcategory']['id'];
    $reslib_subcategory = $rc['subcategory'];

   
    if( isset($s['section-permalink']) ) {
        $s['title'] .= ' - ' . $reslib_subcategory['section_name'];
    }
    if( isset($s['category-permalink']) ) {
        $s['title'] .= ' - ' . $reslib_subcategory['category_name'];
    }
    if( isset($s['subcategory-permalink']) ) {
        $s['title'] .= ' - ' . $reslib_subcategory['name'];
    }

    //
    // Get the list of items in the subcategory
    //
    $strsql = "SELECT items.id, "
        . "items.name, "
        . "items.permalink, "
        . "items.restype, "
        . "items.url, "
        . "items.org_filename, "
        . "items.flags, "
        . "items.thumbnail_image_id, "
        . "items.synopsis "
        . "FROM ciniki_reslib_items AS items "
        . "WHERE items.subcategory_id = '" . ciniki_core_dbQuote($ciniki, $subcategory_id) . "' "
        . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY items.sequence, items.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'items', 'fname'=>'id', 
            'fields'=>array('id', 'title'=>'name', 'permalink', 'restype', 'reslib_url'=>'url', 'org_filename',
                'flags', 'image-id'=>'thumbnail_image_id', 'synopsis', 
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.80', 'msg'=>'Unable to load items', 'err'=>$rc['err']));
    }
    $items = isset($rc['items']) ? $rc['items'] : array();

    if( count($items) < 1 ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'error', 
            'content' => 'Nothing found',
            ];
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    $restypes = [];
    foreach($items as $iid => $item) {  
        if( $item['restype'] == 10 ) {
            $items[$iid]['url'] = "{$base_url}/{$item['permalink']}/download";
        } else {
            $items[$iid]['url'] = "{$base_url}/{$item['permalink']}";
        }
        if( !in_array($item['restype'], $restypes) ) {
            $restypes[] = $item['restype'];
        }
    }

    if( isset($s['title']) && $s['title'] != '' 
        && isset($s['subcategory-permalink']) 
        && isset($reslib_subcategory['description']) && $reslib_subcategory['description'] != '' 
        ) {
        $blocks[] = [
            'type' => 'text',
            'title' => $s['title'],
            'content' => $reslib_subcategory['description'],
            ];
    } 
    elseif( isset($s['title']) && $s['title'] != '' && isset($s['subcategory-id']) && isset($s['content']) && $s['content'] != '' ) {
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
    if( isset($s['subcategory-search']) && $s['subcategory-search'] == 'yes' ) {
    error_log(print_r($s,true));
        $api_args = [
            'subcategory_id' => $subcategory_id,
            'image_ratio' => isset($s['subcat-image-ratio']) ? $s['subcat-image-ratio'] : '1-1',
            'format' => isset($s['subcategory-search-layout']) ? $s['subcategory-search-layout'] : 'table',
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
    // Display the list of items
    //
    if( count($restypes) == 1 && $restypes[0] == 10 ) {
        $blocks[] = [
            'type' => 'filelist',
            'items' => $items,
            ];
    } else {
        $blocks[] = [
            'type' => 'flexcards',
            'class' => 'reslib-subcategory',
            'image-ratio' => isset($s['subcat-image-ratio']) && $s['subcat-image-ratio'] != '' ? $s['subcat-image-ratio'] : '1-1',
            'items' => $items,
            ];
        
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
