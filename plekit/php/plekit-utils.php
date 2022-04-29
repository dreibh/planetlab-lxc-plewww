<?php

// $Id$

// returns array ['url' => path, 'values' => hash (key=>value)* ]
function plekit_split_url ($full_url) {
  $exploded = explode("?", $full_url);
  $url = $exploded[0];
  $values = array();
  if (get_array($exploded, 1)) {
    $args = $exploded[1];
    $pairs = explode("&", $args);
    foreach ($pairs as $pair) {
      list ($name, $value) = explode("=", $pair);
      $values[$name] = $value;
    }
  }
  return array("url"=>$url, "values"=>$values);
}

// at first I thought $_GET was an object, but it's an array
function get_object($object, $prop, $default=null) {
  if (property_exists($object, $prop)) {
    return $object->$prop;
  } else {
    return $default;
  }
}

function get_array($array, $prop, $default=null) {
  if (! $array)
    return $default;
  if (array_key_exists($prop, $array)) {
    return $array[$prop];
  } else {
    return $default;
  }
}

function get_array2($array, $prop1, $prop2, $default=null) {
  return get_array(get_array($array, $prop1), $prop2, $default);
}

?>
