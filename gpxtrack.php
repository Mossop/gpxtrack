<?php
/**
 * @package GPX_Track
 * @version 1.0
 */
/*
Plugin Name: GPX Track
Plugin URI: http://www.fractalbrew.com
Description: Display GPX tracks in blog posts
Author: Dave Townsend
Version: 1.0
Author URI: http://www.oxymoronical.com/
*/

/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

function gpx_display_track($args) {
  $url = $args['url'];
  return '<iframe src="' . plugins_url('track.php', __FILE__) . '?url=' . urlencode($url) . '" style="width: 600px; height: 450px; margin: auto"></iframe>';
}

add_shortcode('gpx', 'gpx_display_track');

add_action('admin_menu', 'gpx_tools_menu');

function gpx_tools_menu() {
  add_submenu_page('tools.php', 'Generate GPX File', 'Generate GPX Track', 'edit_posts', 'gpx-track-tools', 'gpx_track_tools');
  add_options_page('GPX Track', 'GPX Track', 'manage_options', 'gpx-track-options', 'gpx_track_options');
  add_action('admin_init', 'register_gpxsettings' );
}

function register_gpxsettings() {
  register_setting('gpx-settings', 'gpx-settings');

  add_settings_section(
    'gpx-settings',
    'Settings',
    'print_section_info',
    'gpx-track-options'
  );  
    
  add_settings_field(
    'flickr-key', 
    'Flickr API Key', 
    'print_flickr_key_field', 
    'gpx-track-options',
    'gpx-settings'
  );
}

function print_section_info(){
  print 'Settings:';
}

function print_flickr_key_field($args) {
  $options = get_option('gpx-settings');
  echo '<input type="text" id="flickr-key" name="gpx-settings[flickr-key]" value="' . $options['flickr-key'] . '" />';
}

function gpx_track_tools() {
?>
<div class="wrap">
<?php screen_icon(); ?>
<h2>Generate GPX File</h2>
<p>Upload a GPX file and we'll add photos from the area that are listed at <a href="http://flickr.com">Flickr</a> as waypoints.</p>
<p>If you enter your Flickr ID then we'll only include photos from your account.</p>
<form method="post" enctype="multipart/form-data" action="<?php echo plugins_url('addphotos.php', __FILE__); ?>" target="_blank"> 
  <table class="form-table">
    <tr valign="top">
      <th scope="row">GPX File</th>
      <td><input type="file" name="gpx" /></td>
    </tr>
    <tr valign="top">
      <th scope="row">Your Flickr ID (optional)</th>
      <td><input type="text" name="flickrid" /></td>
    </tr>
  </table>
  <?php submit_button('Create'); ?>
</form>
</div>
<?php
}

function gpx_track_options() {
?>
<div class="wrap">
<?php screen_icon(); ?>
<h2>GPX Track Options</h2>
<form method="post" action="options.php">
  <?php settings_fields('gpx-settings'); ?>
  <?php do_settings_sections('gpx-track-options'); ?>
  <?php submit_button(); ?>
</form>
</div>
<?php
}
?>