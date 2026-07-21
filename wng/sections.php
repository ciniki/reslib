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

    $sections = array();

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
