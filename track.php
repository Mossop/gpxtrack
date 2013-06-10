<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

require_once('gpx.php');

$url = $_GET['url'];
$gpx = gpx_load_file($url);
$bounds = $gpx->getBounds();

?><!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
  <meta charset="utf-8">
  <style>
    html, body, #map-canvas {
      margin: 0;
      padding: 0;
      height: 100%;
    }
  </style>
  <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&amp;sensor=false"></script>
  <script>
var map;
function initialize() {
  var sw = new google.maps.LatLng(<?= $bounds['latitude']['min'] ?>, <?= $bounds['longitude']['min'] ?>);
  var ne = new google.maps.LatLng(<?= $bounds['latitude']['max'] ?>, <?= $bounds['longitude']['max'] ?>);
  var bounds = new google.maps.LatLngBounds(sw, ne);
  var options = {
    zoom: 8,
    center: bounds.getCenter(),
    mapTypeId: google.maps.MapTypeId.TERRAIN,
    panControl: false,
    streetViewControl: false
  };
  map = new google.maps.Map(document.getElementById('map-canvas'), options);
  map.fitBounds(bounds);
<?php

function draw_line($route) {
?>

  (new google.maps.Polyline({
    path: [
<?php
  foreach ($route->getPoints() as $point) {
    echo '      new google.maps.LatLng(' . $point->getLatitude() . ', ' . $point->getLongitude() ."),\n";
  }
?>
    ],
    strokeColor: '#0000FF',
    strokeOpacity: 1.0,
    strokeWeight: 2
  })).setMap(map);
<?php
}

foreach ($gpx->getTracks() as $track) {
  foreach ($track->getSegments() as $segment) {
    draw_line($segment);
  }
}

foreach ($gpx->getRoutes() as $route) {
  draw_line($route);
}

?>
}

google.maps.event.addDomListener(window, 'load', initialize);
  </script>
</head>
<body>
  <div id="map-canvas"></div>
</body>
</html>
