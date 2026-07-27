<?php
//
// Description
// -----------
// This function will load an item and update the keywords field
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_reslib_itemKeywordsUpdate(&$ciniki, $tnid, $item_id) {

    //
    // Load the item
    //
    $strsql = "SELECT items.id, "
        . "IFNULL(sections.name, '') AS section_name, "
        . "IFNULL(categories.name, '') AS category_name, "
        . "IFNULL(subcategories.name, '') AS subcategory_name, "
        . "items.name, "
        . "items.synopsis, "
        . "items.description, "
        . "items.additional_keywords, "
        . "items.keywords "
        . "FROM ciniki_reslib_items AS items "
        . "LEFT JOIN ciniki_reslib_subcategories AS subcategories ON ("
            . "items.subcategory_id = subcategories.id "
            . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_reslib_categories AS categories ON ("
            . "subcategories.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_reslib_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE items.id = '" . ciniki_core_dbQuote($ciniki, $item_id) . "' "
        . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.reslib', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.56', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.57', 'msg'=>'Unable to find requested item'));
    }
    $item = $rc['item'];

    //
    // Make keywords
    //
    $str = $item['section_name'] 
        . ' ' . $item['category_name']
        . ' ' . $item['subcategory_name']
        . ' ' . $item['name']
        . ' ' . $item['synopsis']
        . ' ' . $item['description']
        . ' ' . $item['additional_keywords']
        . '';
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makeKeywords');
    $keywords = ciniki_core_makeKeywords($ciniki, $str);

    if( $keywords != $item['keywords'] ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.reslib.item', $item['id'], array(
            'keywords' => $keywords,
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.58', 'msg'=>'Unable to update the item', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>
