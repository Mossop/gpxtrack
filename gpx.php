<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

define('NS_GPX', 'http://www.topografix.com/GPX/1/1');
define('NS_GPT', 'http://www.fractalbrew.com.com/GPT/1/0');

function resolve_bound($b1, $b2) {
  $result = array(
    'min' => $b1['min'],
    'max' => $b1['max']
  );

  if ($b1['min'] === NULL) {
    $result['min'] = $b2['min'];
  }
  else if ($b2['min'] !== NULL) {
    $result['min'] = min($b1['min'], $b2['min']);
  }

  if ($b1['max'] === NULL) {
    $result['max'] = $b2['max'];
  }
  else if ($b2['max'] !== NULL) {
    $result['max'] = max($b1['max'], $b2['max']);
  }

  return $result;
}

function resolve_bounds($b1, $b2) {
  $result = array();
  $result['longitude'] = resolve_bound($b1['longitude'], $b2['longitude']);
  $result['latitude'] = resolve_bound($b1['latitude'], $b2['latitude']);
  $result['time'] = resolve_bound($b1['lattimeitude'], $b2['time']);
  return $result;
}

class DOMWrapper {
  protected $xml = NULL;
  protected $schema = array();

  public function __construct($xml) {
    $this->xml = $xml;
  }

  public function delete() {
    $this->xml->removeChild($this->xml);
  }

  protected function getChildren($name, $namespace = NS_GPX) {
    $children = array();
    $node = $this->xml->firstChild;

    while ($node) {
      if (($node->nodeType == XML_ELEMENT_NODE) && ($node->namespaceURI == $namespace) && ($node->localName == $name)) {
        array_push($children, $node);
      }

      $node = $node->nextSibling;
    }

    return $children;
  }

  protected function getExtension($namespace, $name) {
    $children = $this->getChildren('extensions');
    if (count($children) == 0) {
      return NULL;
    }

    $extensions = new Extensions($children[0]);
    return $extensions->getValue($name, $namespace);
  }

  protected function setExtension($namespace, $name, $value) {
    $children = $this->getChildren('extensions');
    if (count($children) == 0) {
      $ext = $this->xml->ownerDocument->createElementNS(NS_GPX, 'extensions');
      $this->append($ext);
      $extensions = new Extensions($ext);
    }
    else {
      $extensions = new Extensions($children[0]);
    }

    return $extensions->setValue($name, $value, $namespace);
  }

  protected function getValue($name, $namespace = NS_GPX) {
    $values = $this->getChildren($name, $namespace);

    if (count($values) == 0) {
      return NULL;
    }

    return $values[0]->textContent;
  }

  protected function setValue($name, $value, $namespace = NS_GPX) {
    $values = $this->getChildren($name, $namespace);
    foreach ($values as $value) {
      $this->xml->removeChild($value);
    }

    $node = $this->xml->ownerDocument->createElementNS($namespace, $name);
    $node->appendChild($this->xml->ownerDocument->createTextNode($value));
    $this->append($node);
  }

  protected function wrapChildren($name, $class, $namespace = NS_GPX) {
    $children = $this->getChildren($name);

    $results = array();
    foreach ($children as $child) {
      array_push($results, new $class($child));
    }

    return $results;
  }

  protected function append($element) {
    $pos = array_search($element->localName, $this->schema);

    if ($pos === FALSE) {
      throw new Exception("Attempting to add invalid " . $element->localName . " element to schema " . count($this->schema));
    }

    $node = $this->xml->lastChild;
    while ($node) {
      if ($node->nodeType == XML_ELEMENT_NODE && $node->namespaceURI == NS_GPX) {
        $index = array_search($node->localName, $this->schema);
        if ($index !== FALSE && $index <=$pos) {
          $this->xml->insertBefore($element, $node->nextSibling);
          return;
        }
      }

      $node = $node->previousSibling;
    }

    $this->xml->insertBefore($element, $this->xml->firstChild);
  }
}

class Extensions extends DOMWrapper {
  protected function append($element) {
    $this->xml->appendChild($element);
  }
}

class NamedWrapper extends DOMWrapper {
  public function getName() {
    return $this->getValue('name');
  }

  public function setName($value) {
    $this->setValue('name', $value);
  }

  public function getDescription() {
    return $this->getValue('desc');
  }

  public function setDescription($value) {
    $this->setValue('desc', $value);
  }

  public function getLink() {
    return $this->getValue('link');
  }

  public function setLink($value) {
    $this->setValue('link', $value);
  }
}

class Waypoint extends NamedWrapper {
  protected $schema = array('ele', 'time', 'magvar', 'geoidheight', 'name', 'cmt', 'desc', 'src', 'link', 'sym', 'type', 'fix', 'sat', 'hdop', 'vdop', 'pdop', 'ageofdgpsdata', 'dgpsid', 'extensions');

  public static function create($doc, $type, $long, $lat) {
    $xml = $doc->createElementNS(NS_GPX, $type);
    $xml->setAttribute("lat", $lat);
    $xml->setAttribute("lon", $long);
    return new Waypoint($xml);
  }

  public function getBounds() {
    return array(
      'longitude' => array(
        'min' => $this->getLongitude(),
        'max' => $this->getLongitude(),
       ),
      'latitude' => array(
        'min' => $this->getLatitude(),
        'max' => $this->getLatitude(),
       ),
      'time' => array(
        'min' => $this->getTime(),
        'max' => $this->getTime()
       )
    );
  }

  public function getLatitude() {
    return $this->xml->getAttribute('lat');
  }

  public function getLongitude() {
    return $this->xml->getAttribute('lon');
  }

  public function getTime() {
    $time = $this->getValue('time');
    return new DateTime($time);
  }

  public function getThumbnail() {
    return $this->getExtension(NS_GPT, 'thumbnail');
  }

  public function setThumbnail($value) {
    $this->setExtension(NS_GPT, 'thumbnail', $value);
  }
}

class Track extends NamedWrapper {
  protected $schema = array('name', 'cmt', 'desc', 'src', 'link', 'number', 'type', 'extensions', 'trkseg');

  public static function create($doc) {
    return new Track($doc->createElementNS(NS_GPX, 'trk'));
  }

  public function getBounds() {
    $bounds = array(
      'longitude' => array(
        'min' => NULL,
        'max' => NULL,
       ),
      'latitude' => array(
        'min' => NULL,
        'max' => NULL,
       ),
      'time' => array(
        'min' => NULL,
        'max' => NULL
       )
    );

    $segments = $this->getSegments();
    foreach ($segments as $segment) {
      $newbounds = $segment->getBounds();
      $bounds = resolve_bounds($bounds, $newbounds);
    }

    return $bounds;
  }

  public function getSegments() {
    return $this->wrapChildren('trkseg', 'TrackSegment');
  }

  public function addSegment() {
    $seg = TrackSegment::create($this->xml->ownerDocument);
    $this->append($seg->xml);
    return $seg;
  }
}

class TrackSegment extends DOMWrapper {
  protected $schema = array('trkpt', 'extensions');

  public static function create($doc) {
    return new TrackSegment($doc->createElementNS(NS_GPX, 'trkseg'));
  }

  public function getBounds() {
    $bounds = array(
      'longitude' => array(
        'min' => NULL,
        'max' => NULL,
       ),
      'latitude' => array(
        'min' => NULL,
        'max' => NULL,
       ),
      'time' => array(
        'min' => NULL,
        'max' => NULL
       )
    );

    $points = $this->getPoints();
    foreach ($points as $point) {
      $newbounds = $point->getBounds();
      $bounds = resolve_bounds($bounds, $newbounds);
    }

    return $bounds;
  }

  public function getPoints() {
    return $this->wrapChildren('trkpt', 'Waypoint');
  }

  public function addPoint($long, $lat) {
    $pt = Waypoint::create($this->xml->ownerDocument, 'trkpt', $long, $lat);
    $this->append($pt->xml);
    return $pt;
  }
}

class Route extends NamedWrapper {
  protected $schema = array('name', 'cmt', 'desc', 'src', 'link', 'number', 'type', 'extensions', 'rtept');

  public static function create($doc) {
    return new Route($doc->createElementNS(NS_GPX, 'rte'));
  }

  public function getBounds() {
    $bounds = array(
      'longitude' => array(
        'min' => NULL,
        'max' => NULL,
       ),
      'latitude' => array(
        'min' => NULL,
        'max' => NULL,
       ),
      'time' => array(
        'min' => NULL,
        'max' => NULL
       )
    );

    $points = $this->getPoints();
    foreach ($points as $point) {
      $newbounds = $point->getBounds();
      $bounds = resolve_bounds($bounds, $newbounds);
    }

    return $bounds;
  }

  public function getPoints() {
    return $this->wrapChildren('rtept', 'Waypoint');
  }

  public function addPoint($long, $lat) {
    $pt = Waypoint::create($this->xml->ownerDocument, 'rtept', $long, $lat);
    $this->append($pt->xml);
    return $pt;
  }
}

class GPX extends DOMWrapper {
  protected $schema = array('metadata', 'wpt', 'rte', 'trk', 'extensions');

  private $doc;

  public function __construct($doc) {
    parent::__construct($doc->documentElement);
    $this->doc = $doc;

    if ($this->xml->localName != "gpx" || $this->xml->namespaceURI != NS_GPX) {
      throw new Exception("Invalid GPX file");
    }
  }

  public function __toString() {
    return $this->doc->saveXML();
  }

  public function getBounds() {
    $bounds = array(
      'longitude' => array(
        'min' => NULL,
        'max' => NULL,
       ),
      'latitude' => array(
        'min' => NULL,
        'max' => NULL,
       ),
      'time' => array(
        'min' => NULL,
        'max' => NULL
       )
    );

    $items = $this->getWaypoints();
    foreach ($items as $item) {
      $newbounds = $item->getBounds();
      $bounds = resolve_bounds($bounds, $newbounds);
    }

    $items = $this->getTracks();
    foreach ($items as $item) {
      $newbounds = $item->getBounds();
      $bounds = resolve_bounds($bounds, $newbounds);
    }

    $items = $this->getRoutes();
    foreach ($items as $item) {
      $newbounds = $item->getBounds();
      $bounds = resolve_bounds($bounds, $newbounds);
    }

    return $bounds;
  }

  public function getWaypoints() {
    return $this->wrapChildren('wpt', 'Waypoint');
  }

  public function addWaypoint($long, $lat) {
    $pt = Waypoint::create($this->doc, 'wpt', $long, $lat);
    $this->append($pt->xml);
    return $pt;
  }

  public function getRoutes() {
    return $this->wrapChildren('rte', 'Route');
  }

  public function addRoute() {
    $rt = Route::create($this->doc);
    $this->append($rt->xml);
    return $rt;
  }

  public function getTracks() {
    return $this->wrapChildren('trk', 'Track');
  }

  public function addTrack() {
    $trk = Track::create($this->doc);
    $this->append($trk->xml);
    return $trk;
  }
}

function gpx_load_file($file) {
  $doc = new DOMDocument();
  $doc->load($file);
  return new GPX($doc);
}

?>
