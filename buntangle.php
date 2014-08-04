<?php
  /*
    read the bearerbox log file and organize by 3rd log field.

    generate PDUs (including single line virtual PDUs) in memory and 
    output the re-created PDUs without the nesting and interleaving 
    problems that live log files have.

    "PDU" arrays with only one element are single line.  any array
    with more than one element is a proper PDU.
  */
 
  define("DEBUG",false);

  // set true if you want to keep "PDUs" that have no type_name (they're
  // mostly virtual PDUs created by buntangle.
  define("KEEP_NO_TYPE",false);

  if(file_exists("exclude_pdu.php")) {
    include_once "exclude_pdu.php";
  } else {
    // if you want to define your own exclude list, create
    // exclude_pdu.php and define EXCLUDE_PDU_LIST as a comma
    // separated string of pdus to exclude
    define("EXCLUDE_PDU_LIST","");
    }

  if(!isset($argv[1])) {
    die("Missing bearerbox.log\n");
    
  }

  $filename=$argv[1];

  $pdu_line_debug=false;
  if($filename=='-d') {
    $filename=$argv[2];
    $pdu_line_debug=true;
  }

  if(empty($filename) || !is_readable($filename)) {
    print "USAGE: $argv[0] [-d] [path_to/]bearerbox.log\n";
    print "           -d is optional and will print debug start line numbers \n";
    print "           for PDUs\n";
    print "           path to the bearerbox is required\n";
  }

  load_pdus($filename, $pdu_line_debug);

  function load_pdus($filename, $pdu_line_debug) {
    // assoc array key is the $key from the log entry.  
    // entries are assoc arrays whose key is line number and value is the whole log line itself.
    $per_k=array();

    $in=fopen($filename,"r");
    $lno=0;
    while(!feof($in)) {
      $lno++;
      $line=fgets($in);
      $line=trim($line);
      if(empty($line)) {
        continue;
      }

      // we also need current_per_k pdu being built.

      $entries = get_log_entries($line);
      if(count($entries) == 6) {
        list($whole,$dt, $tm, $xx, $key, $log_msg) = $entries;

        if(!array_key_exists($key, $per_k)) {
          $per_k[$key] = array();
        }

        $per_k[$key][$lno]=$whole;

        //print ("$dt : $tm : $xx : $key : $log_msg\n");
      } else {
        print("NO MATCH: $line\n");
      }
    }

    // still organized by key
    $pdus=to_pdu($per_k);

    ksort($pdus);

    foreach($pdus as $k=>$arr) {

      $type=get_pdu_type($arr);
      if(!is_string($type)) {
        $type="no_type";
      }

      if(strstr(EXCLUDE_PDU_LIST, $type)) {
        continue;
      }

      if($type=="no_type" && !KEEP_NO_TYPE) {
        continue;
      }

      // no lead/trail NL if single line
      if(count($arr)> 1) {
        print("\n");
      }

      $first=true;
      foreach($arr as $line) {
        if($first && $pdu_line_debug) {
          print ("$k : $line\n");
          $first=false;
        } else {
          print("$line\n");
        }
      }

      if(count($arr)> 1) {
        print("\n");
      }
    }
      
  }


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

  /*
    for each array entry (per key) in the input array, read through
    the array and output an array of PDUs.  "PDUs" are just arrays.
    single-line (non-pdu) log entries will be coerced into single
    entry "PDU" arrays.
  
    @param $per_k -- the per_k array
    @param $use_last -- which line number to use for ordering.
       if true, we use the last line number in the pdu, else we
       use the first.  i.e., determine if interleaved or nested
       entries go before or after the earlier/enclosing PDU.

       by default interleaved or nested entries go AFTER.
  */
  function to_pdu($per_k,$use_last=true) {
    $all_pdus=array();
    $pdu_per_k=array();

    foreach($per_k as $k=>$arr) {
      $pdu_arr=array();
      $current=array();

      foreach($arr as $lno=>$line) {
        if(is_pdu_start($line)) {
          $start=$lno;
          array_push($current, $line);
          continue;
        }

        if(is_pdu_end($line)) {
          array_push($current,$line);
          $pdu_arr[$start]=$current;
          $current=array();

          insert_pdu_all($pdu_arr[$start],$start,$all_pdus);
          continue;
        }

        // part of current pdu
        if(count($current) > 0){
          array_push($current, $line);
          continue;
        } else {
          $pdu_arr[$lno]=array($line);
          insert_pdu_all($pdu_arr[$lno], $lno, $all_pdus);
        }
      }
      $pdu_per_k[$k]=$pdu_arr;
    }

    return $all_pdus;
  }

  function insert_pdu_all($arr, $lno, &$all_pdus) {
    if(array_key_exists($lno, $all_pdus)) {
      print("ERROR: $lno already exists in all_pdus\n");
      print_r($arr);
      die;
    }

    $all_pdus[$lno]=$arr;
  }

  /*
    a PDU is just an assoc array with key being the line number.
    this function takes a "PDU" array and returns the line number
    that identifies this PDU (first or last).

    NOTE: there's an issue with nested PDUs if use_last, inner 
    goes before outer.  if use_first inner goes after outer.

    When this fn is called we don't know if we were inner or outer,
    thus the confusion.  I think it doesn't matter though, whether
    the inner comes before or after the outer.
  */
  function get_pdu_lno($arr, $use_last) {
    $keys=array_keys(arr);

    // paranoia.  should't be necessary.
    asort($keys);

    if($use_last) {
      return $keys[count($keys)-1];
    } else {
      return $keys[0];
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

    return preg_match($re, $line);
  }


  function is_pdu_end($line) {
    $re="/(.*) SMPP PDU dump ends./";
    return preg_match($re, $line);
  }

  function debug($line) {
    if(DEBUG) {
      print("$line\n");
    }
  }

?>
