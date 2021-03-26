/*!
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
 * 2020-12-15 TC moOde 7.0.0
 *
 */

// Constants
const RENDERER_DATA       = 1;        // y Kernel architecture option on System Config


function renderUIVolRenderer() {
	//console.log('renderUIVol()');
	// Load session vars (required for multi-client)
    $.getJSON('command/moode.php?cmd=readcfgsystem', function(result) {
    	if (result === false) {
            console.log('renderUIVol(): No data returned from readcfgsystem');
    	}
        else {
            SESSION.json = result;
        }

    	// Fixed volume (0dB output)
    	if (SESSION.json['mpdmixer'] == 'none') {
    		disableVolKnob();
    	}
    	// Software or hardware volume
    	else {
    		// Sync moOde's displayed volume to that on a UPnP control point app
            // NOTE: This hack is necessary because upmpdcli set's MPD volume directly and does not use vol.sh
    		if (SESSION.json['feat_bitmask'] & FEAT_UPNPSYNC) {
    			// No renderers active
    			if (SESSION.json['btactive'] == '0' && SESSION.json['aplactive'] == '0' && SESSION.json['spotactive'] == '0'
                    && SESSION.json['slsvc'] == '0' && SESSION.json['rbsvc'] == '0') {
    				if ((SESSION.json['volknob'] != MPD.json['volume']) && SESSION.json['volmute'] == '0') {
    					SESSION.json['volknob'] = MPD.json['volume']
                        $.post('command/moode.php?cmd=updcfgsystem', {'volknob': SESSION.json['volknob']});
    				}
    			}
    		}

    		// Update volume knobs
    		$('#volume').val(SESSION.json['volknob']).trigger('change');
    		$('.volume-display div, #inpsrc-preamp-volume, #playbar-volume-level').text(SESSION.json['volknob']);
            $('.volume-display-db').text(SESSION.json['volume_db_display'] == '1' ? MPD.json['mapped_db_vol'] : '');
    		$('#volume-2').val(SESSION.json['volknob']).trigger('change');
    		$('#mvol-progress').css('width', SESSION.json['volknob'] + '%');

    	   	// Update mute state
    		if (SESSION.json['volmute'] == '1') {
    			$('.volume-display div, #inpsrc-preamp-volume').text('mute');
                $('#playbar-volume-level').text('x');
    		}
    		else {
    			$('.volume-display div, #playbar-volume-level').text(SESSION.json['volknob']);
    		}
    	}
    });
}

function loadDummyData(title, artist, album, coverurl) {
  console.log("Rendererlib.js - Page loaded - Metadata=");

  SESSION.json['volmute'] = '1';
  SESSION.json['mpdmixer'] = 'hardware'

  var moreArtistsEllipsis = "...";
  var albumartist = "My album artist";

  // Playback
  $('#coverart-url').html('<img class="coverart" ' + 'src="' + coverurl + '" ' + 'data-adaptive-background="1" alt="Cover art not found"' + '>');
  $('#currentalbum-div').show();
  $('#currentalbum').html(album);
  $('#currentsong').html(genSearchUrl(artist == 'Unknown artist' ? albumartist : artist, title, album));
  $('#currentartist').html((artist == 'Unknown artist' ? albumartist : artist) + moreArtistsEllipsis);

  // Playbar
  $('#playbar-currentsong, #ss-currentsong').html((artist == 'Unknown artist' ? albumartist : artist) + moreArtistsEllipsis + ' - ' + title);
  $('#playbar-currentalbum, #ss-currentalbum').html(album);

  // Show playback-controls
  $('#playback-panel').css('display', '');
  $('#playback-controls').css('display', '');
  $('#config-tabs').css('display', 'none');
  $('#mbrand').css('display', 'none');
  $('#menu-settings').css('display', 'none');

  // SESSION.json['volknob']
  console.log("Rendererlib.js - Session=" + JSON.stringify(SESSION.json));
  renderUIVolRenderer();
}

function setAutoRefresh(t) {
  setTimeout("location.reload(true);", t);
}
