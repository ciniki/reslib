<?php
//
// Description
// -----------
// This function will check for objects that are referenced in other modules.
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
function ciniki_reslib_hooks_checkObjectUsed(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

    // Set the default not used
    $used = 'no';
    $count = 0;
    $msg = '';

    //
    // Check for customers
    //
    if( $args['object'] == 'ciniki.customers.customer' ) {
        //
        // Check the tables
        //
        $strsql = "SELECT 'items', COUNT(*) "
            . "FROM ciniki_reslib "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.reslib', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
            $used = 'yes';
            $count = $rc['num']['items'];
            $msg .= "The profile is still used by " . $rc['num']['items'] . " in reslib";
        }
        
    }

    //
    // Check for images
    //
    if( $args['object'] == 'ciniki.images.image' ) {
        //
        // Check the tables
        //
        $strsql = "SELECT 'items', COUNT(*) "
            . "FROM ciniki_reslib "
            . "WHERE primary_image_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.reslib', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
            $used = 'yes';
            $count = $rc['num']['items'];
            $msg .= "The image is still used by " . $rc['num']['items'] . " in reslib";
        }
        
    }

    return array('stat'=>'ok', 'used'=>$used, 'count'=>$count, 'msg'=>$msg);
}
?>
