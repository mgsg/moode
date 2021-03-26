<?php
/**
 * moOde audio player (C) 2014 Tim Curtis
 * http://moodeaudio.org
 *
 * tsunamp player ui (C) 2013 Andrea Coiutti & Simone De Gregori
 * http://www.tsunamp.com
 *
 * This Program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3, or (at your option)
 * any later version.
 *
 * This Program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * 2020-07-22 TC moOde 6.7.1
 *
 */

require_once dirname(__FILE__) . '/inc/playerlib.php';

define('SQLDB', 'sqlite:/var/local/www/db/moode-sqlite3.db');
define('SQLDB_PATH', '/var/local/www/db/moode-sqlite3.db');
define('UDP_METADATA_LOG', '/var/log/udp_metadata.log');

// Debug message logger
function rendererLog($msg, $mode = 'a') {
	$fh = fopen(UDP_METADATA_LOG, $mode);
	fwrite($fh, date('Ymd His ') . $msg . "\n");
	fclose($fh);
}

// // Debug message logger
// function debugLog($msg, $mode = 'a') {
// 	$fh = fopen(UDP_METADATA_LOG, $mode);
// 	fwrite($fh, date('Ymd His ') . $msg . "\n");
// 	fclose($fh);
// }

// // Database management
// function cfgdb_connect() {
// 	if ($dbh = new PDO(SQLDB)) {
// 		return $dbh;
// 	}
// 	else {
// 		debugLog("cannot open SQLite database");
// 		return false;
// 	}
// }

// function cfgdb_update($table, $dbh, $key = '', $value) {
//   $querystr = "UPDATE " . $table . " SET value='" . $value . "' WHERE param='" . $key . "'";
// 	if (sdbquery($querystr,$dbh)) {
// 		return true;
// 	}
// 	else {
// 		return false;
// 	}
// }  

// function cfgdb_read($table, $dbh, $param = '', $id = '') {
// 	$querystr = 'SELECT value FROM ' . $table . ' WHERE param="' . $param . '"';
// 	$result = sdbquery($querystr, $dbh);
// 	return $result;
// }

// function sdbquery($querystr, $dbh) {
// 	$query = $dbh->prepare($querystr);
// 	if ($query->execute()) {
// 		$result = array();
// 		$i = 0;
// 		foreach ($query as $value) {
// 			$result[$i] = $value;
// 			$i++;
// 		}
// 		$dbh = null;
// 		if (empty($result)) {
// 			return true;
// 		}
// 		else {
// 			return $result;
// 		}
// 	}
// 	else {
// 		return false;
// 	}
// }

debugLog('renderer-status Started - debugLog');
workerLog('renderer-status Started - workerLog');

playerSession('open', '', '');
playerSession('write', 'volknob', '25');
playerSession('write', 'mpdmixer', 'software');
session_write_close();

// Spotify metadata
$resultSpotify = sdbquery("SELECT * FROM cfg_spotify", cfgdb_connect());
$cfg_spotify = array();
foreach ($resultSpotify as $row) {
  $cfg_spotify[$row['param']] = $row['value'];
}
workerLog('renderer-status Vollibrespot='+$cfg_spotify['vollibrespot']);

$resultSpotActive = sdbquery("SELECT value FROM cfg_system WHERE param='spotactive'", cfgdb_connect());

// && $resultSpotActive[0]['value'] == '1'
if ($cfg_spotify['vollibrespot'] == 'Yes') {  
  workerLog('renderer-status: Getting Spotify metadata');
  // sleep(10);

  try {
    $resultMetadata = sdbquery("SELECT * FROM cfg_nowplaying", cfgdb_connect());
    $cfg_nowplaying = array();
    foreach ($resultMetadata as $row) {
      $cfg_nowplaying[$row['param']] = $row['value'];
    }
    workerLog('renderer-status: Spotify song title=(' . $cfg_nowplaying['title'] . ')');

    $milliseconds = $cfg_nowplaying['duration_ms'];
    $seconds = floor(($milliseconds%60000)/1000);
    $minutes = floor($milliseconds / 60000);
    $format = '%02u:%02u';
    $song_total_time = sprintf($format, $minutes, $seconds);
    workerLog('renderer-status: Spotify song duration=(' . $song_total_time . ')');

    $_current['artist'] = $cfg_nowplaying['artist'];
    $_current['title'] = $cfg_nowplaying['title'];
    $_current['album'] = $cfg_nowplaying['album'];
    $_current['coverurl'] = 'http://i.scdn.co/image/' . $cfg_nowplaying['cover_url'];
    $_current['cover_art_hash'] = $cfg_nowplaying['cover_url'];
    $_current['state'] = 'play';
    $_current['file'] = '';
    // $_current['elapsed'] = '00:10';      // From beginning
    $_current['time'] = $song_total_time;
    // $_current['song_percent'] = 25;
    $_current['disc'] = '';
    $_current['track'] = '-';
    $_current['encoded'] = 'Spotify';
    $_current['bitrate'] = '320bps';
    workerLog('renderer-status: Metadata returned to client: Json=(' . json_encode($_current) . ')');
  } catch (Exception $e) {
    workerLog('Exception retrieving Spotify metadata=' . $e->getMessage());
  }
}

$section = basename(__FILE__, '.php');

$tpl = "renderertpl.html";
include('header.php');
eval("echoTemplate(\"".getTemplate("/var/www/templates/$tpl")."\");");
include('footer.php');
