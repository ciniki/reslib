<?php
//
// Description
// -----------
// Search the wng index
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_reslib_wng_apiSearch(&$ciniki, $tnid, $request) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'cacheImageAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makeKeywords');

    if( !isset($request['args']['search_string']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.93', 'msg'=>'No search string specified'));
    }
    if( !isset($request['args']['base_url']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.94', 'msg'=>'Invalid Search'));
    }
    $base_url = $request['args']['base_url'];

    $format = 'table';
    if( isset($request['args']['format']) && $request['args']['format'] != '' ) {
        $format = $request['args']['format'];
    }

    $search_str = urldecode($request['args']['search_string']);
    if( $search_str == '' ) {
        return array('stat'=>'ok', 'content'=>'');
    }
    $limit = 50;

    $words = ciniki_core_makeKeywords($ciniki, $search_str);
    $words = preg_replace("/ /", '%', $words);

    if( $words == '' || strlen($words) < 2 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'generators', 'msg');
        return ciniki_wng_generators_msg($ciniki, $tnid, $request, [
            'type' => 'message',
            'level' => 'warning',
            'content' => 'Keep typing...',
            ]);
    }

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
    // Determine any restrictions to sections/categories/subcategories
    //
    $section_id_sql = '';
    if( isset($request['args']['section_id']) && $request['args']['section_id'] > 0 ) {
        $section_id_sql = "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $request['args']['section_id']) . "' ";
    }
    $category_id_sql = '';
    if( isset($request['args']['category_id']) && $request['args']['category_id'] > 0 ) {
        $category_id_sql = "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $request['args']['category_id']) . "' ";
    }
    $subcategory_id_sql = '';
    if( isset($request['args']['subcategory_id']) && $request['args']['subcategory_id'] > 0 ) {
        $subcategory_id_sql = "AND subcats.id = '" . ciniki_core_dbQuote($ciniki, $request['args']['subcategory_id']) . "' ";
    }

    //
    // Get the list of items
    //
    $strsql = "SELECT items.id, "
        . "items.name, "
        . "items.permalink, "
        . "items.restype, "
        . "items.url, "
        . "items.org_filename, "
        . "items.thumbnail_image_id, "
        . "items.synopsis, "
        . "subcats.name AS subcategory_name, "
        . "subcats.permalink AS subcategory_permalink, "
        . "categories.name AS category_name, "
        . "categories.permalink AS category_permalink, "
        . "sections.name AS section_name, "
        . "sections.permalink AS section_permalink, "
        . "IFNULL(UNIX_TIMESTAMP(images.last_updated), 0) AS last_updated, "
        . "IFNULL(images.type, '') AS image_type, "
        . "IFNULL(images.uuid, '') AS uuid "
        . "FROM ciniki_reslib_items AS items "
        . "INNER JOIN ciniki_reslib_subcategories AS subcats ON ("
            . "items.subcategory_id = subcats.id "
            . $subcategory_id_sql
            . $subcat_perms_sql
            . "AND subcats.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_reslib_categories AS categories ON ("
            . "subcats.category_id = categories.id "
            . $category_id_sql
            . $category_perms_sql
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_reslib_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . $section_id_sql
            . $section_perms_sql
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_images AS images ON ("
            . "items.thumbnail_image_id = images.id "
            . "AND images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ("
            . "items.keywords LIKE '" . ciniki_core_dbQuote($ciniki, $words) . "%' "
            . "OR items.keywords LIKE '% " . ciniki_core_dbQuote($ciniki, $words) . "%' "
            . ") "
        . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, subcats.sequence, subcats.name, name ";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 150 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'items', 'fname'=>'id', 
            'fields'=>array('id', 'restype', 'name', 'permalink', 'url', 'org_filename', 'image-id'=>'thumbnail_image_id', 'synopsis', 
                'subcategory_name', 'subcategory_permalink', 
                'category_name', 'category_permalink', 
                'section_name', 'section_permalink',
                'last_updated', 'type'=>'image_type', 'uuid',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.92', 'msg'=>'Unable to load items', 'err'=>$rc['err']));
    }
    $items = isset($rc['items']) ? $rc['items'] : array();

    $subcats = [];
    foreach($items as $iid => $item) {
        // Check for image
        $items[$iid]['thumb'] = '';
        if( $item['image-id'] > 0 && $format == 'table' ) {
            $rc = ciniki_wng_cacheImageAdd($ciniki, $tnid, $request['site'], [
                'image_id' => $item['image-id'],
                'type' => $item['type'],
                'last_updated' => $item['last_updated'],
                'uuid' => $item['uuid'],
                'version' => 'thumbnail',
                'maxwidth' => 100,
                ]);
            if( $rc['stat'] == 'ok' ) {
                $items[$iid]['thumb'] = "<img src='{$rc['url']}'/>";
            }
        } 
        $items[$iid]['url'] = "{$base_url}";
        $items[$iid]['path'] = "";
        $items[$iid]['subcat_title'] = "";
        if( !isset($request['args']['subcategory_id']) || $request['args']['subcategory_id'] <= 0 ) {
            if( !isset($request['args']['category_id']) || $request['args']['category_id'] <= 0 ) {
                if( !isset($request['args']['section_id']) || $request['args']['section_id'] <= 0 ) {
                    $items[$iid]['url'] .= "/{$item['section_permalink']}";
                    $items[$iid]['path'] .= ($items[$iid]['path'] != '' ? ' - ' : '') . "{$item['section_name']}";
                    $items[$iid]['subcat_title'] .= ($items[$iid]['subcat_title'] != '' ? ' - ' : '') . "{$item['section_name']}";
                }
                $items[$iid]['url'] .= "/{$item['category_permalink']}";
                $items[$iid]['path'] .= ($items[$iid]['path'] != '' ? ' - ' : '') . "{$item['category_name']}";
                $items[$iid]['subcat_title'] .= ($items[$iid]['subcat_title'] != '' ? ' - ' : '') . "{$item['category_name']}";
            }
            $items[$iid]['url'] .= "/{$item['subcategory_permalink']}";
            $items[$iid]['path'] .= ($items[$iid]['path'] != '' ? ' - ' : '') . "{$item['subcategory_name']}";
            $items[$iid]['subcat_title'] .= ($items[$iid]['subcat_title'] != '' ? ' - ' : '') . "{$item['subcategory_name']}";
        }
        if( $item['restype'] == 10 ) {
            $items[$iid]['url'] .= "/{$item['permalink']}/download";
            if( $format == 'table' ) {
                $items[$iid]['name'] = "<a class='link' target='_blank' href='{$items[$iid]['url']}'>{$items[$iid]['name']}</a>";
            }
        } else {
            $items[$iid]['url'] .= "/{$item['permalink']}";
            if( $format == 'table' ) {
                $items[$iid]['name'] = "<a class='link' href='{$items[$iid]['url']}'>{$items[$iid]['name']}</a>";
            }
        }
        if( !isset($subcats[$items[$iid]['subcat_title']]) ) {
            $subcats[$items[$iid]['subcat_title']] = [
                'title' => $items[$iid]['subcat_title'],
                'restype' => $items[$iid]['restype'],
                'items' => [],
                ];
        }
        $subcats[$items[$iid]['subcat_title']]['items'][] = $items[$iid];
        if( $item['restype'] != $subcats[$items[$iid]['subcat_title']]['restype'] ) {
            $subcats[$items[$iid]['subcat_title']]['restype'] = '';
        }

    }

    if( count($items) > 0 ) {
        if( $format == 'flexcards' ) { 
            $blocks[] = [
                'type' => 'flexcards',
                'class' => 'reslib-subcategory',
                'image-ratio' => isset($request['args']['image_ratio']) && $request['args']['image_ratio'] != '' ? $request['args']['image_ratio'] : '1-1',
                'items' => $items,
                ];
        } elseif( $format == 'tradingcards' ) {
            $blocks[] = [
                'type' => 'tradingcards',
                'class' => 'reslib-subcategory',
                'image-ratio' => isset($request['args']['image_ratio']) && $request['args']['image_ratio'] != '' ? $request['args']['image_ratio'] : '1-1',
                'items' => $items,
                ];
        } elseif( $format == 'table' ) {
            $blocks[] = [
                'type' => 'table', 
                'section' => 'classes', 
                'title' => 'Search Results',
                'headers' => 'no',
                'class' => 'fold-at-40 reslib-items',
                'columns' => [
                    ['label'=>'Thumbnail', 'fold-label'=>'', 'field'=>'thumb', 'class'=>''],
                    ['label'=>'Subcategory', 'fold-label'=>'', 'field'=>'path', 'class'=>''],
                    ['label'=>'Name', 'fold-label'=>'', 'field'=>'name', 'class'=>''],
                    ],
                'rows' => $items,
                ]; 
        } else {
            foreach($subcats as $subcat) {
                $title = '';
                if( !isset($request['args']['subcategory_id']) || $request['args']['subcategory_id'] <= 0 ) {
                    $title = $subcat['title'];
                }
                if( $subcat['restype'] == 10 ) {
                    $blocks[] = [
                        'type' => 'filelist',
                        'title' => $title,
                        'level' => 2,
                        'items' => $subcat['items'],
                        ];
                } else {
                    $blocks[] = [
                        'type' => 'title',
                        'level' => 2,
                        'title' => $title,
                        ];
                    $blocks[] = [
                        'type' => 'flexcards',
                        'class' => 'reslib-subcategory',
                        'image-ratio' => isset($request['args']['image_ratio']) && $request['args']['image_ratio'] != '' ? $request['args']['image_ratio'] : '1-1',
                        'items' => $subcat['items'],
                        ];
                }
            }
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'blocksGenerate');
        return ciniki_wng_blocksGenerate($ciniki, $tnid, $request, $blocks);
    } else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'generators', 'msg');
        return ciniki_wng_generators_msg($ciniki, $tnid, $request, [
            'type' => 'message',
            'level' => 'warning',
            'content' => 'Nothing found',
            ]);
        
    }

    return array('stat'=>'ok', 'results'=>$items);
}
?>
