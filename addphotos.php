<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

require_once('../../../wp-load.php');
require_once('phpFlickr.php');
 
if (!is_user_logged_in() && !current_user_can('edit_posts')) {
  die('Please login as an editor');
}

if (!is_uploaded_file($_FILES['gpx']['tmp_name'])) {
  die('No GPX track supplied');
}

$options = get_option('gpx-settings');
if (!$options['flickr-key']) {
  die("No Flickr API key provided.");
}

require('gpx.php');

$gpx = gpx_load_file($_FILES['gpx']['tmp_name']);

$bounds = $gpx->getBounds();

$bounds['time']['min']->modify('-1 day');
$bounds['time']['max']->modify('+2 days');

$args = array(
  'min_taken_date' => $bounds['time']['min']->format('Y-m-d'),
  'max_taken_date' => $bounds['time']['max']->format('Y-m-d'),
  'bbox' => $bounds['longitude']['min'] . "," . $bounds['latitude']['min'] . "," . $bounds['longitude']['max'] . "," . $bounds['latitude']['max']
);

if ($_POST['flickrid']) {
  $args['user_id'] = $_POST['flickrid'];
}

$f = new phpFlickr($options['flickr-key']);

$results = $f->photos_search($args);

header('Content-Type: text/plain');

foreach ($results['photo'] as $photo) {
  $photo = $f->photos_getInfo($photo['id'], $photo['secret']);

  $url = 'http://www.flickr.com/photos/' . $photo['owner']['nsid'] . '/' . $photo['id'];
  $thumbnail = 'http://farm' . $photo['farm'] . '.staticflickr.com/' . $photo['server'] . '/' . $photo['id'] . '_' . $photo['secret'] . '_m.jpg';
  $title = $photo['title'];
  $long = $photo['location']['longitude'];
  $lat = $photo['location']['latitude'];

  $pt = $gpx->addWaypoint($long, $lat);
  $pt->setName($title);
  $pt->setLink($url);
  $pt->setThumbnail($thumbnail);
}

echo $gpx;

?>
