#!/bin/bash
# a script that takes some variables, finds all matching bearerbox logs
# in the source directory, runs buntangle on them and outputs the
# cleaned logs into destination directory.

set -e

LOGPAT='*.log'

# set BASE to the directory above where your bearerbox logs are.
# empty by default.  if you don't set it this script will fail.
BASE=/home/tiger/Projects-tiger/SMSC-Upgrade-WR233545

# set this to the directory under BASE where the raw logs are
SRC=logs

# and this is where the clean logs will go.
DEST=clean-logs

for s in `find ${BASE}/${SRC} -name ${LOGPAT}`
do
  d=`echo $s | sed "s/\/${SRC}\//\/${DEST}\//"`

  dd=`dirname $d`
  if [ ! -d $dd ]
  then
    mkdir -p $dd
  fi 

  php buntangle.php $s > $d

done
