# metaspot-plugin

This is a preliminary ("beta") version of a plugin for moOde that allows showing metadata for a currently song being played from a Spotify app ("Now Playing feature").

This plugin is based on (Vollibrespot)[https://github.com/ashthespy/Vollibrespot] based on (librespot)[https://github.com/librespot-org/librespot].

A fork of the moode repo with a new branch (nowplaying) can be found (here)[https://github.com/mgsg/moode/tree/nowplaying].

## What does it do?
When Vollibrespot detects a song being played, (PC, iPad, Android), this moOde plugin shows the currently playing song title/album/artist, just as if played from MPD/File system.

## Changes made

Files modified from standard moOde installation:

- [/plugins/metaspot/spo-config.php] Changes to support the new cfg_spotify param
- [/plugins/metaspot/spo-config.html] Template to show new metadata info config option (metaspot-plugin) called "Show Metadata"
- [/plugins/metaspot/sysinfo.sh] Changes to support the new cfg_spotify param
- [/plugins/metaspot/playerlib.php] Changes to optionally start vollibrespot instead of librespot from startSpotify

New files for this plugin:

- [/plugins/metaspot/metaspot-plugin-daemon.php] NEW FILE: php daemon that reads UDP metadata info and stores it in sqlib3 cfg_nowplaying table. chmod +x needed.
- [/plugins/metaspot/metaspot-plugin-install.sh] NEW FILE: Copies changed files, creates cfg_nowplaying table and inserts default values. chmod +x needed. Added a new Table cfg_nowplaying to store the song metadata. Added new parameter 'vollibrespot' to existing Table cfg_spotify (Yes/No values).
- [/plugins/metaspot/metaspot-plugin-start.php] NEW FILE: Starts both Vollibrespot and the metaspot-plugin-daemon. Executed from playerlib.php (startSpotify function.)
- [/plugins/metaspot/metaspot-plugin-stop.sh] FUTURE: Stops both Vollibrespot and the metaspot-plugin-daemon. Executed from playerlib.php (stopSpotify function). NOTE: For now I just do a pkill.

- [/usr/local/bin/vollibrespot] NEW FILE: Downloaded & installed by the plugin install.sh script. This is the (Vollibrespot daemon)[https://github.com/ashthespy/Vollibrespot] that publishes Spotify connect song metadata. chmod +x needed.
- [/plugins/metaspot/metaspot-plugin-ui.php] New page that shows the current song playing from a renderer.

## NOTES

- Why a new table? sqlib3 was used for other stuff, and it looked to cleanest way to communicate to moOde the metadata captured by vollibrespot.
- When playing a song as a renderer, the moOde web display is covered. A link should be seen there with a link to the new page where you would see the now playing song, as if it was playing from MPD. TODO: Modify playerlib.js (it is minified...).
- Only title, album, artist (first one) and cover image are updated for now.
- Now the page refreshes every 20secs. TODO: notify the web ui that new data is available for it to refresh.

## TODO:

- Update additional info that is accesible: current song elapsed time, bitrate,...
- Start vollibrespot / udp_metadata with systemd (bonus: autorestart if crashes).


## HOW TO INSTALL
- Download all files from the metaspot-plugin directory to a new directory in your Raspberry Pi.
- chmod +x metaspot-plugin-install.sh
- sudo ./metaspot-plugin-install.sh
- In moOde, configure the Spotify renderer again, and select "Metadata"Restart again and try.