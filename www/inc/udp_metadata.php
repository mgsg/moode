<?php
/**
 * php udp socket reading client
 */

define('SQLDB', 'sqlite:/var/local/www/db/moode-sqlite3.db');
define('SQLDB_PATH', '/var/local/www/db/moode-sqlite3.db');
define('UDP_METADATA_LOG', '/var/log/udp_metadata.log');

// Debug message logger
function debugLog($msg, $mode = 'a') {
	$fh = fopen(UDP_METADATA_LOG, $mode);
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

function processMetadataInfo($metadata) {
  debugLog('Reply (5030):' . $metadata);

  if ($metadata[0] === '{') {
    $metaobj = json_decode($metadata);
    if (isset($metaobj->state) && isset($metaobj->state->status) && $metaobj->state->status == 'play') {
      debugLog('Playing -> spotactive=1');
      // $dbh  = cfgdb_connect();
      // cfgdb_update('cfg_system', $dbh, 'spotactive', '1');
      // $dbh = null; 
      // Set ALSA VOL
    } else if (isset($metaobj->state) && isset($metaobj->state->status) && $metaobj->state->status == 'pause') {
      debugLog('Playing -> spotactive=0');
      // $dbh  = cfgdb_connect();
      // cfgdb_update('cfg_system', $dbh, 'spotactive', '0');
      // $dbh = null;   
      // Restore ALSA VOL      
    } else if (isset($metaobj->metadata)) {  
      debugLog('Metadata arrived');
      try {
        debugLog('Metadata=' . json_encode($metaobj->metadata) );
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
    } else if (isset($metaobj->volume)) {
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

// Read currently stored metadata info
$dbh  = cfgdb_connect();
$result = cfgdb_read('cfg_nowplaying', $dbh, 'metadata');
$dbh = null;
if ($result === false) {
  debugLog('Now playing - no data');
} else {
  // for ($i = 0; $i < count($result); $i++) {
  //   debugLog('Now playing info [' . (string)$result[$i] . ']');
  // }
}

// Create sending socket
if(!($sockSend = socket_create(AF_INET, SOCK_DGRAM, 0)))
{
	$errorcode = socket_last_error();
	$errormsg = socket_strerror($errorcode);
	die("Couldn't create socket: [$errorcode] $errormsg \n");
}

debugLog('Sending socket created');


// Create receiving socket
if(!($sockReceive = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)))
{
	$errorcode = socket_last_error();
	$errormsg = socket_strerror($errorcode);
	die("Couldn't create socket: [$errorcode] $errormsg \n");
}

$result = socket_bind($sockReceive, $server, $portReceive);
if ($result === false) {
  debugLog('socket_connect() failed. Reason:' . $result . ' socket_strerror(socket_last_error(' . $sockReceive . ')');
} else {
  debugLog('Receiving socket connected.');
}


// Send Hello msg
$hello = "\x1";

// Send to server
if( ! socket_sendto($sockSend, $hello , strlen($hello) , 0 , $server , $portSend))
{
	$errorcode = socket_last_error();
	$errormsg = socket_strerror($errorcode);
	die("Could not send data: [$errorcode] $errormsg \n");
}
debugLog('Hello sent (5031)!');


// Communication loop
while(1)
{
	$from = '';
	//Now receive reply from server and print it
	if(socket_recvfrom ( $sockReceive , $reply , 2045 , 0, $server, $portRemote ) === FALSE)
	{
		$errorcode = socket_last_error();
		$errormsg = socket_strerror($errorcode);
		die("Could not receive data: [$errorcode] $errormsg \n");
	} else {
    processMetadataInfo($reply);
	}
}

$dbh = null;