<?php
/*
  we read the access logs only.

  message blocks start with a timestamp (e.g., "^2013-11-26 13:49:53") 
  and can be in multiple lines (embedded \r or \n in SMS).  We just
  read all lines until the next timestamp.  that next timestamp starts
  the next message block.

  MTs are: 2013-11-26 13:51:16 send-SMS request added - sender:lobster:333 192.168.250.165 target:0279675352 request: 'Hi this is Telecom. To access your account details click here: http://m.telecom.co.nz/yt'

  MOs are: 2013-11-26 13:50:47 SMS HTTP-request sender:0276839900 request: 'You are now unsubscribed from all data usage alerts. Txt START to 172 to start receiving usage alerts^M
  ' url: 'http://192.168.250.163:8084/usagealerts/mo?smsc=MAS&to=172&from=0276839900&message=stop' reply: 200 '<< successful >>'

  So we walk through the files finding message blocks, then smash the message blocks into one line and extract the date, to and from.

  currently output is just to stdout. we ARE now using fputcsv though, so can now output the text message (fputcsv avoids escaping headaches)
*/

  array_shift($argv);

  if(0 < count($argv)) {
    main($argv);
  } else {
    $arr=array("access/ct1-access.log-131127");
    main($arr);
  }

  function main($a) {
    $fn=$a[0];

    $in=fopen($fn,"r");
    $out=fopen("php://stdout","a");

    $curr="";
    $first=true;

    $ctr=0;
    while(!feof($in)) {

      $line=fgets($in);

      $prev=$curr;

      $line=str_replace("\n"," ",$line);
      $line=str_replace("\r"," ",$line);

      if(is_start($line) || feof($in)) {

        if(!empty($curr)) {
          $summary=extract_summary($curr);
          fputcsv($out, $summary);
          $ctr++;
        }

        $curr = $line;
      } else {
        $curr.=$line;
      }

      $first=false;
    }
  }

  function is_start($line) {
    $re="/^[\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2}/";

    $ans=preg_match($re, $line);
    return $ans;
  }

  function extract_summary($curr) {
    $arr=array();
    if(preg_match("/(.*) SMS HTTP-request.*sender:([0-9]+).*url:.*to=([0-9]+)/",$curr, $arr)) {

      list($all,$dt, $sender, $to) = $arr;
      return array("MO",$dt, $to, $sender,$all);
    } else if (preg_match("/(.*) send-SMS request added.*sender:.*:([0-9]+).*target:([0-9]+)/",$curr, $arr)) {
      list($all, $dt, $sender, $to) = $arr;
      return array("MT",$dt,$sender,$to,$all);
    }

    return array("UNKNOWN","","","","$curr");

  }



?>
