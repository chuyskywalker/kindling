#!/bin/sh

# where shall we store this?
PREFIX=/www/steamalerts
LOGFILE=$PREFIX/logs/$1/`date +%Y_%m_%d`.log
PIDFILE=$PREFIX/daemons/$1.pid

# make sure we actually have a place to put this log file!
if [ ! -d $PREFIX/logs/$1 ]; then
    mkdir -p $PREFIX/logs/$1
fi

if [ ! -d $PREFIX/daemons ]; then
    mkdir -p $PREFIX/daemons
fi

# Check to see if the PID file is present
if [ -f $PIDFILE ]; then
    # it is, ensure the process is still running
    ps -p `cat $PIDFILE` > /dev/null 2>&1
    RETVAL=$?
    if [ $RETVAL -eq 0 ]; then
      # we're still running
      #echo 'running'
      exit 0
    fi
fi

# If you have reached this point, the process has either died or has never been started. Make it go!
rm -rf $PIDFILE

# find the file we want to run, run it, and append the std&err to the log file
SCRIPTFILE=$PREFIX/code/$1.php
if [ -e $SCRIPTFILE ]; then
	# Just because we got here from the PIDFILE being empty/missing does not mean the process is dead. Make SURE it is.
	pkill -f $SCRIPTFILE
	# Now start our fresh copy of the script
	/usr/local/bin/php -f $SCRIPTFILE $2 $3 $4 >> $LOGFILE 2>&1 &
	NEWPID=`pgrep -nf $SCRIPTFILE`
	if [ $? -eq 0 ]; then
		echo $NEWPID > $PIDFILE
		#echo 'New process started: ' $NEWPID
		exit 0;
	else
		echo 'Failed to find the PID of the process we just started. Did it fail to start or finish too quickly?'
		exit 1;
	fi
fi

echo 'Script not found!'
exit 1;
