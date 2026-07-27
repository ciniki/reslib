<?php
//
// Description
// -----------
// This method will return the list of Subcategorys for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Subcategory for.
//
// Returns
// -------
//
function ciniki_reslib_subcategoryList($ciniki) {
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
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.subcategoryList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of subcategories
    //
    $strsql = "SELECT ciniki_reslib_subcategories.id, "
        . "ciniki_reslib_subcategories.category_id, "
        . "ciniki_reslib_subcategories.name, "
        . "ciniki_reslib_subcategories.permalink, "
        . "ciniki_reslib_subcategories.flags, "
        . "ciniki_reslib_subcategories.sequence "
        . "FROM ciniki_reslib_subcategories "
        . "WHERE ciniki_reslib_subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'subcategories', 'fname'=>'id', 
            'fields'=>array('id', 'category_id', 'name', 'permalink', 'flags', 'sequence')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $subcategories = isset($rc['subcategories']) ? $rc['subcategories'] : array();
    $subcategory_ids = array();
    foreach($subcategories as $iid => $subcategory) {
        $subcategory_ids[] = $subcategory['id'];
    }

    return array('stat'=>'ok', 'subcategories'=>$subcategories, 'nplist'=>$subcategory_ids);
}
?>
