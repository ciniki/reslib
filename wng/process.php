<?php
//
// Description
// -----------
// This function will return the blocks for the website.
// 
//
function ciniki_reslib_wng_process(&$ciniki, $tnid, &$request, $section) {

    //
    // Check to make sure module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.reslib']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.7', 'msg'=>'Module not enabled'));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reslib.8', 'msg'=>'No section specified.'));
    }

    if( $section['ref'] == 'ciniki.reslib.xxx' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'wng', 'xxxProcess');
        return ciniki_reslib_wng_xxxProcess($ciniki, $tnid, $request, $section);
    }

    return array('stat'=>'ok');
}
?>
