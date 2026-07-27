<?php
//
// Description
// -----------
// This method will add a new item for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Item to.
//
// Returns
// -------
//
function ciniki_reslib_itemAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'subcategory_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subcategory'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'restype'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Item Type'),
        'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Url'),
        'org_filename'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Filename'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'thumbnail_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Thumbnail'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'additional_keywords'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Additional Keywords'),
        'keywords'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Keywords'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'private', 'checkAccess');
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.itemAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Setup permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
    }

    //
    // Make sure the permalink is unique
    //
    $strsql = "SELECT id, name, permalink "
        . "FROM ciniki_reslib_items "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND subcategory_id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.49', 'msg'=>'You already have a item with that name, please choose another.'));
    }

    //
    // Get a new UUID
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.reslib');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.67', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
    }
    $args['uuid'] = $rc['uuid'];

    //
    // Get the next sequence if not specified
    //
    if( !isset($args['sequence']) ) {
        $strsql = "SELECT MAX(sequence) AS max "
            . "FROM ciniki_reslib_items "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND subcategory_id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'seq');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.69', 'msg'=>'Unable to load seq', 'err'=>$rc['err']));
        }
        if( isset($rc['seq']['max']) ) {
            $args['sequence'] = $rc['seq']['max'] + 1;
        } else {
            $args['sequence'] = 1;
        }
    }

    //
    // Check if file uploaded
    //
    if( isset($_FILES) ) {
        foreach($_FILES as $field_name => $file) {
            if( $field_name != 'org_filename' ) {
                error_log('UNKNOWN FILE: ' . $field_name);
                continue;
            }
            if( isset($file['error']) && $file['error'] == UPLOAD_ERR_INI_SIZE ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.70', 'msg'=>'Upload failed, file too large.'));
            }
            if( !isset($file['tmp_name']) || $file['tmp_name'] == '' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.71', 'msg'=>'No file specified.'));
            }

            $args["org_filename"] = $file['name'];

            //
            // Move the file to ciniki-storage
            //
            $storage_filename = $tenant_storage_dir . "/ciniki.reslib/files/{$args['uuid'][0]}/{$args['uuid']}";
            if( !is_dir(dirname($storage_filename)) ) {
                if( !mkdir(dirname($storage_filename), 0700, true) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.72', 'msg'=>'Unable to add file'));
                }
            }
            if( !rename($file['tmp_name'], $storage_filename) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.73', 'msg'=>'Unable to add file'));
            }
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.reslib');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the item to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.reslib.item', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reslib');
        return $rc;
    }
    $item_id = $rc['id'];

    //
    // Update the keywords
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'private', 'itemKeywordsUpdate');
    $rc = ciniki_reslib_itemKeywordsUpdate($ciniki, $args['tnid'], $item_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.reslib');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'reslib');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.reslib.item', 'object_id'=>$item_id));

    return array('stat'=>'ok', 'id'=>$item_id);
}
?>
