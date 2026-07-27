<?php
//
// Description
// ===========
// This method will return all the information about an item.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the item is attached to.
// item_id:          The ID of the item to get the details for.
//
// Returns
// -------
//
function ciniki_reslib_itemGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'item_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Item'),
        'subcategory_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subcategory'),
        'download'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Download'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'private', 'checkAccess');
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.itemGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Item
    //
    if( $args['item_id'] == 0 ) {
        $item = array('id'=>0,
            'subcategory_id' => (isset($args['subcategory_id']) ? $args['subcategory_id'] : 0),
            'name'=>'',
            'permalink'=>'',
            'restype'=>'',
            'url'=>'',
            'org_filename'=>'',
            'flags'=>'0',
            'sequence'=>'',
            'thumbnail_image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
            'additional_keywords'=>'',
            'keywords'=>'',
        );
    }

    //
    // Get the details for an existing Item
    //
    else {
        $strsql = "SELECT ciniki_reslib_items.id, "
            . "ciniki_reslib_items.uuid, "
            . "ciniki_reslib_items.subcategory_id, "
            . "ciniki_reslib_items.name, "
            . "ciniki_reslib_items.permalink, "
            . "ciniki_reslib_items.restype, "
            . "ciniki_reslib_items.url, "
            . "ciniki_reslib_items.org_filename, "
            . "ciniki_reslib_items.flags, "
            . "ciniki_reslib_items.sequence, "
            . "ciniki_reslib_items.thumbnail_image_id, "
            . "ciniki_reslib_items.synopsis, "
            . "ciniki_reslib_items.description, "
            . "ciniki_reslib_items.additional_keywords, "
            . "ciniki_reslib_items.keywords "
            . "FROM ciniki_reslib_items "
            . "WHERE ciniki_reslib_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_reslib_items.id = '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
            array('container'=>'items', 'fname'=>'id', 
                'fields'=>array('subcategory_id', 'name', 'permalink', 'restype', 'url', 'org_filename', 'flags', 'sequence', 'thumbnail_image_id', 'synopsis', 'description', 'additional_keywords', 'keywords'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.53', 'msg'=>'Item not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['items'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.54', 'msg'=>'Unable to find Item'));
        }
        $item = $rc['items'][0];

        if( isset($args['download']) && $args['download'] == 'yes' ) {
            //
            // Get the tenant storage directory
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
            $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $storage_filename = $rc['storage_dir'] . '/ciniki.musicfestivals/files/' . $item['uuid'][0] . '/' . $item['uuid'];
            if( !file_exists($storage_filename) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.68', 'msg'=>'File does not exist'));
            }

            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            // Set mime header
            $finfo = finfo_open(FILEINFO_MIME);
            if( $finfo ) { 
                header('Content-Type: ' . finfo_file($finfo, $storage_filename)); 
            }
            // Specify Filename
            header('Content-Disposition: attachment;filename="' . $item['org_filename'] . '"');
            header('Content-Length: ' . filesize($storage_filename));
            header('Cache-Control: max-age=0');

            $fp = fopen($storage_filename, 'rb');
            fpassthru($fp);

            return array('stat'=>'binary');
        }
    }

    $rsp = array('stat'=>'ok', 'item'=>$item);

    //
    // Get the subcategory list
    //
    $strsql = "SELECT subcategories.id, "
        . "CONCAT_WS(' - ', sections.name, categories.name, subcategories.name) AS name "
        . "FROM ciniki_reslib_sections AS sections "
        . "INNER JOIN ciniki_reslib_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_reslib_subcategories AS subcategories ON ("
            . "categories.id = subcategories.category_id "
            . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'subcategories', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.63', 'msg'=>'Unable to load subcategories', 'err'=>$rc['err']));
    }
    $rsp['subcategories'] = isset($rc['subcategories']) ? $rc['subcategories'] : array();

    return $rsp;
}
?>
