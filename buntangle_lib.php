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

  function is_sql_line($line) {
    if(SQL_SINGLE_PDU) {
      $re="/.*DEBUG: sql: (INSERT|DELETE)/";

     return preg_match($re, $line);
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

?>
