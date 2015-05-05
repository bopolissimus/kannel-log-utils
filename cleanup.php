<?php
  /**
    Read an untangled log file and remove submit_sms (and their submit_sm_resps) which
    we don't care about.
  **/

  include_once "buntangle_lib.php";

  if(!file_exists("mangle_pdu.php")) {
    function mangle_pdu($arr) {
      return $arr;
    }
  }

  /**
    define a function mangle_pdu($array).  It should return an array (possibly empty)
    with the array mangled as necessary. If just deleting the pdu, return an empty array.
    if actually changing the pdu, return an array with PDU lines (doesn't need to be a
    real PDU, could just as well be random text).
  **/
  include "mangle_pdu.php";

  if( 1 == count($argv) ) {
    print("USAGE: $argv[0] INPUTFILE\n");
    die;
  }

  cleanup_and_overwrite($argv[1]);

  function cleanup_and_overwrite($filename) {
    $dir=pathinfo($filename, PATHINFO_DIRNAME);
    $fn = pathinfo($filename, PATHINFO_BASENAME);

    $in=fopen("$filename","r");

    $tmpfn=tempnam($dir, "");
    $out = fopen($tmpfn, "a+");

    while(!feof($in)) {
      $arr=load_pdu($in);
      $arr=mangle_pdu($arr);
      if(!empty($arr)) {
        foreach($arr as $line) {
          fwrite($out,"$line\n");
        }

        fwrite($out, "\n\n");
      }
    }

    fclose($in);
    fclose($out);
    unlink($filename);

    rename($tmpfn, $filename);
  }


?>
