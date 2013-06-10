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
function addWaypoint(map, latlng, name, thumbnail, link) {
  var infowindow = new google.maps.InfoWindow({
    content: '<div id="content">\n' +
'  <h3>' + name + '</h3>\n' +
'  <p style="text-align: center"><a href="' + link + '" target="_blank"><img src="' + thumbnail + '"></a></p>\n' +
'</div>'
  });

  var marker = new google.maps.Marker({
    position: latlng,
    map: map,
    title: name
  });

  google.maps.event.addListener(marker, 'click', function() {
    infowindow.open(map, marker);
  });
}

function initialize() {
  var sw = new google.maps.LatLng(<?= (float)$bounds['latitude']['min'] ?>, <?= (float)$bounds['longitude']['min'] ?>);
  var ne = new google.maps.LatLng(<?= (float)$bounds['latitude']['max'] ?>, <?= (float)$bounds['longitude']['max'] ?>);
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
    echo '      new google.maps.LatLng(' . (float)$point->getLatitude() . ', ' . (float)$point->getLongitude() ."),\n";
  }
?>
    ],
    strokeColor: '#0000FF',
    strokeOpacity: 1.0,
    strokeWeight: 2
  })).setMap(map);
<?php
}

function jsize($str) {
  if ($str) {
    return '"' . addslashes($str) . '"';
  }

  return 'null';
}

foreach ($gpx->getTracks() as $track) {
  foreach ($track->getSegments() as $segment) {
    draw_line($segment);
  }
}

foreach ($gpx->getRoutes() as $route) {
  draw_line($route);
}

foreach ($gpx->getWaypoints() as $point) {
  $name = $point->getName();
  $thumb = $point->getThumbnail();
  $link = $point->getLink();

  if (!$name && !$thumb) {
    continue;
  }

  $name = jsize($name);
  $thumb = jsize($thumb);
  $link = jsize($link);
?>
  addWaypoint(map, new google.maps.LatLng(<?= (float)$point->getLatitude() ?>, <?= (float)$point->getLongitude() ?>),
              <?= $name ?>, <?= $thumb ?>, <?= $link ?>);
<?php
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
