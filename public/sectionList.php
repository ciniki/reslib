<?php
//
// Description
// -----------
// This method will return the list of Sections for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Section for.
//
// Returns
// -------
//
function ciniki_reslib_sectionList($ciniki) {
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
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.sectionList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of sections
    //
    $strsql = "SELECT ciniki_reslib_sections.id, "
        . "ciniki_reslib_sections.name, "
        . "ciniki_reslib_sections.permalink, "
        . "ciniki_reslib_sections.flags, "
        . "ciniki_reslib_sections.sequence "
        . "FROM ciniki_reslib_sections "
        . "WHERE ciniki_reslib_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'sections', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'flags', 'sequence')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();
    $section_ids = array();
    foreach($sections as $iid => $section) {
        $section_ids[] = $section['id'];
    }

    return array('stat'=>'ok', 'sections'=>$sections, 'nplist'=>$section_ids);
}
?>
