<?php
  /*
    read the bearerbox log file and organize by 3rd log field.

    generate PDUs (including single line virtual PDUs) in memory and 
    output the re-created PDUs without the nesting and interleaving 
    problems that live log files have.

    "PDU" arrays with only one element are single line.  any array
    with more than one element is a proper PDU.
  */
 
  include_once "buntangle_lib.php";

  if(file_exists("customize.php")) {
    include_once "customize.php";
  } else {
    // if you want to define your own exclude list, create
    // exclude_pdu.php and define EXCLUDE_PDU_LIST as a comma
    // separated string of pdus to exclude
    define("EXCLUDE_PDU_LIST","");
  }

  if(!defined("DEBUG")) {
    define("DEBUG", false);
  }

  if(!defined("DLR_VIRTUAL_PDU")) {
    /**
      do we want to highlight DLR sql statements?  Only inserts and deletes
      handled.
    */
    define("DLR_VIRTUAL_PDU", true);
  }

  if(!defined("KEEP_NO_TYPE")) {
    // set true if you want to keep "PDUs" that have no type_name (they're
    // mostly virtual PDUs created by buntangle.
    define("KEEP_NO_TYPE",true);
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

  print("\n");
  clean_pdus($filename);

  /**
    get all the lines, and then, all the starts.
    for each start, go to where it is in the all lines array and
    walk forward until we see its end, accumulating lines in this
    pdu.  

    when we see its end, if this is a type we want to keep, print
    it out.  otherwise, skip it.  then go to the next pdu start
    and do the same thing.
  */
  function clean_pdus ($filename) {

    $all_lines = get_line_array($filename);
    $all_start = get_all_pdu_starts($all_lines);

    foreach( $all_start as $lno=>$k ) {
      $pdu = array();

      while(true) {
        $line = $all_lines[$lno];
        $start_entries = $all_lines[$lno];
        list($whole,$dt, $tm, $xx, $key, $log_msg) = $start_entries;

        if($key == $k) {
          array_push($pdu, $whole);

          if(has_exclude_text($whole)) {
            break;
          }

          if(is_pdu_end($whole)) {
            $type=get_pdu_type($pdu);

            if(!is_string($type)) {
              $type="no_type";
            }

            if(strstr(EXCLUDE_PDU_LIST, $type)) {
              break;
            }

            if($type=="no_type" && !KEEP_NO_TYPE) {
              break;
            }

            foreach($pdu as $line) {
              print("$line\n");
            }

            if(count($pdu) > 0) {
              print("\n\n");
            }

            break;
          } else {

            // DLR lines are virtual PDUs
            $dlra = get_dlr_info($whole);

            $prefix=strstr($whole, "DEBUG:", true)."DEBUG:";

            if(is_array($dlra)) {
              list($type, $tstamp) = $dlra;

              print ("$whole\n");
              print("$prefix type_name: $type\n");
              print("$prefix receipted_message_id: $tstamp\n\n\n");
              break;
            }

          }
        }

        $lno++;
      }
    }
  }

  // return an array.  the key is the line number of the start.  the value is the key
  // of the start, for faster matching of following lines.
  function get_all_pdu_starts($all_lines) {
    $ret = array();
    foreach($all_lines as $lno => $entries) {
      list($whole,$dt, $tm, $xx, $key, $log_msg) = $entries;

      $dlri = get_dlr_info($whole);
      $isdlr = is_array($dlri) && 2==count($dlri);

      if(is_pdu_start($whole) || $isdlr ) {
        $ret[$lno] = $key;
      }
    }

    return $ret;
  }

  /**
    get all lines in the file which are log lines we're interested in.
    WHINE about lines we don't care about.
  **/
  function get_line_array($filename) {
    $ret = array();

    $in=fopen($filename,"r");
    $lno=0;
    while(!feof($in)) {
      $lno++;
      $line=fgets($in);
      $line=trim($line);
      if(empty($line)) {
        continue;
      }

      $entries = get_log_entries($line);

      if(count($entries) == 6) {
        list($whole,$dt, $tm, $xx, $key, $log_msg) = $entries;

        $ret[$lno]=$entries;
      } else {
        print("NO MATCH: $line\n");
      }
    }

    return $ret;
  }

?>
