<?php
/**
 * moOde audio player (C) 2014 Tim Curtis
 * http://moodeaudio.org
 *
 * metaspot-plugin-stop.php
 * 
 * Stops the needed services for the metadata plugin
 *
 */

define('METASPOT_LOG', '/var/log/metaspot-plugin-start.log');

// Debug message logger
function debugLog($msg, $mode = 'a') {
	$fh = fopen(METASPOT_LOG, $mode);
	fwrite($fh, date('Ymd His ') . $msg . "\n");
	fclose($fh);
}

debugLog("#########################################");
debugLog("metaspot-plugin-stop v0.1");

# TODO: Check sudo
#

# Kill previous daemons
debugLog("metaspot-plugin-start - Kill started vollibrespot");
exec('sudo killall vollibrespot 2>&1 &', $output);

debugLog("metaspot-plugin-start - Kill started metaspot-plugin-daemon");
// sysCmd('kill $(ps aux | grep \'[p]hp /var/www/inc\'| awk \'{print $2}\')');
# This works from the command line: sudo kill $(ps aux | grep '[p]hp /var/www/inc/metaspot-plugin-daemon' | awk '{print $2}')
exec('sudo kill $(ps aux | grep \'[p]hp /var/www/inc/metaspot-plugin-daemon\'| awk \'{print $2}\') 2>&1 &', $output);