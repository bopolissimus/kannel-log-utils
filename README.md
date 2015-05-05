kannel-log-utils
================

misc programs for working with kannel log files

buntangle.php -- untangle kannel bearerbox files.  Reads the file
   and organizes PDU blocks so that nested or overlapping PDUs
   get untangled into separate PDUs with the PDU contents 
   consistent and untangled.

kannel_access_log_to_csv.php -- parse access logs, write out a CSV
   file with date, shortcode and msisdn.  It just writes out the
   CSV row with print, so we don't currently try to write out
   the SMS message (because we don't try to escape ",'\ yet).

   the CSV file is convenient for loading into a database for
   analysis (e.g., shortcode MT/MO per month which is what I 
   implemented this for).

cleanup.php -- in conjunction with a mangle_pdu.php file
   (see sample_mangle_pdu.php), can read a clean bearerbox 
   log file (already processed by buntangle.php) and
   mangle or remove PDUs we don't care about (e.g.,
   submit_sms which contain text messages we don't care
   about, and their corresponding submit_sm_resps).
