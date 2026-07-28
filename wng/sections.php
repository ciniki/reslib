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
                'layout' => ['label'=>'Format', 'type'=>'select', 'options'=>[
                    'tradingcards' => 'Trading Cards',
                    'tradingcards-subcatbuttons' => 'Trading Cards with Subcategory Buttons',
                    ]],
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
