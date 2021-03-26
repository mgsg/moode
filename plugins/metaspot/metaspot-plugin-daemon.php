<?php
/**
 * moOde audio player (C) 2014 Tim Curtis
 * http://moodeaudio.org
 *
 * metaspot-plugin-daemon.php
 * 
 * Daemon that listens on UDP for Spotify metadata and stores it in DB
 *
 */

define('SQLDB', 'sqlite:/var/local/www/db/moode-sqlite3.db');
define('SQLDB_PATH', '/var/local/www/db/moode-sqlite3.db');
define('METASPOT_LOG', '/var/log/metaspot-plugin-daemon.log');

// Debug message logger
function debugLog($msg, $mode = 'a') {
	$fh = fopen(METASPOT_LOG, $mode);
	fwrite($fh, date('Ymd His ') . $msg . "\n");
	fclose($fh);
}

// Database management
function cfgdb_connect() {
	if ($dbh = new PDO(SQLDB)) {
		return $dbh;
	}
	else {
		debugLog("cannot open SQLite database");
		return false;
	}
}

function cfgdb_update($table, $dbh, $key = '', $value) {
  $querystr = "UPDATE " . $table . " SET value='" . $value . "' WHERE param='" . $key . "'";
	if (sdbquery($querystr,$dbh)) {
		return true;
	}
	else {
		return false;
	}
}  

function cfgdb_read($table, $dbh, $param = '', $id = '') {
	$querystr = 'SELECT value FROM ' . $table . ' WHERE param="' . $param . '"';
	$result = sdbquery($querystr, $dbh);
	return $result;
}

function sdbquery($querystr, $dbh) {
	$query = $dbh->prepare($querystr);
	if ($query->execute()) {
		$result = array();
		$i = 0;
		foreach ($query as $value) {
			$result[$i] = $value;
			$i++;
		}
		$dbh = null;
		if (empty($result)) {
			return true;
		}
		else {
			return $result;
		}
	}
	else {
		return false;
	}
}

function debugLogMetadata($metaobj) {
  try {
    debugLog('Metadata ARRIVED=' . json_encode($metaobj->metadata) );
    debugLog('Title=' . (string)$metaobj->metadata->track_name );
    debugLog('Album=' . (string)$metaobj->metadata->album_name );
    if (isset($metaobj->metadata->artist_name)) {
      debugLog('Artist=' . (string)$metaobj->metadata->artist_name[0] );
    }
    if (isset($metaobj->metadata->albumartId)) {
      debugLog('CoverUrl=' . (string)$metaobj->metadata->albumartId[0] );
    }
  } catch (Exception $e) {
    debugLog('Exception showing metadata=' . $e->getMessage());
  }
}

function storeMetadata($metaobj) {
  // Save to DB
  try {
    $dbh  = cfgdb_connect();
    cfgdb_update('cfg_nowplaying', $dbh, 'metadata', json_encode($metaobj->metadata));
    cfgdb_update('cfg_nowplaying', $dbh, 'title', $metaobj->metadata->track_name);
    cfgdb_update('cfg_nowplaying', $dbh, 'album', $metaobj->metadata->album_name);
    cfgdb_update('cfg_nowplaying', $dbh, 'artist', $metaobj->metadata->artist_name[0]);
    if (isset($metaobj->metadata->albumartId)) {
      cfgdb_update('cfg_nowplaying', $dbh, 'cover_url', $metaobj->metadata->albumartId[0]);
    } else {
      cfgdb_update('cfg_nowplaying', $dbh, 'cover_url', '');
    }
    cfgdb_update('cfg_nowplaying', $dbh, 'duration_ms', $metaobj->metadata->duration_ms);
    cfgdb_update('cfg_nowplaying', $dbh, 'position_ms', $metaobj->metadata->position_ms);
    $dbh = null;      
  } catch (Exception $e) {
    debugLog('Exception updating DB metadata=' . $e->getMessage());
  }
}

function processMetadataInfo($metadata) {
  debugLog('Reply (5030):' . $metadata);

  if ($metadata[0] === '{') {
    $metaobj = json_decode($metadata);
    if (isset($metaobj->state) && isset($metaobj->state->status) && $metaobj->state->status == 'play') {
      debugLog('Play -> set spotactive=1 in DB');
      $dbh  = cfgdb_connect();
      cfgdb_update('cfg_system', $dbh, 'spotactive', '1');
      $dbh = null; 
      // TODO: Set ALSA VOL
    } else if (isset($metaobj->state) && isset($metaobj->state->status) && $metaobj->state->status == 'pause') {
      debugLog('Pause -> set spotactive=0 in DB');
      $dbh  = cfgdb_connect();
      cfgdb_update('cfg_system', $dbh, 'spotactive', '0');
      $dbh = null;   
      // TODO: Restore ALSA VOL      
    } else if (isset($metaobj->metadata)) {
      // Parse metadata
      debugLogMetadata($metaobj);
      storeMetadata($metaobj);
    } else if (isset($metaobj->volume)) {
      // Volume changes
      $dbh  = cfgdb_connect();
      $newVolume = $metaobj->volume;
      debugLog('New volume =[' . $newVolume . ']');
      cfgdb_update('cfg_nowplaying', $dbh, 'volume', $metaobj->volume);
    }
  } else if ($metadata === 'kSpDeviceActive') {
    debugLog('kSpDeviceActive');
  } else if ($metadata === 'kSpPlaybackLoading') {
    debugLog('kSpDeviceActive');
  }
}

// Reduce errors
error_reporting(~E_WARNING);

$server = '127.0.0.1';
$portSend = 5031;
$portReceive = 5030;

debugLog('********************');
debugLog('Starting udp metadata monitor process');

// Read currently stored metadata info
$dbh  = cfgdb_connect();
$result = cfgdb_read('cfg_nowplaying', $dbh, 'metadata');
$dbh = null;
if ($result === false) {
  debugLog('Now playing - no data');
} else {
  // TODO: Check array to string conversion error:
  // for ($i = 0; $i < count($result); $i++) {
  //  debugLog('Now playing info [' . (string)$result[$i] . ']');
  // }
}

// Create sending socket
if(!($sockSend = socket_create(AF_INET, SOCK_DGRAM, 0)))
{
	$errorcode = socket_last_error();
	$errormsg = socket_strerror($errorcode);
  debugLog("Couldn't create socket: [$errorcode] $errormsg \n");
	die("Couldn't create socket: [$errorcode] $errormsg \n");
} else {
  debugLog('Sending socket created @ port ' . $portSend);
}


// Create & bind receiving socket
if(!($sockReceive = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)))
{
	$errorcode = socket_last_error();
	$errormsg = socket_strerror($errorcode);
  debugLog("Couldn't create socket: [$errorcode] $errormsg \n");
	die("Couldn't create socket: [$errorcode] $errormsg \n");
}

$result = socket_bind($sockReceive, $server, $portReceive);
if ($result === false) {
  debugLog('socket_connect() failed. Reason:' . $result . ' socket_strerror(socket_last_error(' . $sockReceive . ')');
} else {
  debugLog('Receiving socket connected @ port ' . $portReceive);
}


// Send Hello msg to server
$hello = "\x1";
if( ! socket_sendto($sockSend, $hello , strlen($hello) , 0 , $server , $portSend))
{
	$errorcode = socket_last_error();
	$errormsg = socket_strerror($errorcode);
  debugLog("Could not send data: [$errorcode] $errormsg \n");
	die("Could not send data: [$errorcode] $errormsg \n");
}
debugLog('Hello sent (5031)!');

// Communication loop
while(1)
{
	$from = '';
	// Receive metadata and process it
	if(socket_recvfrom ( $sockReceive , $reply , 2045 , 0, $server, $portRemote ) === FALSE) {
		$errorcode = socket_last_error();
		$errormsg = socket_strerror($errorcode);
    debugLog("Could not receive data: [$errorcode] $errormsg \n");
		die("Could not receive data: [$errorcode] $errormsg \n");
	} else {
    processMetadataInfo($reply);
	}
}

$dbh = null;