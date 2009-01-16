<?php

// $Id$

// person sort on last name, first name, email
function __cmp_persons($a, $b) {
  $persona = $a['last_name'] . $a['first_name'] . $a['email'];
  $personb = $b['last_name'] . $b['first_name'] . $b['email'];
  return strcmp($persona, $personb);
}

function sort_persons(&$persons) {
  return usort($persons, "__cmp_persons");
}

function __cmp_nodes($a, $b) {
 $as = array_reverse(explode(".", $a['hostname']));
 $bs = array_reverse(explode(".", $b['hostname']));
 foreach ($as as $key => $val) {
   if ($val == $bs[$key]) {
     continue;
   }
   return ($val < $bs[$key]) ? -1 : 1;
 }
}

function sort_nodes(&$nodes) {
  return usort($nodes, "__cmp_nodes");
}

// node group sort on name
function __cmp_nodegroups($a, $b) {
	return strcasecmp($a['name'], $b['name']);
}

function sort_nodegroups(&$nodegroups) {
	return usort($nodegroups, "__cmp_nodegroups");
}

// site sort on name
function __cmp_sites($a, $b) {
  return strcasecmp($a['name'], $b['name']);
}

function sort_sites(&$sites) {
  return usort($sites, "__cmp_sites");
}

// slice sort on name
function __cmp_slices($a, $b) {
  return strcasecmp($a['name'], $b['name']);
}

function sort_slices(&$slices) {
  return usort($slices, "__cmp_slices");
}

function __cmp_peers($a,$b) {
  return strcmp($a['peername'],$b['peername']);
}

function sort_peers (&$peers) {
  return usort ($peers, "__cmp_peers");
}

function __cmp_interface_tags($a,$b) {
  $cat=strcmp($a['category'],$b['category']);
  if ($cat != 0) {
    return $cat;
  } else {
    return strcmp($a['name'],$b['name']);
  }
}

function sort_interface_tags (&$interface_tags) {
  return usort ($interface_tags,"__cmp_interface_tags");
}

?>
