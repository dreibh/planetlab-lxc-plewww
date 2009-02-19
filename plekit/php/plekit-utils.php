<?php

// $Id: plc_functions.php 12096 2009-02-18 22:05:34Z thierry $

// returns array ['url' => path, 'values' => hash (key=>value)* ]
function plekit_split_url ($full_url) {
  list($url,$args) = explode("?",$full_url);
  $values=array();
  if ($args) {
    $pairs=explode("&",$args);
    foreach ($pairs as $pair) {
      list ($name,$value) = explode("=",$pair);
      $values[$name]=$value;
    }
  }
  return array("url"=>$url,"values"=>$values);
}

?>
