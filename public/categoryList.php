<?php
//
// Description
// -----------
// This method will return the list of Categorys for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Category for.
//
// Returns
// -------
//
function ciniki_reslib_categoryList($ciniki) {
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
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.categoryList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of categories
    //
    $strsql = "SELECT ciniki_reslib_categories.id, "
        . "ciniki_reslib_categories.section_id, "
        . "ciniki_reslib_categories.name, "
        . "ciniki_reslib_categories.permalink, "
        . "ciniki_reslib_categories.flags, "
        . "ciniki_reslib_categories.sequence "
        . "FROM ciniki_reslib_categories "
        . "WHERE ciniki_reslib_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'categories', 'fname'=>'id', 
            'fields'=>array('id', 'section_id', 'name', 'permalink', 'flags', 'sequence')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();
    $categorie_ids = array();
    foreach($categories as $iid => $categorie) {
        $categorie_ids[] = $categorie['id'];
    }

    return array('stat'=>'ok', 'categories'=>$categories, 'nplist'=>$categorie_ids);
}
?>
