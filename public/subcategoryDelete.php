<?php
//
// Description
// -----------
// This method will delete an subcategory.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the subcategory is attached to.
// subcategory_id:            The ID of the subcategory to be removed.
//
// Returns
// -------
//
function ciniki_reslib_subcategoryDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'subcategory_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Subcategory'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'private', 'checkAccess');
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.subcategoryDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the subcategory
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_reslib_subcategories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'subcategory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['subcategory']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.30', 'msg'=>'Subcategory does not exist.'));
    }
    $subcategory = $rc['subcategory'];

    //
    // Check for any dependencies before deleting
    //
    $strsql = "SELECT 'items', COUNT(*) "
        . "FROM ciniki_reslib_items "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND subcategory_id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.reslib', 'num');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
        $count = $rc['num']['items'];
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.64', 'msg'=>'There ' . ($count==1?'is':'are') . ' still ' . $count . ' item' . ($count==1?'':'s') . ' in the subcategory.'));
    }

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.reslib.subcategory', $args['subcategory_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.31', 'msg'=>'Unable to check if the subcategory is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.32', 'msg'=>'The subcategory is still in use. ' . $rc['msg']));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.reslib');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the subcategory
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.reslib.subcategory',
        $args['subcategory_id'], $subcategory['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reslib');
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

    return array('stat'=>'ok');
}
?>
