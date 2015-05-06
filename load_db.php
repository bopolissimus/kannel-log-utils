<?php
  include "buntangle_lib.php";

  $conn=pg_connect("host=db  user=kannel_dlr");

  if(1 == count($argv)) {
    print("Missing input filename\n");
    die;
  }

  if(!file_exists($argv[1])) {
    print("No such file $argv[1].  Exiting.\n");
    die;
  }

  $in=fopen($argv[1],"r");

  while(!feof($in)) {
    $pdu = load_pdu($in);
    if(is_array($pdu) && count($pdu)>0) {
      $type=get_pdu_type($pdu);

      unset($sql);
      unset ($sql2);

      switch($type) {
        case "submit_sm":
          $fields=get_submit_sm($pdu);
          extract ($fields);
          if($registered_delivery=='1') {
            $registered_delivery='true';
          } else {
            $registered_delivery='false';
          }

          $msg=pg_escape_literal($msg);

          $sql = "insert into mt (tstamp,status,seqno,has_dlr,msgid,src,dest,msg) values ('$tstamp','$command_status',$sequence_number,$registered_delivery,null,'$from','$to',$msg)";
          break;

        case "submit_sm_resp":
          $fields = get_submit_sm_resp($pdu);
          extract ($fields);
          $sql = "update mt set status='$command_status',msgid='$message_id' where seqno=$sequence_number";
          break;

        case "deliver_sm":
          $fields = get_deliver_sm($pdu);
          extract ($fields);
          
          $re="/stat:([A-Z]+)/";

          $dlr_status="";
          $matches=array();
          if(preg_match($re, $msg, $matches)) {
            $dlr_status=$matches[1];
          }

          if(isset($receipted_message_id)) {
            $receipted_message_id="'$receipted_message_id'";
            $sql2="update mt set dlr_status='$dlr_status' where msgid = $receipted_message_id";
          } else {
            $receipted_message_id='null';
          }

          $msg=pg_escape_literal($msg);

          $sql= "insert into mo (tstamp,status,seqno,dlr_msgid,src,dest,msg) values ('$tstamp','$command_status','$sequence_number',$receipted_message_id,'$from','$to',$msg)";
          break;

        case "deliver_sm_resp":
          $fields = get_deliver_sm_resp($pdu);
          extract ($fields);
          break;

        /* NOTE: this depends on the dlr_save and dlr_end messages appearing AFTER the submit_sm_resp.
           If this is a problem (sometimes the resp is delayed and the DLR is processed before the resp
           then we might do these as post processing instead. */
        case 'dlr_save':
          $msgid=get_pdu_field($pdu, 'receipted_message_id');
          $sql = "update mt set dlr_saved=true where msgid='$msgid'";
          break;

        case 'dlr_send':
          $msgid=get_pdu_field($pdu, 'receipted_message_id');
          $tstamp=get_pdu_tstamp($pdu);
          $sql = "update mt set dlr_sent=true,dlr_tstamp='$tstamp' where msgid='$msgid'";
          break;
      }

      if(isset($sql)) {
        print("$sql\n");
        $res=pg_query($sql);
        if(!$res) {
          print("\n\n$sql\n");
          print(pg_last_error());
          die;
        }
      }

      if(isset($sql2)) {
        print("$sql2\n");
        $res=pg_query($sql2);
        if(!$res) {
          print("\n\n$sql\n");
          print(pg_last_error());
          die;
        }
      }
    
    }
  }

  pg_query("cluster mt using mt_pkey");
  pg_query("cluster mo using mo_pkey");

?>
