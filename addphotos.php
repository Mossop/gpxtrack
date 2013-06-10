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

$minlon = NULL;
$maxlon = NULL;
$minlat = NULL;
$maxlat = NULL;
$mintime = NULL;
$maxtime = NULL;

$waypoints = $gpx->getWaypoints();
$routes = $gpx->getRoutes();
$tracks = $gpx->getTracks();

function process_point($point) {
  global $minlon, $minlat, $maxlon, $maxlat, $mintime, $maxtime;

  $lon = $point->getLongitude();
  $lat = $point->getLatitude();
  $time = $point->getTime();

  if ($minlon === NULL) {
    $minlon = $lon;
    $maxlon = $lon;
    $minlat = $lat;
    $maxlat = $lat;
    if ($time !== NULL) {
      $mintime = $time;
      $maxtime = $time;
    }
    return;
  }

  $minlon = min($minlon, $lon);
  $maxlon = max($maxlon, $lon);
  $minlat = min($minlat, $lat);
  $maxlat = max($maxlat, $lat);
  if ($time !== NULL) {
    $mintime = min($mintime, $time);
    $maxtime = max($maxtime, $time);
  }
}

function process_route($route) {
  $points = $route->getPoints();
  foreach ($points as $point) {
    process_point($point);
  }
}

foreach ($routes as $route) {
  process_route($route);
}

foreach ($tracks as $track) {
  $segs = $track->getSegments();

  foreach ($segs as $segment) {
    process_route($segment);
  }
}

$mintime->modify('-1 day');
$maxtime->modify('+2 days');

$args = array(
  'min_taken_date' => $mintime->format('Y-m-d'),
  'max_taken_date' => $maxtime->format('Y-m-d'),
  'bbox' => $minlon . "," . $minlat . "," . $maxlon . "," . $maxlat
);

if ($_POST['flickrid']) {
  $args['user_id'] = $_POST['flickrid'];
}

$f = new phpFlickr($options['flickr-key']);

$results = $f->photos_search($args);

header('Content-Type: text/plain');

foreach ($results['photo'] as $photo) {
  $photo = $f->photos_getInfo($photo['id'], $photo['secret']);

  $url = 'http://www.flickr.com/photos/' . $photo['owner'] . '/' . $photo['id'];
  $thumbnail = 'http://farm' . $photo['farm'] . '.staticflickr.com/' . $photo['server'] . '/' . $photo['id'] . '_' . $photo['secret'] . '_t.jpg';
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
