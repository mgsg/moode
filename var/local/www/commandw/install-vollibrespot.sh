#!/bin/bash
# Install Spotify metadata support using Vollibrespot
# - Copy files from directory where files were copied
# - Create tables needed for vollibrespot / now playing
#

echo 'Installing Spotify metadata support...'

echo 'Copying modded files from /home/pi/vollibrespot to correct directories'
chmod +x ./install-vollibrespot.sh
chmod +x ./udp_metadata.php
chmod +x ./vollibrespot
cp ./vollibrespot            /usr/local/bin
cp ./spo-config.php          /var/www
cp ./engine-mpd.php          /var/www
cp ./spo-config.html         /var/www/templates
cp ./playerlib.php           /var/www/inc
cp ./udp_metadata.php        /var/www/inc
cp ./sysinfo.sh              /var/www/command
cp ./worker.php              /var/www/command
cp ./install-vollibrespot.sh /var/local/www/commandw
cp ./moode-sqlite3.db.sql    /var/local/www/db

echo 'Creating DB data...'
SQLDB=/var/local/www/db/moode-sqlite3.db

RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_spotify (id, param, value) VALUES (7, 'vollibrespot', 'Yes')")

RESULT=$(sqlite3 $SQLDB "CREATE TABLE cfg_nowplaying (id INTEGER PRIMARY KEY, param CHAR (32), value CHAR (32))")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (1, 'metadata', '')")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (2, 'title', '')")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (3, 'artist', '')")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (4, 'album', '')")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (5, 'cover_url', '')")

RESULT=$(sqlite3 $SQLDB "select value from cfg_nowplaying")
readarray -t arr <<<"$RESULT"
METADATA=${arr[0]}
TITLE=${arr[1]}
ARTIST=${arr[2]}
ALBUM=${arr[3]}
COVER_URL=${arr[4]}

echo 'Verify DB cfg_nowplaying data...'
echo 'Title=' $TITLE
echo 'Cover URL=' $COVER_URL

echo 'Verify DB cfg_spotify data...'
RESULT=$(sqlite3 $SQLDB "select value from cfg_spotify")
readarray -t arr <<<"$RESULT"
BITRATE=${arr[0]}
VOLLIBRESPOT=${arr[6]}

echo 'Bitrate' $BITRATE
echo 'Vollibrespot' $VOLLIBRESPOT
