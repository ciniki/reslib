<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_reslib_itemUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'item_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Item'),
        'subcategory_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subcategory'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
// Restype can't be changed after added, must be deleted and recreated
//        'restype'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Item Type'),
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
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'private', 'checkAccess');
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.itemUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Get the current item
    //
    $strsql = "SELECT items.id, "
        . "items.uuid, "
        . "items.subcategory_id, "
        . "items.name, "
        . "items.restype, "
        . "items.sequence, "
        . "items.url, "
        . "items.org_filename "
        . "FROM ciniki_reslib_items AS items "
        . "WHERE items.id = '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
        . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.65', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.66', 'msg'=>'Unable to find requested item'));
    }
    $item = $rc['item'];

    //
    // Check if unique name
    //
    if( isset($args['name']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_reslib_items "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.55', 'msg'=>'You already have an item with this name, please choose another.'));
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
    // Check if file uploaded
    //
    if( isset($_FILES) ) {
        foreach($_FILES as $field_name => $file) {
            error_log($field_name);
            if( $field_name != 'org_filename' ) {
                error_log('UNKNOWN FILE: ' . $field_name);
                continue;
            }
            if( isset($file['error']) && $file['error'] == UPLOAD_ERR_INI_SIZE ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.59', 'msg'=>'Upload failed, file too large.'));
            }
            if( !isset($file['tmp_name']) || $file['tmp_name'] == '' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.60', 'msg'=>'No file specified.'));
            }

            $args["org_filename"] = $file['name'];

            //
            // Move the file to ciniki-storage
            //
            $storage_filename = $tenant_storage_dir . "/ciniki.reslib/files/{$item['uuid'][0]}/{$item['uuid']}";
            if( !is_dir(dirname($storage_filename)) ) {
                if( !mkdir(dirname($storage_filename), 0700, true) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.61', 'msg'=>'Unable to add file'));
                }
            }
            if( !rename($file['tmp_name'], $storage_filename) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.62', 'msg'=>'Unable to add file'));
            }
        }
    }

    //
    // Update the Item in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.reslib.item', $args['item_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reslib');
        return $rc;
    }

    //
    // Update the keywords
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'private', 'itemKeywordsUpdate');
    $rc = ciniki_reslib_itemKeywordsUpdate($ciniki, $args['tnid'], $args['item_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if sequences should be updated
    //
    if( isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
        $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.reslib.item', 
            'subcategory_id', isset($args['subcategory_id']) ? $args['subcategory_id'] : $item['subcategory_id'], $args['sequence'], $item['sequence']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reslib');
            return $rc;
        }
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.reslib.item', 'object_id'=>$args['item_id']));

    return array('stat'=>'ok');
}
?>
