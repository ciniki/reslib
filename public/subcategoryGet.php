<?php
//
// Description
// ===========
// This method will return all the information about an subcategory.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the subcategory is attached to.
// subcategory_id:          The ID of the subcategory to get the details for.
//
// Returns
// -------
//
function ciniki_reslib_subcategoryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'subcategory_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subcategory'),
        'category_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Category'),
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
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.subcategoryGet');
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
    // Return default for new Subcategory
    //
    if( $args['subcategory_id'] == 0 ) {
        $subcategory = array('id'=>0,
            'category_id'=>isset($args['category_id']) ? $args['category_id'] : '',
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
    // Get the details for an existing Subcategory
    //
    else {
        $strsql = "SELECT ciniki_reslib_subcategories.id, "
            . "ciniki_reslib_subcategories.category_id, "
            . "ciniki_reslib_subcategories.name, "
            . "ciniki_reslib_subcategories.permalink, "
            . "ciniki_reslib_subcategories.flags, "
            . "ciniki_reslib_subcategories.sequence, "
            . "ciniki_reslib_subcategories.image_id, "
            . "ciniki_reslib_subcategories.synopsis, "
            . "ciniki_reslib_subcategories.description, "
            . "ciniki_reslib_subcategories.customer_perms "
            . "FROM ciniki_reslib_subcategories "
            . "WHERE ciniki_reslib_subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_reslib_subcategories.id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
            array('container'=>'subcategories', 'fname'=>'id', 
                'fields'=>array('category_id', 'name', 'permalink', 'flags', 'sequence', 'image_id', 'synopsis', 'description', 'customer_perms'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.33', 'msg'=>'Subcategory not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['subcategories'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.34', 'msg'=>'Unable to find Subcategory'));
        }
        $subcategory = $rc['subcategories'][0];
        if( $subcategory['customer_perms'] != '' ) {
            $subcategory['restrictions'] = 'limit';
        }
    }

    $rsp = array('stat'=>'ok', 'subcategory'=>$subcategory);

    //
    // Get the category list
    //
    $strsql = "SELECT categories.id, "
        . "CONCAT_WS(' - ', sections.name, categories.name) AS name "
        . "FROM ciniki_reslib_sections AS sections "
        . "INNER JOIN ciniki_reslib_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.38', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $rsp['categories'] = isset($rc['categories']) ? $rc['categories'] : array();

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
