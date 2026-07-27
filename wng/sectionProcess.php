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
function ciniki_reslib_wng_sectionProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.reslib']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.reslib.9', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.10', 'msg'=>"No section specified"));
    }
    $s = $section['settings'];
    $blocks = [];
    $base_url = $request['ssl_domain_base_url'] . $request['page']['path'];

    //
    // Check for section
    //
    if( !isset($s['section-id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.13', 'msg'=>'No section specified'));
    }
    $section_id = $s['section-id'];

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
    $strsql = "SELECT sections.id, "
        . "sections.name, "
        . "sections.permalink "
        . "FROM ciniki_reslib_sections AS sections "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $section_id) . "' "
        . $section_perms_sql        // Make sure customer has permission to this section
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'section');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.14', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
    }
    if( !isset($rc['section']) ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'error', 
            'content' => 'Not found',
            ];
        return array('stat'=>'404', 'blocks'=>$blocks);
    }
    $reslib_section = $rc['section'];
    
    //
    // Get the list categories/subcats for the section 
    //
    $strsql = "SELECT categories.id, "
        . "categories.name, "
        . "categories.permalink, "
        . "categories.image_id, "
        . "categories.synopsis, "
        . "subcats.id AS subcat_id, "
        . "subcats.name AS subcat_name, "
        . "subcats.permalink AS subcat_permalink "
        . "FROM ciniki_reslib_categories AS categories "
        . "INNER JOIN ciniki_reslib_subcategories AS subcats ON ("
            . "categories.id = subcats.category_id "
            . $subcat_perms_sql
            . "AND subcats.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $section_id) . "' "
        . $category_perms_sql
        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY categories.sequence, categories.name, subcats.sequence, subcats.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'categories', 'fname'=>'id', 
            'fields'=>array('id', 'title'=>'name', 'permalink', 'image-id'=>'image_id', 'synopsis'),
            ),
        array('container'=>'subcats', 'fname'=>'subcat_id', 
            'fields'=>array('id'=>'subcat_id', 'name'=>'subcat_name', 'permalink'=>'subcat_permalink'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.12', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();

    if( count($categories) < 1 ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'error', 
            'content' => 'No categories found',
            ];
        return array('stat'=>'404', 'blocks'=>$blocks);
    }

    //
    // Setup the buttons for the trading cards to display list of subcategories
    //
    if( isset($s['layout']) && $s['layout'] == 'tradingcards-subcatbuttons' ) {
        foreach($categories as $cid => $cat) {
            if( !isset($cat['subcats']) ) {
                continue;
            }
            $categories[$cid]['buttons'] = [];
            foreach($cat['subcats'] as $sid => $subcat) {
                $categories[$cid]['buttons'][] = [
                    'text' => $subcat['name'],
                    'url' => "{$base_url}/{$cat['permalink']}/{$subcat['permalink']}",
                    ];
            }
            unset($categories[$cid]['subcats']);
        }
    } else {
        foreach($categories as $cid => $cat) {
            if( !isset($cat['subcats']) ) {
                continue;
            }
            $categories[$cid]['url'] = "{$base_url}/{$cat['permalink']}";
            unset($categories[$cid]['subcats']);
        }
    }

    if( isset($s['title']) && $s['title'] != '' 
        && isset($s['content']) && $s['content'] != '' 
        ) {
        $blocks[] = [
            'type' => 'text',
            'title' => $s['title'],
            'content' => $s['content'],
            ];
    }
    //
    // Display the list of categories using trading cards 
    //
/*    foreach($categories as $cat) {
        $blocks[] = [
            'type' => 'contentphoto',
            'image-size' => 'small',
            'image-position' => 'top-left',
            'image-id' => $cat['image-id'],
            'title' => $cat['title'],
            'content' => $cat['synopsis'],
            'button-1-text' => 'Forms',
            'button-1-url' => "{$base_url}/{$cat['permalink']}/forms",
            'button-2-text' => 'Syllabi',
            'button-2-url' => "{$base_url}/{$cat['permalink']}/syllabi",
            'button-3-text' => 'Videos',
            'button-3-url' => "{$base_url}/{$cat['permalink']}/videos",
            ];
    } */
    $blocks[] = [
        'type' => 'tradingcards',
        'items' => $categories,
        ];

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
