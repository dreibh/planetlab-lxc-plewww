<?php

// $Id$

// will trash this eventually
require_once 'plc_functions_trash.php';

//////////////////////////////////////////////////////////// roles & other checks on global $plc
function plc_is_admin () {
  global $plc;
  return in_array( 10, $plc->person['role_ids']);
}
function plc_is_pi () {
  global $plc;
  return in_array( 20, $plc->person['role_ids']);
}
function plc_is_tech () {
  global $plc;
  return in_array( 40, $plc->person['role_ids']);
}
function plc_in_site ($site_id) {
  global $plc;
  return in_array( $site_id, $plc->person['site_ids']);
}

function plc_my_site_id () {
  global $plc;
  return $plc->person['site_ids'][0];
}

function plc_my_person_id () {
  global $plc;
  return $plc->person['person_id'];
}

//////////////////////////////////////////////////////////// links    
function href ($url,$text) { return "<a href='" . $url . "'>" . $text . "</a>"; }

// naming scheme is
// l_objects()			-> the url to the page that list objects
// l_object($object_id)		-> the url to hte page thas details object with given id
// l_object_t($object_id,text)	-> an <a> tag that shows text and links to the above
// l_object_add ()		-> the url to that object-afding page

function l_nodes ()			{ return "/db/nodes/index.php"; }
function l_node ($node_id)		{ return "/db/nodes/node.php?id=" . $node_id; }
function l_node_t ($node_id,$text)	{ return href (l_node($node_id),$text); }
function l_node_add ()			{ return "/db/nodes/add_node.php"; }
function l_nodes_site ($site_id)	{ return "/db/nodes/index.php?site_id=" . $site_id; }

function l_interface ($interface_id)	{ return "/db/nodes/interfaces.php?id=" . $interface_id; }
function l_interface_t ($interface_id,$text) { 
					  return href (l_interface($interface_id),$text); }
function l_interface_add($node_id)	{ return "/db/nodes/interfaces.php?node_id=" . $node_id; }

function l_sites ()			{ return "/db/sites/index.php"; }
function l_site ($site_id)		{ return "/db/persons/index.php?id=" . $site_id; }
function l_site_t ($site_id,$text)	{ return href (l_site($site_id),$text); }

function l_slices ()			{ return "/db/slices/index.php"; }
function l_slice ($slice_id)		{ return "/db/slices/index.php?id=" . $slice_id; }
function l_slice_t ($slice_id,$text)	{ return href (l_slice($slice_id),$text); }
function l_slice_add ()			{ return "/db/slices/add_slice.php"; }

function l_sliver ($node_id,$slice_id)	{ return "/db/nodes/slivers.php?node_id=" . $node_id. "&slice_id=" . $slice_id; }
function l_sliver_t ($node_id,$slice_id,$text) { 
					  return href (l_sliver($node_id,$slice_id),$text) ; }

function l_persons ()			{ return "/db/persons/index.php"; }
function l_person ($person_id)		{ return "/db/persons/index.php?id=" . $person_id; }
function l_person_t ($person_id,$text)	{ return href (l_person($person_id),$text); }
function l_persons_site ($site_id)	{ return "/db/persons/index.php?site_id=" . $site_id; }

function l_tags ()			{ return "/db/tags/index.php"; }
function l_tags_node ()			{ return "/db/tags/index.php?type=node"; }
# xxx cleanup duplicate pages
#function l_tags_interface ()		{ return "/db/tags/index.php?type=interface"; }
function l_tags_interface ()		{ return "/db/nodes/settings.php"; }
function l_tags_slice ()		{ return "/db/tags/index.php?type=slice"; }

function l_nodegroups ()		{ return "/db/tags/node_groups.php"; }
function l_nodegroup ($nodegroup_id)	{ return "/db/tags/node_groups.php?id=" . $nodegroup_id; }
function l_nodegroup_t ($nodegroup_id,$text) { 
					  return href(l_nodegroup($nodegroup_id),$text); }

function l_events ()			{ return '/db/events/index.php'; }
function l_event ($type,$param,$id)	{ return '/db/events/index.php?type=' . $type . '&' . $param . '=' . $id; }

function l_peers()			{ return "/db/peers/index.php"; }
function l_peer($peer_id)		{ return "/db/peers/index.php?id=" . $peer_id; }

function l_comon($id_name,$id_value)	{ return '/db/nodes/comon.php?' . $id_name . "=" . $id_value; }
function l_sirius()			{ return "/db/sirius/index.php"; }
function l_about()			{ return "/db/about.php"; }
function l_doc_plcapi()			{ return "/db/doc/PLCAPI.php"; }
function l_doc_nmapi()			{ return "/db/doc/NMAPI.php"; }

function l_logout()			{ return "/planetlab/logout.php"; }
function l_sulogout()			{ return "/planetlab/sulogout.php"; }
function l_reset_password()		{ return "/db/persons/reset_password.php"; }
function l_person_register()		{ return "/db/persons/register.php"; }
function l_site_register()		{ return "/db/sites/register.php"; }
function l_site_pending()		{ return "/db/sites/join_request.php"; }
function l_site_status()		{ return "/db/sites/peers.php"; }

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

////////////////////////////////////////////////////////////  peer & peerscopes
// when shortnames are needed on peers
function plc_peer_get_hash ($api) {
  $peer_columns=array('peer_id','shortname');
  $peer_filter=array();
  $peers = $api->GetPeers($peer_filter,$peer_columns);
  
  $peer_hash=array();
  foreach ($peers as $peer) {
    $peer_hash[$peer['peer_id']]=$peer;
  }
}

function plc_peer_shortname ($peer_hash,$peer_id) {
  if ( ! $peer_id ) {
    return PLC_SHORTNAME;
  } else {
     return $peer_hash[$node['peer_id']]['shortname'];
  }
}

// to set the background to grey on foreign objects
// return true if the peer is local 
function plc_peer_block_start ($peer_hash,$peer_id) {
  if ( ! $peer_id ) {
    print "<div>";
    return true;
  } else {
    // set two classes, one eneraic to all foreign, and one based on the peer's shortname for finer grain tuning
    printf ('<div class="plc-foreign plc-%s>"',strtolower(plc_peer_shortname($peer_hash,$peer_id)));
    return false;
  }
}

function plc_peer_block_end () {
  print "</div>\n";
}

// interpret standard syntax for peerscope
function plc_peer_info ($api,$peerscope) {
  switch ($peerscope) {
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
    if (is_int ($peerscope)) {
      $peer_id=intval($peerscope);
      $peers=$api->GetPeers(array("peer_id"=>$peer_id));
    } else {
      $peers=$api->GetPeers(array("shortname"=>$peerscope));
    }
    if ($peers) {
      $peer=$peers[0];
      $peer_id=$peer['peer_id'];
      $peer_filter=array("peer_id"=>$peer_id);
      $peer_label='peer "' . $peer['shortname'] . '"';
    } else {
      $peer_filter=array();
      $peer_label="[no such peer " . $peerscope . "]";
    }
    break;
  }
  return array ($peer_filter,$peer_label);
}

//////////////////////////////////////////////////////////// titles
function t_site($site) { return " on site " . $site['name'] . " (" . $site['login_base'] .")"; }
function t_slice ($slice) { return " running slice " . $slice['name'] . " (" . $slice['slice_id'] . ")"; }

//////////////////////////////////////////////////////////// nav tabs
function tabs_node($node) { return array('Node ' . $node['hostname']=>l_node($node_id)); }
function tabs_site($site) { return array('Site ' . $site['name']=>l_site($site_id)); }
function tabs_slice($slice) { return array('Slice ' . $slice['name']=>l_slice($slice_id)); }

//////////////////////////////////////////////////////////// presentation
// builds a table from an array of strings, with the given class
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

function plc_comon_button ($id_name, $id_value,$target="") {
  $result='<a ';
  if (!empty($target)) {
    $result.='target="' . $target . '" ';
  }
  $result.='href="' . l_comon($id_name,$id_value) . '">';
  $result.='<span title="Link to Comon"> <img src="/planetlab/icons/comon.png" width="18"></span></a>';
  return $result;
}

function plc_vertical_table ($messages, $class="") {
  // pretty print the cell
  if ( empty( $messages) ) return "";
  $formatted = "";
  $formatted .= "<table ";
  if ($class) $formatted .= "class='" . $class . "'";
  $formatted .= ">";
  foreach ($messages as $message) {
    $formatted .= "<tr><td>" . $message . "</td></tr>";
  }
  $formatted .= "</table>";
  return $formatted;
}

////////////////////////////////////////////////////////////
function plc_error ($text) {
  // should use the same channel as the php errors..
  print "<div class='plc-error'> Error " . $text . "</div>";
}

function plc_errors ($list) {
  print( "<div class='plc-error'>" );
  print( "<p style='font-weight:bold'>The following errors occured:</p>" );
  print("<ul>");
  foreach( $errors as $err ) {
    print( "<li>$err</li>\n" );
  }
  print( "</ul></div>\n" );
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
