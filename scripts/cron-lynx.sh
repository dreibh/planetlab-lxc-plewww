#!/bin/sh
# $Id: cron-lynx.sh 144 2007-03-28 07:52:20Z thierry $

/usr/bin/lynx -source http://yoursite.com/cron.php > /dev/null 2>&1
