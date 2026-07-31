<?php
//
// Description
// -----------
// This function will process api requests for wng.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get sapos request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_reslib_wng_api(&$ciniki, $tnid, &$request) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.reslib']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.reslib.91', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // saveSubmission - Save the form submission
    //
    if( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'search' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'wng', 'apiSearch');
        return ciniki_reslib_wng_apiSearch($ciniki, $tnid, $request);
    }

    return array('stat'=>'ok');
}
?>
