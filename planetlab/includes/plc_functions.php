<?php

// $Id$

// will trash this eventually
require_once 'plc_functions_trash.php';

//////////////////////////////////////////////////////////// validation functions
function topdomain ($hostname) {
  $exploded=array_reverse(explode(".",$hostname));
  return $exploded[0];
}

function is_valid_email_addr ($email) {
  if (ereg("^.+@.+\\..+$", $email) ) {
    return true;
  } else {
    return false;
  }
}

function is_valid_url ($url) {
  if (ereg("^(http|https)://.+\..+$", strtolower($url) ) ) {
    return true;
  } else {
    return false;
  }
}

function is_valid_ip ($ip) {
  if (ereg("^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$", $ip ) ) {
      // it's at least in the right format, now check to see if
      // each part is equal to less than 255
      $parts= explode( '.', $ip );
      $count= count($parts);

      for( $i= 0; $i < $count; $i++ ) {
	if( intval($parts[$i]) > 255 )
	  return false;
      }

      return true;
  } else {
    return false;
  }
}

function is_valid_network_addr($network_addr,$mask) {
  $lNetwork= ip2long($network_addr);
  $lMask= ip2long($mask);

  // are they the correct format?
  if( $lNetwork == -1 || $lMask == -1 )
    return false;

  // is network address valid for the mask?
  if( ($lNetwork & $lMask) != $lNetwork )
    return false;

  return true;
}


// returns whether or not a network address is in the reserved space
// in the case of a invalid network address, false will be returned.
function is_reserved_network_addr($network_addr) {
  $lNetwork= ip2long($network_addr);

  if( $lNetwork == -1 )
    return false;

  // does the network address fall in a reserved block?
  $reserved_ips = array (
			 array('10.0.0.0','10.255.255.255'),
			 array('172.16.0.0','172.31.0.0'),
			 array('192.168.0.0','192.168.255.0')
			 );
  foreach ($reserved_ips as $r) {
    $min = ip2long($r[0]);
    $max = ip2long($r[1]);
      
    if (($lNetwork >= $min) && ($lNetwork <= $max))
      return true;
  }

  return false;
}

////////////////////////////////////////////////////////////  peerscopes
function plc_peer_info ($api,$peerscope) {
  switch ($_GET['peerscope']) {
  case '':
    $peer_filter=array();
    $peer_label="all peers";
    break;
  case 'local':
    $peer_filter=array("peer_id"=>NULL);
    $peer_label=PLC_SHORTNAME;
    break;
  case 'foreign':
    $peer_filter=array("~peer_id"=>NULL);
    $peer_label="foreign peers";
    break;
  default:
    $peer_id=intval($_GET['peerscope']);
    $peer_filter=array("peer_id"=>$peer_id);
    $peer=$api->GetPeers(array("peer_id"=>$peer_id));
    $peer_label='peer "' . $peer[0]['peername'] . '"';
    break;
  }
  return array ($peer_filter,$peer_label);
}

//////////////////////////////////////////////////////////// links    
function href ($url,$text) { return "<a href='" . $url . "'>" . $text . "</a>"; }

function l_nodes () { return "/db/nodes/newindex.php"; }
function l_node_u ($node_id) { return "/db/nodes/node.php?id=" . $node_id; }
function l_node ($node_id) { return href (l_node_u($node_id),$node_id); }
function l_node2 ($node_id,$text) { return href (l_node_u($node_id),$text); }

function l_sites () { return "/db/sites/index.php"; }
function l_site_u ($site_id) { return "/db/persons/index.php?id=" . $site_id; }
function l_site ($site_id) { return href (l_site_u($site_id),$site_id); }
function l_site2 ($site_id,$text) { return href (l_site_u($site_id),$text); }

function l_slices () { return "/db/slices/index.php"; }
function l_slice_u ($slice_id) { return "/db/persons/index.php?id=" . $slice_id; }
function l_slice ($slice_id) { return href (l_slice_u($slice_id),$slice_id); }
function l_slice2 ($slice_id,$text) { return href (l_slice_u($slice_id),$text); }

function l_persons () { return "/db/persons/index.php"; }
function l_person_u ($person_id) { return "/db/persons/index.php?id=" . $person_id; }
function l_person ($person_id) { return href (l_person_u($person_id),$person_id); }
function l_person2 ($person_id,$text) { return href (l_person_u($person_id),$text); }

function l_interfaces () { return "/db/interfaces/index.php"; }
function l_interface_u ($interface_id) { return "/db/interfaces/index.php?id=" . $interface_id; }
function l_interface ($interface_id) { return href (l_interface_u($interface_id),$interface_id); }
function l_interface2 ($interface_id,$text) { return href (l_interface_u($interface_id),$text); }

function l_event ($type,$param,$id) { return '/db/events/index.php?type=' . $type . '&' . $param . '=' . $id; }

//////////////////////////////////////////////////////////// titles
function t_site($site) { return " on site " . $site['name'] . " (" . $site['login_base'] .")"; }
function t_slice ($slice) { return " running slice " . $slice['name'] . " (" . $slice['slice_id'] . ")"; }

//////////////////////////////////////////////////////////// nav tabs
function tabs_node($node) { return array('Node ' . $node['hostname']=>l_node_u($node_id)); }
function tabs_site($site) { return array('Site ' . $site['name']=>l_site_u($site_id)); }
function tabs_slice($slice) { return array('Slice ' . $slice['name']=>l_slice_u($slice_id)); }

//////////////////////////////////////////////////////////// presentation
// builds a table from an array of strings, with the given class
function plc_make_table ($class, $messages) {
  // pretty print the cell
  $formatted = "";
  if (! empty ($messages)) {
    $formatted="<table class='" . $class . "'>";
    foreach ($messages as $message) {
      $formatted .= "<tr><td>" . $message . "</td></tr>";
    }
    $formatted .= "</table>";
  }
  return $formatted;
}

// attempt to normalize the delete buttons and confirmations
function plc_delete_button($width=15) {
  return '<span title="Delete this entry"><img width=' . $width . ' alt="Delete this entry" src="/planetlab/icons/delete.png"></span>';
}

function plc_js_confirm($message) {
  return "onclick=\"javascript:return confirm('Are you sure you want to delete " . $message . " ?')\"";
}

function plc_delete_link($url,$delete_message,$visible) {
  return "<a href='" . $url . "' " . plc_js_confirm($delete_message) . ">" . $visible . "</a>";
}

function plc_delete_link_button($url,$delete_message,$width=15) {
  return "<a href='" . $url . "' " . plc_js_confirm($delete_message) . ">" . plc_delete_button($width) . "</a>";
}

function plc_event_button($type,$param,$id) {
  return '<a href="' . l_event($type,$param,$id) . '"> <span title="Related events"> <img src="/planetlab/icons/event.png" width=18></span></a>';
}

function plc_comon_button ($field, $value,$target="") {
  $result='<a ';
  if (!empty($target)) {
    $result.='target="' . $target . '" ';
  }
  $result.='href="/db/nodes/comon.php?' . $field . "=" . $value . '">';
  $result.='<span title="Link to Comon"> <img src="/planetlab/icons/comon.png" width="18"></span></a>';
  return $result;
}

////////////////////////////////////////////////////////////
function plc_error ($text) {
  // should use the same channel as the php errors..
  print "<div class='plc-warning'> Error " . $text . "</div>";
}

function plc_warning ($text) {
  print "<div class='plc-warning'> Warning " . $text . "</div>";
}

// shows a php variable verbatim with a heading message
function plc_debug ($message,$object) {
  print "<br>" . $message . "<pre>";
  print_r ($object);
  print "</pre>";
}

?>
