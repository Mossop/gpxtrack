<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

define('NS_GPX', 'http://www.topografix.com/GPX/1/1');

class DOMWrapper {
  protected $xml = NULL;
  protected $schema = array();

  public function __construct($xml) {
    $this->xml = $xml;
  }

  public function delete() {
    $this->xml->removeChild($this->xml);
  }

  protected function getChildren($name, $namespace = NS_GPX, $xml = NULL) {
    if ($xml === NULL) {
      $xml = $this->xml;
    }

    $children = array();
    $node = $xml->firstChild;

    while ($node) {
      if (($node->nodeType == XML_ELEMENT_NODE) && ($node->namespaceURI == $namespace) && ($node->localName == $name)) {
        array_push($children, $node);
      }

      $node = $node->nextSibling;
    }

    return $children;
  }

  protected function getValue($name) {
    $values = $this->getChildren($name);

    if (count($values) == 0) {
      return "";
    }

    return $values[0]->textContent;
  }

  protected function setValue($name, $value) {
    $values = $this->getChildren($name);
    foreach ($values as $value) {
      $this->xml->removeChild($value);
    }

    $value = new DOMElement($name, NS_GPX, $value);
    $this->append($value);
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
      throw new Exception("Attempting to add invalid element");
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

class NamedWrapper extends DOMWrapper {
  public function getName() {
    return $this->getValue('name');
  }

  public function getDescription() {
    return $this->getValue('desc');
  }

  public function getLink() {
    return $this->getValue('link');
  }
}

class Waypoint extends NamedWrapper {
  protected $schema = array('ele', 'time', 'magvar', 'geoidheight', 'name', 'cmt', 'desc', 'src', 'link', 'sym', 'type', 'fix', 'sat', 'hdop', 'vdop', 'pdop', 'ageofdgpsdata', 'dgpsid', 'extensions');

  public static function create($type, $long, $lat) {
    $xml = new DOMElement($type, NULL, NS_GPX);
    $xml->setAttribute("lat", $lat);
    $xml->setAttribute("lon", $long);
    return new Waypoint($xml);
  }

  public function getLatitude() {
    return $this->xml->getAttribute("lat");
  }

  public function getLongitude() {
    return $this->xml->getAttribute("lon");
  }

  public function getTime() {
    $time = $this->getValue('time');
    return new DateTime($time);
  }
}

class Track extends NamedWrapper {
  protected $schema = array('name', 'cmt', 'desc', 'src', 'link', 'number', 'type', 'extensions', 'trkseg');

  public static function create() {
    return new Track(new DOMElement('trk', NULL, NS_GPX));
  }

  public function getSegments() {
    return $this->wrapChildren('trkseg', 'TrackSegment');
  }

  public function addSegment() {
    $seg = TrackSegment::create();
    $this->append($seg);
  }
}

class TrackSegment extends DOMWrapper {
  protected $schema = array('trkpt', 'extensions');

  public static function create() {
    return new TrackSegment(new DOMElement('trkseg', NULL, NS_GPX));
  }

  public function getPoints() {
    return $this->wrapChildren('trkpt', 'Waypoint');
  }

  public function addPoint($long, $lat) {
    $pt = Waypoint::create('trkpt', $long, $lat);
    $this->append($pt);
  }
}

class Route extends NamedWrapper {
  protected $schema = array('name', 'cmt', 'desc', 'src', 'link', 'number', 'type', 'extensions', 'rtept');

  public static function create() {
  }

  public function getPoints() {
    return $this->wrapChildren('rtept', 'Waypoint');
  }

  public function addPoint($long, $lat) {
    $pt = Waypoint::create('rtept', $long, $lat);
    $this->append($pt);
  }
}

class GPX extends DOMWrapper {
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

  public function getWaypoints() {
    return $this->wrapChildren('wpt', 'Waypoint');
  }

  public function addWaypoint($long, $lat) {
    $pt = Waypoint::create('wpt', $long, $lat);
    $this->append($pt);
  }

  public function getRoutes() {
    return $this->wrapChildren('rte', 'Route');
  }

  public function addRoute() {
    $rt = Route::create('rte');
    $this->append($rt);
  }

  public function getTracks() {
    return $this->wrapChildren('trk', 'Track');
  }

  public function addTrack() {
    $trk = Track::create();
    $this->append($trk);
  }
}

function gpx_load_file($file) {
  $doc = new DOMDocument();
  $doc->load($file);
  return new GPX($doc);
}

?>
