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
 * 2019-MM-DD TC moOde 7.0.0
 *
 */

require_once dirname(__FILE__) . '/inc/playerlib.php';

$result = sdbquery("SELECT value FROM cfg_system WHERE param='wrkready'", cfgdb_connect());

// Check for Worker startup complete
if ($result[0]['value'] == '0') {
	//workerLog('engine-mpd: Worker startup is not finished yet');
	exit;
}

// Check for MPD connection failure
$sock = openMpdSock('localhost', 6600);
if (!$sock) {
	debugLog('engine-mpd: Connection to MPD failed');
	echo json_encode(array('error' => 'openMpdSock() failed', 'module' => 'engine-mpd'));
	exit;
}

//workerLog('engine-mpd: Get initial status');
$current = parseStatus(getMpdStatus($sock));

// Initiate MPD idle
//workerLog('engine-mpd: UI state=(' . $_GET['state'] . '), MPD state=(' . $current['state'] .')');
if ($_GET['state'] == $current['state']) {
	//workerLog('engine-mpd: Wait for idle timeout');
	sendMpdCmd($sock, 'idle');
	stream_set_timeout($sock, 600000); // Value determines how often PHP times out the socket
	$resp = readMpdResp($sock);

	$event = explode("\n", $resp)[0];
	//workerLog('engine-mpd: Idle timeout event=(' . $event . ')');
	//workerLog('engine-mpd: Get new status');
	$current = parseStatus(getMpdStatus($sock));
	$current['idle_timeout_event'] = $event;
}

// Create enhanced metadata
workerLog('engine-mpd: Generating enhanced metadata');
$current = enhanceMetadata($current, $sock, 'engine_mpd_php');
closeMpdSock($sock);
//workerLog('engine-mpd: Metadata returned to client: Size=(' . sizeof($current) . ')');
//foreach ($current as $key => $value) {workerLog('engine-mpd: Metadata returned to client: Raw=(' . $key . ' ' . $value . ')');}
//workerLog('engine-mpd: Metadata returned to client: Json=(' . json_encode($current) . ')');

// Spotify metadata
$resultSpotify = sdbquery("SELECT * FROM cfg_spotify", cfgdb_connect());
$cfg_spotify = array();
foreach ($resultSpotify as $row) {
  $cfg_spotify[$row['param']] = $row['value'];
}

if ($cfg_spotify['vollibrespot'] == 'Yes') {
  workerLog('engine-mpd: Getting Spotify metadata');
  sleep(10);
  $resultMetadata = sdbquery("SELECT * FROM cfg_nowplaying", cfgdb_connect());
  $cfg_nowplaying = array();
  foreach ($resultMetadata as $row) {
    $cfg_nowplaying[$row['param']] = $row['value'];
  }
  workerLog('engine-mpd: Spotify title=(' . $cfg_nowplaying['title'] . ')');

  $current['artist'] = $cfg_nowplaying['artist'];
  $current['title'] = $cfg_nowplaying['title'];
  $current['album'] = $cfg_nowplaying['album'];
  $current['state'] = $cfg_nowplaying['play'];
  $current['coverurl'] = $cfg_nowplaying['cover_url'];
  workerLog('engine-mpd: Metadata returned to client: Json=(' . json_encode($current) . ')');
  $current_json = json_encode($current, JSON_UNESCAPED_SLASHES);
} else {
  $current_json = json_encode($current);
}

// @ohinckel https: //github.com/moode-player/moode/pull/14/files
if ($current_json === FALSE) {
  echo json_encode(array('error' => array('code' => json_last_error(), 'message' => json_last_error_msg())));
}
else {
  echo $current_json;
}
