<?php
//
// Description
// -----------
// Changes the customer_id when merging ciniki.customers
//
// Arguments
// ---------
// ciniki:
// tnid:
// args: The arguments for the hook
//
// Returns
// -------
//
function ciniki_reslib_hooks_customerMerge(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    
    if( !isset($args['primary_customer_id']) || $args['primary_customer_id'] == ''
        || !isset($args['secondary_customer_id']) || $args['secondary_customer_id'] == ''
        ) {
        return array('stat'=>'ok');
    }
    
    //
    // Keep track of how many items we've updated
    //
    $updated = 0;

    //
    // Get the list to update
    //
    $strsql = "SELECT id "
        . "FROM ciniki_reslib_ "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.4', 'msg'=>'Unable to find customers', 'err'=>$rc['err']));{
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.reslib.object', $row['id'], array('customer_id'=>$args['primary_customer_id']), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.5', 'msg'=>'Unable to update object', 'err'=>$rc['err']));
        }
        $updated++;
    }

    if( $updated > 0 ) {
        //
        // Update the last_change date in the tenant modules
        // Ignore the result, as we don't want to stop user updates if this fails.
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
        ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'reslib');
    }

    return array('stat'=>'ok', 'updated'=>$updated);
}
?>
