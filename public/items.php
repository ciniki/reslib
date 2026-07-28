<?php
//
// Description
// -----------
// This method will return the elements required for the UI.
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
function ciniki_reslib_items($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'subcategory_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subcategory'),
        'action'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Action'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'private', 'checkAccess');
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.items');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check for actions
    //
    if( isset($args['action']) && $args['action'] == 'resort' 
        && isset($args['subcategory_id']) && $args['subcategory_id'] > 0
        ) {
        $strsql = "SELECT id, sequence "
            . "FROM ciniki_reslib_items "
            . "WHERE subcategory_id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
            array('container'=>'items', 'fname'=>'id', 'fields'=>array('id', 'sequence')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.75', 'msg'=>'Unable to load items', 'err'=>$rc['err']));
        }
        $items = isset($rc['items']) ? $rc['items'] : array();
        $sequence = 1;
        foreach($items as $item) {
            if( $item['sequence'] != $sequence ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.reslib.item', $item['id'], [
                    'sequence' => $sequence,
                    ], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.75', 'msg'=>'Unable to update the item', 'err'=>$rc['err']));
                }
            }
            $sequence++;
        }
    }

    $rsp = ['stat'=>'ok'];

    //
    // Get the list of sections
    //
    $strsql = "SELECT sections.id, "
        . "sections.name, "
        . "sections.flags, "
        . "sections.sequence, "
        . "sections.customer_perms "
        . "FROM ciniki_reslib_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sections.sequence, sections.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name', 'flags', 'sequence', 'customer_perms')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['sections'] = isset($rc['sections']) ? $rc['sections'] : array();
    foreach($rsp['sections'] as $sid => $section) {
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x1000) ) {
            if( $rsp['sections'][$sid]['customer_perms'] == '' ) {
                $rsp['sections'][$sid]['customer_perms'] = '';
            } else {
                $rsp['sections'][$sid]['customer_perms'] = join(', ', explode('::', trim($rsp['sections'][$sid]['customer_perms'], '::')));
            }
        } else {
            $rsp['sections'][$sid]['customer_perms'] = '';
        }
    }

    //
    // Get the list of categories if section specified
    //
    if( isset($args['section_id']) && $args['section_id'] > 0 ) {
        $strsql = "SELECT categories.id, "
            . "categories.name, "
            . "categories.flags, "
            . "categories.sequence, "
            . "categories.customer_perms "
            . "FROM ciniki_reslib_categories AS categories "
            . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
            . "ORDER BY categories.sequence, categories.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
            array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name', 'flags', 'sequence', 'customer_perms')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['categories'] = isset($rc['categories']) ? $rc['categories'] : array();
        foreach($rsp['categories'] as $cid => $cat) {
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x1000) ) {
                if( $rsp['categories'][$cid]['customer_perms'] == '' ) {
                    $rsp['categories'][$cid]['customer_perms'] = '';
                } else {
                    $rsp['categories'][$cid]['customer_perms'] = join(', ', explode('::', trim($rsp['categories'][$cid]['customer_perms'], '::')));
                }
            } else {
                $rsp['categories'][$cid]['customer_perms'] = '';
            }
        }
    }

    //
    // Get the list of subcategories if section specified
    //
    if( isset($args['category_id']) && $args['category_id'] > 0 ) {
        $strsql = "SELECT subcategories.id, "
            . "subcategories.name, "
            . "subcategories.flags, "
            . "subcategories.sequence, "
            . "subcategories.customer_perms "
            . "FROM ciniki_reslib_subcategories AS subcategories "
            . "WHERE subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND subcategories.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "ORDER BY subcategories.sequence, subcategories.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
            array('container'=>'subcategories', 'fname'=>'id', 'fields'=>array('id', 'name', 'flags', 'sequence', 'customer_perms')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['subcategories'] = isset($rc['subcategories']) ? $rc['subcategories'] : array();
        foreach($rsp['subcategories'] as $sid => $subcat) {
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x1000) ) {
                if( $rsp['subcategories'][$sid]['customer_perms'] == '' ) {
                    $rsp['subcategories'][$sid]['customer_perms'] = '';
                } else {
                    $rsp['subcategories'][$sid]['customer_perms'] = join(', ', explode('::', trim($rsp['subcategories'][$sid]['customer_perms'], '::')));
                }
            } else {
                $rsp['subcategories'][$sid]['customer_perms'] = '';
            }
        }
        array_unshift($rsp['subcategories'], ['id'=>0, 'name'=>'All']);
    }

    //
    // Get the list of items
    //
    if( isset($args['category_id']) && $args['category_id'] > 0 ) {
        $strsql = "SELECT sections.id AS section_id, "
            . "sections.name AS section_name, "
            . "categories.id AS category_id, "
            . "categories.name AS category_name, "
            . "subcategories.id AS subcategory_id, "
            . "subcategories.name AS subcategory_name, "
            . "items.id, "
            . "items.thumbnail_image_id, "
            . "items.name, "
            . "items.restype, "
            . "items.flags, "
            . "items.url, "
            . "items.org_filename, "
            . "IFNULL(UNIX_TIMESTAMP(images.last_updated), 0) AS last_updated, "
            . "IFNULL(images.uuid, '') AS uuid "
            . "FROM ciniki_reslib_sections AS sections "
            . "INNER JOIN ciniki_reslib_categories AS categories ON ("
                . "sections.id = categories.section_id ";
        if( isset($args['category_id']) && $args['category_id'] > 0 ) {
            $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
        }
            $strsql .= "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_reslib_subcategories AS subcategories ON ("
                . "categories.id = subcategories.category_id ";
        if( isset($args['subcategory_id']) && $args['subcategory_id'] > 0 ) {
            $strsql .= "AND subcategories.id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' ";
        }
            $strsql .= "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_reslib_items AS items ON ("
                . "subcategories.id = items.subcategory_id "
                . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_images AS images ON ("
                . "items.thumbnail_image_id = images.id "
                . "AND images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
        if( isset($args['section_id']) && $args['section_id'] > 0 ) {
            $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
        }
        $strsql .= "ORDER BY sections.sequence, categories.sequence, subcategories.sequence, items.sequence, items.name ";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
            array('container'=>'items', 'fname'=>'id', 
                'fields'=>array(
                    'id', 'section_name', 'category_name', 'subcategory_name', 'thumbnail_image_id', 'name', 'restype', 'flags', 'url', 'org_filename',
                    'last_updated', 'uuid',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.36', 'msg'=>'Unable to load items', 'err'=>$rc['err']));
        }
        $rsp['items'] = isset($rc['items']) ? $rc['items'] : array();

        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'loadThumbnail');
        foreach($rsp['items'] as $iid => $item) {
            $item_ids[] = $item['id'];
            $rsp['items'][$iid]['resource'] = '';
            if( $item['restype'] == 10 ) {
                $rsp['items'][$iid]['resource'] = $item['org_filename'];
            } else {
                $rsp['items'][$iid]['resource'] = $item['url'];
            }
            if( isset($item['thumbnail_image_id']) && $item['thumbnail_image_id'] > 0 ) {
                $rc = ciniki_images_hooks_loadThumbnail($ciniki, $args['tnid'], 
                    array('image_id'=>$item['thumbnail_image_id'], 'maxlength'=>75, 'last_updated'=>$item['last_updated'], 'uuid'=>$item['uuid']));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $rsp['items'][$iid]['image'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
            }
        }
    }

    return $rsp;
}
?>
