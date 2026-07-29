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

    if( $section['ref'] == 'ciniki.reslib.section' ) {
        $section['settings']['base_url'] = $request['ssl_domain_base_url'] . $request['page']['path'];
        if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
            && $request['uri_split'][($request['cur_uri_pos']+1)] != ''
            ) {
            $request['cur_uri_pos']++;
            $section['settings']['category-permalink'] = $request['uri_split'][$request['cur_uri_pos']];
            $section['settings']['base_url'] .= '/' . $section['settings']['category-permalink'];
            if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
                && $request['uri_split'][($request['cur_uri_pos']+1)] != ''
                ) {
                $request['cur_uri_pos']++;
                $section['settings']['subcategory-permalink'] = $request['uri_split'][$request['cur_uri_pos']];
                $section['settings']['base_url'] .= '/' . $section['settings']['subcategory-permalink'];
                if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
                    && $request['uri_split'][($request['cur_uri_pos']+1)] != ''
                    ) {
                    $request['cur_uri_pos']++;
                    $section['settings']['item-permalink'] = $request['uri_split'][$request['cur_uri_pos']];
                    $section['settings']['base_url'] .= '/' . $section['settings']['item-permalink'];
                }
            }
        }
        if( isset($section['settings']['item-permalink']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'wng', 'itemProcess');
            return ciniki_reslib_wng_itemProcess($ciniki, $tnid, $request, $section);
        } elseif( isset($section['settings']['subcategory-permalink']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'wng', 'subcategoryProcess');
            return ciniki_reslib_wng_subcategoryProcess($ciniki, $tnid, $request, $section);
        } elseif( isset($section['settings']['category-permalink']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'wng', 'categoryProcess');
            return ciniki_reslib_wng_categoryProcess($ciniki, $tnid, $request, $section);
        } else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'wng', 'sectionProcess');
            return ciniki_reslib_wng_sectionProcess($ciniki, $tnid, $request, $section);
        }
//    } elseif( $section['ref'] == 'ciniki.reslib.category' ) {
//        ciniki_core_loadMethod($ciniki, 'ciniki', 'reslib', 'wng', 'categoryProcess');
//        return ciniki_reslib_wng_categoryProcess($ciniki, $tnid, $request, $section);
    }

    return array('stat'=>'ok');
}
?>
