<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_reslib_categoryUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
        'section_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Section'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'customer_perms'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Permissions'),
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
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.categoryUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($args['customer_perms']) && $args['customer_perms'] != '' ) {
        if( $args['customer_perms'][0] != ':' && $args['customer_perms'][1] != ':' ) {
            $args['customer_perms'] = '::' . $args['customer_perms'] . '::';
        }
    }

    $strsql = "SELECT categories.id, "
        . "categories.section_id, "
        . "categories.name, "
        . "categories.sequence "
        . "FROM ciniki_reslib_categories AS categories "
        . "WHERE categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'category');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.45', 'msg'=>'Unable to load category', 'err'=>$rc['err']));
    }
    if( !isset($rc['category']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.46', 'msg'=>'Unable to find requested category'));
    }
    $category = $rc['category'];
    

    if( isset($args['name']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_reslib_categories "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND section_id = '" . ciniki_core_dbQuote($ciniki, isset($args['section_id']) ? $args['section_id'] : $category['section_id']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.28', 'msg'=>'You already have an category with this name, please choose another.'));
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.reslib');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Category in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.reslib.category', $args['category_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reslib');
        return $rc;
    }

    //
    // Check if sequences should be updated
    //
    if( isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
        $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.reslib.category', 
            'section_id', isset($args['section_id']) ? $args['section_id'] : $category['section_id'], $args['sequence'], $category['sequence']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.reslib.category', 'object_id'=>$args['category_id']));

    return array('stat'=>'ok');
}
?>
