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
function ciniki_reslib_wng_itemProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.reslib']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.reslib.83', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.84', 'msg'=>"No section specified"));
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
    $strsql = "SELECT items.id, "
        . "items.uuid, "
        . "items.name, "
        . "items.restype, "
        . "items.url, "
        . "items.org_filename, "
        . "items.flags, "
        . "items.thumbnail_image_id, "
        . "items.synopsis, "
        . "items.description, "
        . "subcats.id AS subcat_id, "
        . "subcats.name AS subcat_name, "
        . "subcats.permalink AS subcat_permalink, "
        . "subcats.flags, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.permalink AS category_permalink, "
        . "sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "sections.permalink AS section_permalink "
        . "FROM ciniki_reslib_items AS items "
        . "INNER JOIN ciniki_reslib_subcategories AS subcats ON ("
            . "items.subcategory_id = subcats.id "
            . $subcat_perms_sql
            . "AND subcats.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . " )"
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
    if( isset($s['item-id']) ) {
        $strsql .= "WHERE items.id = '" . ciniki_core_dbQuote($ciniki, $s['item-id']) . "' ";
    }
    elseif( isset($s['item-permalink']) ) {
        $strsql .= "WHERE items.permalink = '" . ciniki_core_dbQuote($ciniki, $s['item-permalink']) . "' ";
        if( isset($s['subcategory-id']) ) {
            $strsql .= "AND subcats.id = '" . ciniki_core_dbQuote($ciniki, $s['subcategory-id']) . "' ";
        } elseif( isset($s['subcategory-permalink']) ) {
            $strsql .= "AND subcats.permalink = '" . ciniki_core_dbQuote($ciniki, $s['subcategory-permalink']) . "' ";
            if( isset($s['category-id']) ) {
                $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $s['category-id']) . "' ";
            } elseif( isset($s['category-permalink']) ) {
                $strsql .= "AND categories.permalink = '" . ciniki_core_dbQuote($ciniki, $s['category-permalink']) . "' ";
                if( isset($s['section-id']) ) {
                    $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $s['section-id']) . "' ";
                } elseif( isset($s['section-permalink']) ) {
                    $strsql .= "AND sections.permalink = '" . ciniki_core_dbQuote($ciniki, $s['section-permalink']) . "' ";
                } else {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.85', 'msg'=>'No section specified'));
                }
            } else {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.86', 'msg'=>'No category specified'));
            }
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.87', 'msg'=>'No subcategory specified'));
        }
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.88', 'msg'=>'No subcategory specified'));
    }
    $strsql . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.89', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    if( !isset($rc['item']) ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'error', 
            'content' => 'Not found',
            ];
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }
    $item_id = $rc['item']['id'];
    $item = $rc['item'];

    //
    // Check if file to download
    //
    if( $item['restype'] == 10 
        && isset($request['uri_split'][($request['cur_uri_pos']+1)]) 
        && $request['uri_split'][($request['cur_uri_pos']+1)] == 'download'
        ) {
        //
        // Get the tenant storage directory
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
        $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $tenant_storage_dir = $rc['storage_dir'];

        $storage_filename = $tenant_storage_dir . '/ciniki.reslib/files/' . $item['uuid'][0]
            . '/' . $item['uuid'];
        if( !is_file($storage_filename) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.90', 'msg'=>'Unable to find file'));
        }

        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        $finfo = finfo_open(FILEINFO_MIME);
        if( $finfo ) {
            $content_type = finfo_file($finfo, $storage_filename);
        }
        if( $content_type != '' ) {
            header('Content-Type: ' . $content_type);
        }
        header('Content-Disposition: filename="' . $item['org_filename'] . '"');
        header('Content-Length: ' . filesize($storage_filename));
        header('Cache-Control: max-age=0');

        $fp = fopen($storage_filename, 'rb');
        fpassthru($fp);
        return array('stat'=>'exit');
    }

    if( isset($s['section-permalink']) ) {
        $s['title'] .= ' - ' . $item['section_name'];
    }
    if( isset($s['category-permalink']) ) {
        $s['title'] .= ' - ' . $item['category_name'];
    }
    if( isset($s['subcategory-permalink']) ) {
        $s['title'] .= ' - ' . $item['subcat_name'];
    }

    $blocks[] = [
        'type' => 'title',
        'title' => $s['title'],
        ];
    if( $item['restype'] == 30 ) {
        $blocks[] = [
            'type' => 'contentvideo',
            'title' => $item['name'],
            'video-position' => 'top',
            'sequence' => 2,
            'video-url' => $item['url'],
            'content' => $item['description'],
            'clickload' => 'no',
            ];
    } else {
        if( $item['description'] != '' ) {
            $blocks[] = [
                'type' => 'text',
                'title' => $s['name'],
                'content' => $item['description'],
                ];
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
