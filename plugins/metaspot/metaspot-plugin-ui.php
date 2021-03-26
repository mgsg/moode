<?php
/**
 * moOde audio player (C) 2014 Tim Curtis
 * http://moodeaudio.org
 *
 * metaspot-plugin-ui.php
 *
 */

require_once dirname(__FILE__) . '/inc/playerlib.php';

define('SQLDB', 'sqlite:/var/local/www/db/moode-sqlite3.db');
define('SQLDB_PATH', '/var/local/www/db/moode-sqlite3.db');
define('METASPOT_LOG', '/var/log/metaspot-plugin-ui.log');

// Debug message logger
function rendererLog($msg, $mode = 'a') {
	$fh = fopen(METASPOT_LOG, $mode);
	fwrite($fh, date('Ymd His ') . $msg . "\n");
	fclose($fh);
}

try {
  rendererLog('metaspot-plugin-ui Started - debugLog');
  workerLog('metaspot-plugin-ui Started - workerLog');

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
  workerLog('metaspot-plugin-ui metaspot-plugin-active=' . $cfg_spotify['vollibrespot']);

  $resultSpotActive = sdbquery("SELECT value FROM cfg_system WHERE param='spotactive'", cfgdb_connect());
  workerLog('metaspot-plugin-ui spotify-active=' . $resultSpotActive[0]['value']);
  // && $resultSpotActive[0]['value'] == '1'
  // $cfg_spotify['vollibrespot'] == 'Yes'
  if ($resultSpotActive[0]['value'] == '1') {  
    workerLog('metaspot-plugin-ui: Getting Spotify metadata');
    // sleep(10);

    $resultMetadata = sdbquery("SELECT * FROM cfg_nowplaying", cfgdb_connect());
    $cfg_nowplaying = array();
    foreach ($resultMetadata as $row) {
      $cfg_nowplaying[$row['param']] = $row['value'];
    }
    workerLog('metaspot-plugin-ui: Spotify song title=(' . $cfg_nowplaying['title'] . ')');

    $milliseconds = $cfg_nowplaying['duration_ms'];
    $seconds = floor(($milliseconds%60000)/1000);
    $minutes = floor($milliseconds / 60000);
    $format = '%02u:%02u';
    $song_total_time = sprintf($format, $minutes, $seconds);
    workerLog('metaspot-plugin-ui: Spotify song duration=(' . $song_total_time . ')');

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
    workerLog('metaspot-plugin-ui: Metadata returned to client: Json=(' . json_encode($_current) . ')');
    rendererLog('metaspot-plugin-ui: Metadata returned to client: Json=(' . json_encode($_current) . ')');
 }
} catch (Exception $e) {
  rendererLog('Exception retrieving Spotify metadata=' . $e->getMessage());
  workerLog('Exception retrieving Spotify metadata=' . $e->getMessage());
}

$section = basename(__FILE__, '.php');

$tpl = "metaspot-plugin-tpl.html";
include('header.php');
eval("echoTemplate(\"".getTemplate("/var/www/templates/$tpl")."\");");
include('footer.php');
