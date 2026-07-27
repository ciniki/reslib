<?php
//
// Description
// -----------
// This method searchs for a Items for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Item for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_reslib_itemSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'private', 'checkAccess');
    $rc = ciniki_reslib_checkAccess($ciniki, $args['tnid'], 'ciniki.reslib.itemSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makeKeywords');
    $words = ciniki_core_makeKeywords($ciniki, $args['start_needle'], true);
    $words = preg_replace("/ /", '%', $words);

    //
    // Get the list of items
    //
    $strsql = "SELECT items.id, "
        . "items.subcategory_id, "
        . "items.name, "
        . "items.permalink, "
        . "items.restype, "
        . "items.url, "
        . "items.org_filename, "
        . "items.flags, "
        . "items.sequence, "
        . "items.thumbnail_image_id, "
        . "IFNULL(UNIX_TIMESTAMP(images.last_updated), 0) AS last_updated, "
        . "IFNULL(images.uuid, '') AS uuid "
        . "FROM ciniki_reslib_items AS items "
        . "LEFT JOIN ciniki_images AS images ON ("
            . "items.thumbnail_image_id = images.id "
            . "AND images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    foreach($words as $word) {
        $strsql .= "AND ("
            . "items.keywords LIKE '" . ciniki_core_dbQuote($ciniki, $word) . "%' "
            . "OR items.keywords LIKE '% " . ciniki_core_dbQuote($ciniki, $word) . "%' "
            . ") ";
    }
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'items', 'fname'=>'id', 
            'fields'=>array('id', 'subcategory_id', 'name', 'permalink', 'restype', 'url', 'org_filename', 'flags', 'sequence', 
                'thumbnail_image_id', 'last_updated', 'uuid',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $items = isset($rc['items']) ? $rc['items'] : array();
    $item_ids = array();
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'hooks', 'loadThumbnail');
    foreach($items as $iid => $item) {
        $item_ids[] = $item['id'];
        $items[$iid]['resource'] = '';
        if( $item['restype'] == 10 ) {
            $items[$iid]['resource'] = $item['org_filename'];
        } else {
            $items[$iid]['resource'] = $item['url'];
        }
        if( isset($item['thumbnail_image_id']) && $item['thumbnail_image_id'] > 0 ) {
            $rc = ciniki_images_hooks_loadThumbnail($ciniki, $args['tnid'], 
                array('image_id'=>$item['thumbnail_image_id'], 'maxlength'=>75, 'last_updated'=>$item['last_updated'], 'uuid'=>$item['uuid']));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $items[$iid]['image'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
        }
    }

    return array('stat'=>'ok', 'items'=>$items, 'nplist'=>$item_ids);
}
?>
