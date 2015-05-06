/*
  A table to store some interesting fields from submit_sm, submit_sm_resp, deliver_sm, deliver_sm_resp and the DLR fields.
  
  We don't store everything, just some commonly used fields.  Add more later as necessary.
*/

drop table if exists mt;

/* populated from submit_sm and (for msgid) from submit_sm_resp. */
create table mt (
  id serial primary key,
  tstamp timestamp not null default now(),  /* of the submit, not the resp. */
  status int not null,  /* submit is always zero.  this is also set in the resp. */
  seqno int not null,
  has_dlr boolean not null,
  dlr_status text default '',
  dlr_saved boolean default false,
  dlr_sent boolean default false,
  dlr_tstamp timestamp default null,
  msgid text,            /* from the submit_sm_resp, null while no resp yet. */
  src text not null,
  dest text not null,
  msg text not null
);


drop table if exists mo;

/* populated from deliver_sm and deliver_sm_resp. if a DLR, can be associated with
   the mt entry by msgid. */
create table mo (
  id serial primary key,
  tstamp timestamp not null default now(),
  status int not null,
  seqno int not null,
  dlr_msgid text, /* receipted_message_id, null if not a DLR */
  src text not null,
  dest text not null,
  msg text not null
);

