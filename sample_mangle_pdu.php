<?php
  

  function mangle_pdu($arr) {
    return mangle_skip_on_keywords($arr);
  }

  /**
    an example function that can be used to skip submit_sm and
    corresponding submit_sm_resps if the keywords we specify
    exist.  Just call this function inside mangle_pdu.

    This assumes that the resp always comes after the submit_sm
  **/
  function mangle_skip_on_keywords ($arr) {
    $skip_array=array("Ignore this SMS","This too", "And also this");

    static $sequence_numbers=array();

    $type = get_pdu_type($arr);

    if($type == 'submit_sm') {
      $seqno=get_pdu_seqno($arr);

      foreach($skip_array as $needle) {
        foreach($arr as $haystack) {
          if(strstr($haystack, $needle)) {
            $sequence_numbers[$seqno] = $seqno;
            return array();
          }
        }
      }
    }
    
    if($type == 'submit_sm_resp') {
      $seqno=get_pdu_seqno($arr);

      if(array_key_exists($seqno, $sequence_numbers)) {
        unset($sequence_numbers[$seqno]);
        return array();
      }
    }

    return $arr;
  }


?>
