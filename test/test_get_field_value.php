<?php
  include_once "../buntangle_lib.php";

  $arr=array(
  "2015-04-30 16:59:05 [23054] [13] DEBUG:   type_name: deliver_sm",
  "2015-04-30 16:59:05 [23054] [13] DEBUG:   command_status: 0 = 0x00000000",
  '2015-04-30 16:59:05 [23054] [13] DEBUG:   destination_addr: "177"',
  '2015-04-30 16:59:05 [23054] [13] DEBUG:      data: 69 64 3a 31 37 33 39 31 30 30 30 31 36 37 31 36   id:1739100016716',
  '2015-04-30 16:59:05 [23054] [13] DEBUG:      data: 32 20 73 75 62 3a 30 30 31 20 64 6c 76 72 64 3a   2 sub:001 dlvrd:',
  '2015-04-30 16:59:05 [23054] [13] DEBUG:      data: 30 30 30 20 73 75 62 6d 69 74 20 64 61 74 65 3a   000 submit date:',
  '2015-04-30 16:59:05 [23054] [13] DEBUG:      data: 31 35 30 34 33 30 31 36 35 33 20 64 6f 6e 65 20   1504301653 done',
  '2015-04-30 16:59:05 [23054] [13] DEBUG:      data: 64 61 74 65 3a 31 35 30 34 33 30 31 36 35 39 20   date:1504301659',
  '2015-04-30 16:59:05 [23054] [13] DEBUG:      data: 73 74 61 74 3a 44 45 4c 45 54 45 44 20 65 72 72   stat:DELETED err',
  '2015-04-30 16:59:05 [23054] [13] DEBUG:      data: 3a 30 30 30 20 54 65 78 74 3a 73 61 6d 65 20 6d   :000 Text:same m',
  '2015-04-30 16:59:05 [23054] [13] DEBUG:      data: 65 73 73 61 67 65 20 74 6f 20 61 6c 6c 00         essage to all.');

   $data='id:17391000167162 sub:001 dlvrd:000 submit date:1504301653 done date:1504301659 stat:DELETED err:000 Text:same message to all';

    assert('deliver_sm'==get_pdu_field($arr, 'type_name'));
    assert(0 == get_pdu_field($arr, 'command_status'));
    assert('177' == get_pdu_field($arr, 'destination_addr'));

    assert($data == get_pdu_field($arr, 'data'));
?>
