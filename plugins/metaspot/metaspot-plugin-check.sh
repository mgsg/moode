#!/bin/bash
SQLDB=/var/local/www/db/moode-sqlite3.db

echo 'Verify DB cfg_spotify data...'
RESULT=$(sqlite3 $SQLDB "select value from cfg_spotify")
readarray -t arr <<<"$RESULT"
BITRATE=${arr[0]}
METASPOT=${arr[6]}
echo 'Bitrate' $BITRATE
echo 'Spotify Metadata' $METASPOT

RESULT=$(sqlite3 $SQLDB "select value from cfg_nowplaying")
readarray -t arr <<<"$RESULT"
METADATA=${arr[0]}
TITLE=${arr[1]}
ARTIST=${arr[2]}
ALBUM=${arr[3]}
COVER_URL=${arr[4]}

echo 'Verify DB cfg_nowplaying data...'
echo 'Title' $TITLE
echo 'Artist' $ARTIST
echo 'Cover URL' $COVER_URLy
