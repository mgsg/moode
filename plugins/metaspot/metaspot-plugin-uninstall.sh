#!/bin/bash
# metaspot-plugin-uninstall.sh
#
# Revert moOde to original state
#
echo 'metaspot-plugin-uninstall v0.1'
echo 'Uninstalling Spotify metadata support...'

# TODO: Check sudo
#

###########################################################
# Changes to moOde files
# Restore original moOde files
# TODO: Check if backup directory exists
# TODO: Restore original files

###########################################################
# Stop vollibrespot & daemon
killall vollibrespot
pkill -F /var/log/metaspot-plugin-daemon.pid

###########################################################
# Uninstall vollibrespot
# For now, delete vollibrespot
rm /usr/local/bin/vollibrespot

###########################################################
# Delete metaspot-plugin files
rm /var/local/www/commandw/metaspot-plugin-install.sh
rm /var/www/inc/metaspot-plugin-daemon.php
rm /var/www/inc/metaspot-plugin-start.php
rm /var/www/inc/metaspot-plugin-stop.php
rm /var/www/metaspot-plugin-ui.php
rm /var/www/js/metaspot-plugin-lib.js
rm /var/www/templates/metaspot-plugin-tpl.html

###########################################################
# Database changes
# TODO: DELETE 
echo 'Deleting DB data...'
SQLDB=/var/local/www/db/moode-sqlite3.db

# Remove metaspot-plugin value to cfg_spotify
# TODO: DELETE! - RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_spotify (id, param, value) VALUES (7, 'vollibrespot', 'Yes')")

# Delete cfg_nowplaying table (?)
# TODO: DELETE! - RESULT=$(sqlite3 $SQLDB "CREATE TABLE cfg_nowplaying (id INTEGER PRIMARY KEY, param CHAR (32), value CHAR (32))")
