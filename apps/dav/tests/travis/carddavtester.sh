#!/usr/bin/env bash
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`


# start the server
php -S 127.0.0.1:8888 -t "$SCRIPTPATH/../../../.." &


if [ ! -f CalDAVTester/run.py ]; then
    git clone git@github.com:DeepDiver1975/CalDAVTester.git
	cd "$SCRIPTPATH/CalDAVTester"
    python run.py -s
	cd "$SCRIPTPATH"
fi

# create test user
cd "$SCRIPTPATH/../../../../"
OC_PASS=user01 php occ user:add --password-from-env user01
cd "$SCRIPTPATH/../../../../"

# run the tests
cd "$SCRIPTPATH/CalDAVTester"
PYTHONPATH="$SCRIPTPATH/pycalendar/src" python testcaldav.py --print-details-onfail -s "$SCRIPTPATH/config/serverinfo.xml" -o cdt.txt \
	CardDAV/ab-client.xml

