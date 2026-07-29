<?php
//
// Description
// -----------
// This function will return the sections available for this module
// 
//
function ciniki_reslib_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.reslib']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.6', 'msg'=>'Module not enabled'));
    }

    //
    // Get the list of sections
    //
    $strsql = "SELECT sections.id, "
        . "sections.name "
        . "FROM ciniki_reslib_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reslib', array(
        array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.11', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
    }
    $sectionlist = isset($rc['sections']) ? $rc['sections'] : array();

    $sections = [];
    if( count($sectionlist) > 0 ) {
        $sections['ciniki.reslib.section'] = [
            'name' => 'Section',
            'module' => 'Resource Library',
            'settings' => [
                'title' => ['label'=>'Title', 'type'=>'text'],
                'content' => ['label'=>'Intro', 'type'=>'htmlarea'],
                'section-id' => ['label'=>'Section', 'type'=>'select', 
                    'complex_options' => ['value'=>'id', 'name'=>'name'],
                    'options' => $sectionlist,
                    ],
                'section-layout' => ['label'=>'Format', 'type'=>'select', 'options'=>[
                    'flexcards' => 'Flex Cards',
                    'tradingcards' => 'Trading Cards',
                    ]],
                'section-subcatbuttons' => ['label'=>'Card Subcategory Buttons', 'type'=>'toggle', 'default'=>'no', 'toggles'=>[
                    'no' => 'No',
                    'yes' => 'Yes',
                    ]],
                'subcat-image-ratio' => array('label' => 'Thumbnail Image Ratio', 
                    'type'=>'select', 
                    'default'=>'1-1', 
                    'options'=>array(
                        '2-1' => 'Panoramic',
                        '16-9' => 'Letterbox',
                        '6-4' => 'Wider',
                        '4-3' => 'Wide',
                        '1-1' => 'Square',
                        '3-4' => 'Tall',
                        '4-6' => 'Taller',
                    )),
                ],
            ];
/*        $sections['ciniki.reslib.category'] = [
            'name' => 'Category',
            'module' => 'Resource Library',
            'settings' => [
                'title' => ['label'=>'Title', 'type'=>'text'],
                'content' => ['label'=>'Intro', 'type'=>'htmlarea'],
                'category-id' => ['label'=>'Section', 'type'=>'select', 
                    'complex_options' => ['value'=>'id', 'name'=>'name'],
                    'options' => $categorylist,
                    ],
                ],
            ]; */
    }


    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
