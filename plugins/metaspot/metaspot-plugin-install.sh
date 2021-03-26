#!/bin/bash
# metaspot-plugin-install.sh
#
# Install Spotify metadata support
# - Copy files from directory where files were copied
# - Create tables needed for now playing feature
#
echo 'metaspot-plugin-install v0.1'
echo 'Installing Spotify metadata support...'

# TODO: Check sudo
#

###########################################################
echo 'Backup up original moOde files'

# Create archive filename.
# day=$(date +%A)
HOSTNAME=$(hostname -s)
BACKUP_FILE="metaspot-backup-$HOSTNAME.tgz"

###########################################################
echo 'Kill previous daemons'
php /var/www/inc/metaspot-plugin-stop.php 2>&1

###########################################################
echo 'Check if backup already exists'
if [ -f $BACKUP_FILE ]; then
  # TODO: Backup original files
  backup_files1="/var/www/spo-config.php"
  backup_files2="/var/www/templates/spo-config.html"
  backup_files3="/var/www/inc/playerlib.php"
  backup_files4="/var/www/command/sysinfo.sh"
  backup_files5="/var/www/command/worker.php"

  # Backup the files using tar.
  tar czf ./$BACKUP_FILE $backup_files1 $backup_files2 $backup_files3 $backup_files4 $backup_files5
else
  echo 'Backup file already exists...'
fi

###########################################################
echo 'Copy modified moOde files'
cp ./spo-config.php             /var/www
cp ./spo-config.html            /var/www/templates
# cp ./engine-mpd.php             /var/www
cp ./playerlib.php              /var/www/inc
# cp ./moode-sqlite3.db.sql       /var/local/www/db   # No need for this. Plugin install (this file) does that already.
# cp ./sysinfo.sh                 /var/www/command
cp ./worker.php                  /var/www/command

###########################################################
echo 'Copy metaspot-plugin files'
chmod +x ./metaspot-plugin-install.sh
chmod +x ./metaspot-plugin-start.php
cp ./metaspot-plugin-install.sh /var/local/www/commandw
cp ./metaspot-plugin-daemon.php /var/www/inc
cp ./metaspot-plugin-start.php  /var/www/inc
# cp ./metaspot-plugin-stop.php   /var/www/inc
cp ./metaspot-plugin-ui.php     /var/www
cp ./metaspot-plugin-lib.js     /var/www/js
cp ./metaspot-plugin-tpl.html   /var/www/templates

###########################################################
# install Vollibrespot
# Copy installation procedure.
# Borrowed from: https://github.com/balbuze/volumio-plugins/blob/master/plugins/music_service/volspotconnect2/install.sh
echo 'Install Vollibrespot'
VOLSPOT_EXEC="/usr/local/bin/vollibrespot"
VOLSPOT_INSTALLED=no
name="vollibrespot"
libpath=.

# Check if vollspot is installed
if [ -f $VOLSPOT_EXEC ]; then
  VOLSPOT_INSTALLED=yes
fi

## Get the Daemon binary
if [[ $VOLSPOT_INSTALLED == no ]]; then
  declare -A VLS_BIN=(
    [armv6l]="vollibrespot-armv6l.tar.xz"  \
    [armv7l]="vollibrespot-armv7l.tar.xz" \
    [aarch64]="vollibrespot-armv7l.tar.xz" \
    [i686]="vollibrespot-i686.tar.xz" \
  )

  # Find arch
  cpu=$(lscpu | awk 'FNR == 1 {print $2}')
  echo "Detected cpu architecture as $cpu"

  # Download and extract latest release
  cd $libpath
  if [ ${VLS_BIN[$cpu]+ok} ]; then
    # Check for the latest release first
    RELEASE_JSON=$(curl --silent "https://api.github.com/repos/ashthespy/vollibrespot/releases/latest")
    # LATEST_VER=$(jq -r  '.tag_name' <<< "${RELEASE_JSON}")
    LATEST_VER=$(curl --silent "https://api.github.com/repos/ashthespy/vollibrespot/releases/latest" | grep -Po '"tag_name":(.*?[^\\]",)')
    echo "Supported device (arch = $cpu), downloading required packages for vollibrespot $LATEST_VER"
    LATEST_VER="v0.2.2"
    DOWNLOAD_URL="https://github.com/ashthespy/Vollibrespot/releases/download/$LATEST_VER/${VLS_BIN[$cpu]}"
    echo "Downloading file <${DOWNLOAD_URL}>"
    wget $DOWNLOAD_URL
    if [ $? -eq 0 ]; then
      echo "Extracting..."
      tar -xf ${VLS_BIN[$cpu]}
      rm ${VLS_BIN[$cpu]}
    else
      echo -e "Failed to download vollibrespot daemon. Check for internet connectivity/DNS issues. \nTerminating installation!"
      exit -1
    fi
  else
    echo -e "Sorry, current device (arch = $cpu) is not supported! \nTerminating installation!"
    exit -1
  fi

  ## Install the service
  # For now just copy to /usr/local/bin
  chmod +x ./vollibrespot
  cp ./vollibrespot               /usr/local/bin
  # TODO: Explore install it as systemd service
  # sudo tar -xvf ${name}.service.tar -C /
  # sudo chmod +x /data/plugins/music_service/${name}/onstart1.sh
  echo "${name} installed"
else
  echo "${name} already installed"
fi

###########################################################
echo 'Creating DB data...'
SQLDB=/var/local/www/db/moode-sqlite3.db

# Add metaspot-plugin to cfg_spotify
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_spotify (id, param, value) VALUES (7, 'vollibrespot', 'Yes')")

echo 'Verify DB cfg_spotify data...'
RESULT=$(sqlite3 $SQLDB "select value from cfg_spotify")
readarray -t arr <<<"$RESULT"
BITRATE=${arr[0]}
METASPOT=${arr[6]}
echo 'Stored Bitrate' $BITRATE
echo 'Stored Spotify Metadata' $METASPOT

# Create new cfg_nowplaying table
RESULT=$(sqlite3 $SQLDB "CREATE TABLE cfg_nowplaying (id INTEGER PRIMARY KEY, param CHAR (32), value CHAR (32))")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (1, 'metadata', '')")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (2, 'title', '')")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (3, 'artist', '')")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (4, 'album', '')")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (5, 'cover_url', '')")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (6, 'volume', '')")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (7, 'duration_ms', '')")
RESULT=$(sqlite3 $SQLDB "INSERT INTO cfg_nowplaying (id, param, value) VALUES (8, 'position_ms', '')")

echo 'Verify DB cfg_nowplaying data...'
RESULT=$(sqlite3 $SQLDB "select value from cfg_nowplaying")
readarray -t arr <<<"$RESULT"
METADATA=${arr[0]}
TITLE=${arr[1]}
ARTIST=${arr[2]}
ALBUM=${arr[3]}
echo 'Last Song Title' $TITLE
echo 'Last Song Artist' $ARTIST

###########################################################
echo 'Start plugin services'
read -p 'Press <Enter> to start services or <Ctrl-C> otherwise'
php /var/www/inc/metaspot-plugin-start.php 2>&1 &

