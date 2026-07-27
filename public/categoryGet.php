<?php
//
// Description
// ===========
// This method will return all the information about an category.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the category is attached to.
// category_id:          The ID of the category to get the details for.
//
// Returns
// -------
//
function ciniki_reslib_categoryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
        'section_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Section'),
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
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.categoryGet');
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
    // Return default for new Category
    //
    if( $args['category_id'] == 0 ) {
        $category = array('id'=>0,
            'section_id'=>isset($args['section_id']) ? $args['section_id'] : '',
            'name'=>'',
            'permalink'=>'',
            'flags'=>'0',
            'sequence'=>'',
            'image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
            'customer_perms'=>'',
        );
    }

    //
    // Get the details for an existing Category
    //
    else {
        $strsql = "SELECT ciniki_reslib_categories.id, "
            . "ciniki_reslib_categories.section_id, "
            . "ciniki_reslib_categories.name, "
            . "ciniki_reslib_categories.permalink, "
            . "ciniki_reslib_categories.flags, "
            . "ciniki_reslib_categories.sequence, "
            . "ciniki_reslib_categories.image_id, "
            . "ciniki_reslib_categories.synopsis, "
            . "ciniki_reslib_categories.description, "
            . "ciniki_reslib_categories.customer_perms "
            . "FROM ciniki_reslib_categories "
            . "WHERE ciniki_reslib_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_reslib_categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
            array('container'=>'categories', 'fname'=>'id', 
                'fields'=>array('section_id', 'name', 'permalink', 'flags', 'sequence', 'image_id', 'synopsis', 'description', 'customer_perms'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.26', 'msg'=>'Category not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['categories'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.27', 'msg'=>'Unable to find Category'));
        }
        $category = $rc['categories'][0];
        if( $category['customer_perms'] != '' ) {
            $category['restrictions'] = 'limit';
        }
    }

    $rsp = array('stat'=>'ok', 'category'=>$category);

    //
    // Get the section list
    //
    $strsql = "SELECT sections.id, "
        . "sections.name "
        . "FROM ciniki_reslib_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sections.sequence, sections.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.37', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
    }
    $rsp['sections'] = isset($rc['sections']) ? $rc['sections'] : array();

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
