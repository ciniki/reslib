<?php
//
// Description
// ===========
// This method will return all the information about an section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the section is attached to.
// section_id:          The ID of the section to get the details for.
//
// Returns
// -------
//
function ciniki_reslib_sectionGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'section_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Section'),
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
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.sectionGet');
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
    // Return default for new Section
    //
    if( $args['section_id'] == 0 ) {
        $section = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'image_id'=>'0',
            'flags'=>'0',
            'sequence'=>'',
            'synopsis'=>'',
            'description'=>'',
            'customer_perms'=>'',
        );
    }

    //
    // Get the details for an existing Section
    //
    else {
        $strsql = "SELECT ciniki_reslib_sections.id, "
            . "ciniki_reslib_sections.name, "
            . "ciniki_reslib_sections.permalink, "
            . "ciniki_reslib_sections.image_id, "
            . "ciniki_reslib_sections.flags, "
            . "ciniki_reslib_sections.sequence, "
            . "ciniki_reslib_sections.synopsis, "
            . "ciniki_reslib_sections.description, "
            . "ciniki_reslib_sections.customer_perms "
            . "FROM ciniki_reslib_sections "
            . "WHERE ciniki_reslib_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_reslib_sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('name', 'permalink', 'image_id', 'flags', 'sequence', 'synopsis', 'description', 'customer_perms'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.19', 'msg'=>'Section not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['sections'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.20', 'msg'=>'Unable to find Section'));
        }
        $section = $rc['sections'][0];
        if( $section['customer_perms'] != '' ) {
            $section['restrictions'] = 'limit';
        }
    }

    $rsp = array('stat'=>'ok', 'section'=>$section);

    //
    // Get the default customers permission tags
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'permissionTags');
    $rc = ciniki_customers_hooks_permissionTags($ciniki, $args['tnid'], []);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['customers-permission-tags'] = isset($rc['tags']) ? $rc['tags'] : [];

    return $rsp;
}
?>
