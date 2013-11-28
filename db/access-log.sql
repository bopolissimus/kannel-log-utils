begin;

drop table if exists access_log;
create table access_log
(
  mtmo text, 
  tm timestamp, 
  shortcode integer, 
  msisdn text,
  msg  text
);

end;
