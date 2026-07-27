<?php
//
// Description
// -----------
// This method will return the list of Items for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Item for.
//
// Returns
// -------
//
function ciniki_reslib_itemList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'private', 'checkAccess');
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.itemList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of items
    //
    $strsql = "SELECT ciniki_reslib_items.id, "
        . "ciniki_reslib_items.subcategory_id, "
        . "ciniki_reslib_items.name, "
        . "ciniki_reslib_items.permalink, "
        . "ciniki_reslib_items.restype, "
        . "ciniki_reslib_items.url, "
        . "ciniki_reslib_items.org_filename, "
        . "ciniki_reslib_items.flags, "
        . "ciniki_reslib_items.sequence, "
        . "ciniki_reslib_items.thumbnail_image_id "
        . "FROM ciniki_reslib_items "
        . "WHERE ciniki_reslib_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'items', 'fname'=>'id', 
            'fields'=>array('id', 'subcategory_id', 'name', 'permalink', 'restype', 'url', 'org_filename', 'flags', 'sequence', 'thumbnail_image_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $items = isset($rc['items']) ? $rc['items'] : array();
    $item_ids = array();
    foreach($items as $iid => $item) {
        $item_ids[] = $item['id'];
    }

    return array('stat'=>'ok', 'items'=>$items, 'nplist'=>$item_ids);
}
?>
