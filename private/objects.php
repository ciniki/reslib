<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_reslib_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();
    $objects['section'] = array(
        'name' => 'Section',
        'sync' => 'yes',
        'o_name' => 'section',
        'o_container' => 'sections',
        'table' => 'ciniki_reslib_sections',
        'fields' => array(
            'name' => array('name'=>'Name'),
            'permalink' => array('name'=>'Permalink', 'default'=>''),
            'image_id' => array('name'=>'Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'sequence' => array('name'=>'Order', 'default'=>''),
            'synopsis' => array('name'=>'Synopsis', 'default'=>''),
            'description' => array('name'=>'Description', 'default'=>''),
            'customer_perms' => array('name'=>'Customer Permissions', 'default'=>''),
            ),
        'history_table' => 'ciniki_reslib_history',
        );
    $objects['category'] = array(
        'name' => 'Category',
        'sync' => 'yes',
        'o_name' => 'categorie',
        'o_container' => 'categories',
        'table' => 'ciniki_reslib_categories',
        'fields' => array(
            'section_id' => array('name'=>'Section', 'ref'=>'ciniki.reslib.section'),
            'name' => array('name'=>'Name'),
            'permalink' => array('name'=>'Permalink', 'default'=>''),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'sequence' => array('name'=>'Order', 'default'=>''),
            'image_id' => array('name'=>'Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'synopsis' => array('name'=>'Synopsis', 'default'=>''),
            'description' => array('name'=>'Description', 'default'=>''),
            'customer_perms' => array('name'=>'Customer Permissions', 'default'=>''),
            ),
        'history_table' => 'ciniki_reslib_history',
        );
    $objects['subcategory'] = array(
        'name' => 'Subcategory',
        'sync' => 'yes',
        'o_name' => 'subcategory',
        'o_container' => 'subcategories',
        'table' => 'ciniki_reslib_subcategories',
        'fields' => array(
            'category_id' => array('name'=>'Category', 'ref'=>'ciniki.reslib.category'),
            'name' => array('name'=>'Name'),
            'permalink' => array('name'=>'Permalink', 'default'=>''),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'sequence' => array('name'=>'Order', 'default'=>''),
            'image_id' => array('name'=>'Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'synopsis' => array('name'=>'Synopsis', 'default'=>''),
            'description' => array('name'=>'Description', 'default'=>''),
            'customer_perms' => array('name'=>'Customer Permissions', 'default'=>''),
            ),
        'history_table' => 'ciniki_reslib_history',
        );
    $objects['item'] = array(
        'name' => 'Item',
        'sync' => 'yes',
        'o_name' => 'item',
        'o_container' => 'items',
        'table' => 'ciniki_reslib_items',
        'fields' => array(
            'subcategory_id' => array('name'=>'Subcategory', 'ref'=>'ciniki.reslib.subcategory'),
            'name' => array('name'=>'Name'),
            'permalink' => array('name'=>'Permalink', 'default'=>''),
            'restype' => array('name'=>'Item Type', 'default'=>''),
            'url' => array('name'=>'Url', 'default'=>''),
            'org_filename' => array('name'=>'Filename', 'default'=>''),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'sequence' => array('name'=>'Order', 'default'=>''),
            'thumbnail_image_id' => array('name'=>'Thumbnail', 'ref'=>'ciniki.images.image_id', 'default'=>'0'),
            'synopsis' => array('name'=>'Synopsis', 'default'=>''),
            'description' => array('name'=>'Description', 'default'=>''),
            'additional_keywords' => array('name'=>'Additional Keywords', 'default'=>''),
            'keywords' => array('name'=>'Keywords', 'default'=>''),
            ),
        'history_table' => 'ciniki_reslib_history',
        );
    //
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
