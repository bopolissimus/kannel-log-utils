<?php

  /*
    grab the pieces of the log line.
  */
  function get_log_entries($line) {
    $re="/^([-0-9]+) ([:0-9]+) \[([0-9]+)\] \[([0-9])+\] (.*)$/";
    $matches=array();

    if(1==preg_match($re, $line, $matches)) {
      return $matches;
    } else {
      return array();
    }
  }

  function get_pdu_type($arr) {
    foreach($arr as $line) {
      $pcs = explode(":", $line);
      $len=count($pcs);
      $fld = $pcs[$len-2];
      $fld=trim($fld);

      $val=$pcs[$len -1];
      $val=trim($val);

      if($fld == 'type_name')
        return $val;
    }
  }

  function is_pdu_start($line) {
    $re="/.* SMPP PDU (0x){0,1}[a-f0-9]+ dump:/";

    $re="/.*SMPP[^:]+:.*(Sending|Got).*:/";

    return preg_match($re, $line);
  }

  function is_pdu_end($line) {
    $re="/(.*) SMPP PDU dump ends./";
    return preg_match($re, $line);
  }

  /**
    returns an array of {0=>type, 1=>tstamp} or false if not a DLR 
  **/
  function get_dlr_info($line) {
    $re="/.*DEBUG: DLR\[[a-zA-Z0-9]+\]: (Adding DLR|Looking for DLR).*ts=([0-9]+)/";

    $matches=array();
    if(preg_match($re, $line, $matches)){

      if($matches[1]=="Adding DLR") {
        $type='dlr_save';
      } else {
        $type='dlr_send';
      }

      return array($type, $matches[2]);
    } else {
      return false;
    }
  }


  function has_exclude_text($line) {
    return false;
    global $EXCLUDE_PDU_TEXT;

    $exclude=$EXCLUDE_PDU_TEXT;
    foreach($exclude as $ex) {
      if(strstr($line, $ex)) {
        return true;
      }
    }

    return false;
  }

  function debug($line) {
    if(DEBUG) {
      print("$line\n");
    }
  }

  function get_pdu_seqno($arr) {
    $re="/.*(sequence_number: [0-9]+.*$)/";
    foreach($arr as $line) {
      $matches=array();
      if(preg_match($re, $line, $matches)) {
        return $matches[1];
      }
    }
   
    print ("ERROR: sequence_number not found in array\n");
    print_r($arr);
    die();
  }

  /** reads a bearerbox file that's already
      been passed through buntangle so it's much
      cleaner.  returns pdus one at a time. **/
  function load_pdu($in) {
    $pdu=array();
    $line="";

    while(!feof($in)) {
      $line=fgets($in);
      $line=trim($line);
      if(!empty($line)) {
        break;
      }
    }

    if(empty($line)) {
      return array();
    }

    while(!empty($line)) {
      array_push($pdu, $line);
      $line=fgets($in);
      $line=trim($line);
    }
  
    return $pdu;
  }

  /**
    get a field value the naive way.  
    type_name: deliver_sm returns delivery_sm
    command_id: 5 = 0x00000005 returns 5
    receipted_message_id: "17391000167162" 
       returns 17391000167162 as a string without 
       the quotes inside the string.
    data: ... returns the concatenation of all the
       *text* (we ignore the hex).

    for future, timestamps (schedule_delivery_time,
    validity_period) will be converted to UTC.
    For now though, they're just returned as is.

    if no regexp matches, returns false.

  **/
  function get_field_value($line, $field) {
    $re1="/.*$field: ([0-9]+) = [0-9xabcdef]+$/";
    $re2="/.*$field: ".'"([^'.'"]+)"$/';
    $re3="/.*$field: ([^ ]+)$/";

    // data short messages also have short_message: but with
    // no text after the ": ".  so, as intended, this won't catch those.
    $re4="/.*data:(([0-9a-f ]+ )+) .*/";
    $re5='/.*short_message:[^"]+"([^"]+)"$/';

    $matches=array();
    $rea=array($re1, $re2, $re3);

    foreach($rea as $re) {
      if(preg_match($re, $line, $matches)) {
        return $matches[1];
      } 
    }

    // the short message is either in a data: or
    // short_message: field.  if short_message, it's
    // only one line and we just use the text.  if data,
    // it's hex, we decode it.
    if($field=='data') {
      $ret="";

      $matches=array();
      if(preg_match($re4, $line, $matches)) {
        $hex=trim($matches[2]);
        $hexa=split(' ',$hex);
      
        // best effort convert to ascii.  we don't actually try to
        // detect or convert encoded text here.  just filter and
        // replace non isprint.
        foreach($hexa as $chx) {
          $decimal=hexdec($chx);
          if($decimal >=127 || $decimal<31 ) {
            $decimal=42;
          }

            $a=chr($decimal);
            $ret.=$a;
        }

        return $ret;
      } else {
        if(preg_match($re5, $line, $matches)) {
          return $matches[1];
        }
      }
  }
    return false;
  }

  /**
    get the value of a field from a pdu.  returns
    false if no such field.
  **/
  function get_pdu_field($pdu, $field) {
    $data="";
    foreach($pdu as $line) {
      $v = get_field_value($line, $field);
      if($v && $field!='data') {
        return $v;
      } else {
        $v=addslashes($v);
        $data.=$v;
      }
    }

    return $data;
  }

  /**
    $fielda is a comma delimited string of fieldnames. 
    @return an array of the given fields.  if a field
      specified does not exist false is returned and
      an error message printed out to stdout.
  **/
  function get_pdu_fields($pdu, $field_list) {
    $fielda=explode(',', $field_list);
    $ret=array();
    foreach($fielda as $f) {
      $f=trim($f);

      if(strstr($f,'=')) {
        list($from,$to)=explode('=', $f);
      } else {
        $from=$f;
        $to=$f;
      }

      $ret[$to]=get_pdu_field($pdu, $from);
    }

    return $ret;
  }

  function get_pdu_tstamp($pdu) {
    $keys = array_keys($pdu);
    $key = $keys[0];

    $row=$pdu[$key];
    $tstamp=strstr($row,'[',true);
    $tstamp=trim($tstamp);
    return $tstamp;
  }

  /**
    return an array with submit_sm data or false if not
    a submit_sm.
  **/
  function get_submit_sm($pdu) {
    $type=get_pdu_type($pdu);

    if($type!= 'submit_sm') {
      return false;
    } else {
      $ret=get_pdu_fields($pdu, "type_name,command_status,source_addr=from,destination_addr=to,sequence_number,data=msg,schedule_delivery_time,validity_period,registered_delivery");

      $ret['tstamp']=get_pdu_tstamp($pdu);

    return $ret;
    }
  }

  /**
    return an array with relevant submit_sm_resp data
    or false if not a submit_sm_resp
  **/
  function get_submit_sm_resp($pdu) {
    $ret=get_pdu_fields($pdu, "type_name,command_status,sequence_number,message_id");

    $ret['tstamp']=get_pdu_tstamp($pdu);

    return $ret;
  }

  function get_deliver_sm($pdu) {
    $ret=get_pdu_fields($pdu, "type_name,command_status,source_addr=from,destination_addr=to,sequence_number,data=msg,schedule_delivery_time,validity_period,receipted_message_id");
    
    $ret['tstamp']=get_pdu_tstamp($pdu);
    return $ret;
  }

  function get_deliver_sm_resp($pdu) {
    $ret=get_pdu_fields($pdu, "type_name,command_status,sequence_number,message_id");

    return $ret;
  }


?>
