<?php

// $Id$

// will trash this eventually
  //require_once 'plc_functions_trash.php';

// utility
function my_is_int ($x) {
    return (is_numeric($x) ? intval($x) == $x : false);
}

//// belongs to plkit
// returns array ['url' => path, 'values' => hash (key=>value)* ]
function plkit_split_url ($full_url) {
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

function l_actions ()			{ return "/db/actions.php"; }
// some complex node actions are kept separate, e.g. the ones related to getbootmedium
function l_actions_download ()		{ return "/db/nodes/node_downloads.php"; }

function l_nodes ()			{ return "/db/nodes/index.php"; }
function l_nodes_peer ($peer_id)	{ return "/db/nodes/index.php?peerscope=$peer_id"; }
function l_node ($node_id)		{ return "/db/nodes/node.php?id=$node_id"; }
function l_node_t ($node_id,$text)	{ return href (l_node($node_id),$text); }
function l_node_obj($node)		{ return href(l_node($node['node_id']),$node['hostname']); }
function l_node_add ()			{ return "/db/nodes/node_add.php"; }
function l_nodes_site ($site_id)	{ return "/db/nodes/index.php?site_id=$site_id"; }

function l_interface ($interface_id)	{ return "/db/nodes/interface.php?id=$interface_id"; }
function l_interface_t ($interface_id,$text) { 
					  return href (l_interface($interface_id),$text); }
function l_interface_add($node_id)	{ return "/db/nodes/interface.php?node_id=$node_id"; }

function l_sites ()			{ return "/db/sites/index.php"; }
function l_sites_peer ($peer_id)	{ return "/db/sites/index.php?peerscope=$peer_id"; }
function l_site ($site_id)		{ return "/db/sites/index.php?id=$site_id"; }
function l_site_t ($site_id,$text)	{ return href (l_site($site_id),$text); }

function l_slices ()			{ return "/db/slices/index.php"; }
function l_slices_peer ($peer_id)	{ return "/db/slices/index.php?peerscope=$peer_id"; }
function l_slice ($slice_id)		{ return "/db/slices/index.php?id=$slice_id"; }
function l_slice_t ($slice_id,$text)	{ return href (l_slice($slice_id),$text); }
function l_slice_add ()			{ return "/db/slices/slice_add.php"; }
function l_slices_site($site_id)	{ return "/db/slices/index.php?site_id=$site_id"; }
// from an object
function l_slice_obj ($slice)		{ return l_slice_t ($slice['slice_id'],$slice['name']); }

function l_sliver ($node_id,$slice_id)	{ return "/db/nodes/slivers.php?node_id=$node_id&slice_id=$slice_id"; }
function l_sliver_t ($node_id,$slice_id,$text) { 
					  return href (l_sliver($node_id,$slice_id),$text) ; }

function l_persons ()			{ return "/db/persons/index.php"; }
function l_persons_peer ($peer_id)	{ return "/db/persons/index.php?peerscope=$peer_id"; }
function l_person ($person_id)		{ return "/db/persons/index.php?id=$person_id"; }
function l_person_t ($person_id,$text)	{ return href (l_person($person_id),$text); }
function l_persons_site ($site_id)	{ return "/db/persons/index.php?site_id=$site_id"; }
function l_person_obj ($person)		{ return l_person_t($person['person_id'],$person['email']); }

function l_tags ()			{ return "/db/tags/index.php"; }
function l_tag ($tag_type_id)		{ return "/db/tags/index.php?id=$tag_type_id"; }
function l_tag_obj ($tag)		{ return href(l_tag($tag['tag-type_id']),$tag['tagname']); }

function l_nodegroups ()		{ return "/db/tags/nodegroups.php"; }
function l_nodegroup ($nodegroup_id)	{ return "/db/tags/nodegroup.php?id=$nodegroup_id"; }
function l_nodegroup_t ($nodegroup_id,$text) { 
					  return href(l_nodegroup($nodegroup_id),$text); }

function l_events ()			{ return "/db/events/index.php"; }
function l_event ($type,$param,$id)	{ return "/db/events/index.php?type=$type&$param=$id"; }

function l_peers()			{ return "/db/peers/index.php"; }
function l_peer($peer_id)		{ return "/db/peers/index.php?id=$peer_id"; }
function l_peer_t($peer_id,$text)	{ return href(l_peer($peer_id),$text); }

function l_comon($id_name,$id_value)	{ return "/db/nodes/comon.php?$id_name=$id_value"; }
function l_sirius()			{ return "/db/sirius/index.php"; }
function l_about()			{ return "/db/about.php"; }
function l_doc_plcapi()			{ return "/db/doc/PLCAPI.php"; }
function l_doc_nmapi()			{ return "/db/doc/NMAPI.php"; }
function l_admin()			{ return "/db/adminsearch.php"; }

function l_login()			{ return "/db/login.php"; }
function l_logout()			{ return "/planetlab/logout.php"; }
function l_sulogout()			{ return "/planetlab/sulogout.php"; }
function l_reset_password()		{ return "/db/persons/reset_password.php"; }
function l_person_register()		{ return "/db/persons/register.php"; }
function l_site_register()		{ return "/db/sites/register.php"; }
function l_sites_pending()		{ return "/db/sites/join_request.php"; }


//////////////////////////////////////////////////////////// nav tabs
function tab_nodes ()		{ return array ('label'=>'All nodes','url'=>l_nodes(), 
						'bubble'=>'Display nodes from all peers'); }
function tab_nodes_local ()	{ return array ('label'=>'Local nodes', 'url'=>l_nodes_peer('local'), 
						'bubble'=>'Display local nodes only'); }
function tab_nodes_site($site_id){ return array ('label'=>'Site nodes', 'url'=>l_nodes_site($site_id), 
						 'bubble'=>'Display nodes on that site'); }
function tab_nodes_mysite ()	{ return array ('label'=>'My nodes', 'url'=>l_nodes_site(plc_my_site_id()), 
						'bubble'=>'Display nodes on that site'); }
function tab_node($node)	{ return array ('label'=>'Node '.$node['hostname'], 'url'=>l_node($node_id),
						'bubble'=>'Details for ' . $node['hostname']); }
//////////
function tab_site($site)	{ return array ('label'=>'Site '.$site['login_base'], 'url'=>l_site($site_id),
						'bubble'=>'Details for ' . $site['name']); }
function tab_mysite()		{ return array ('label'=>'My site', 'url'=>l_site(plc_my_site_id()),
						'bubble'=>'Details for site ' . plc_my_site_id()); }
function tab_sites ()		{ return array ('label'=>'All sites' , 'url'=>l_sites(), 'bubble'=> 'Display all sites'); }
function tab_sites_local ()	{ return array ('label'=>'Local sites' , 'url'=>l_sites_peer('local'), 'bubble'=> 'Display local sites'); }
//////////
function tab_slices()		{ return array ('label'=>'All slices', 'url'=>l_slices(),
						'bubble' => 'Display all slices'); }
function tab_slice($slice)	{ return array ('label'=>'Slice '.$slice['name'], 'url'=>l_slice($slice_id),
						'bubble' => 'Details for ' . $slice['name']); }
//////////
function tab_persons()		{ return array ('label'=>'All accounts', 'url'=>l_persons(),
						'bubble'=>'Display users from all peers'); }
function tab_persons_local()	{ return array ('label'=>'Local accounts', 'url'=>l_persons_peer('local'),
						'bubble'=>'Display local users'); }
function tab_persons_mysite()	{ return array ('label'=>'My accounts' , 'url'=>l_persons_site(plc_my_site_id()),
						'bubble'=>'Display accounts on site ' . plc_my_site_id()); }
//////////
function tab_tags()		{ return array ('label'=>'Tag Types', 'url'=>l_tags(),
						'bubble' => 'Display and create tag types'); }
function tab_nodegroups()	{ return array ('label'=>'Nodegroups', 'url'=>l_nodegroups(),
						'bubble' => 'Display and create nodegroups'); }

// only partial tab
function tablook_event()	{ return array('image'=>'/planetlab/icons/event.png','height'=>18);}
function tablook_comon()	{ return array('image'=>'/planetlab/icons/comon.png','height'=>18);}

////////////////////

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

////////////////////////////////////////////////////////////  roles
function plc_role_global_hash ($api) {
  $hash=array();
  $roles=$api->GetRoles();
  foreach ($roles as $role) {
    $hash[$role['role_id']]=$role['name'];
  }
  return $hash;
}

//////////////////////////////////////////////////////////// nodegroups
// hash by 'tagname=value'
function plc_nodegroup_global_hash ($api,$tagnames=NULL) {
  $filter=NULL;
  // xxx somehow this does not work; I've checked that the feature is working from plcsh
  // but I suspect the php marshalling or something; no time to fix, get all nodegroups for now
  // if ($tagnames) $filter=array("tagname"=>$tagnames);
  $nodegroups=$api->GetNodeGroups($filter);
  $hash=array();
  if ($nodegroups) foreach ($nodegroups as $nodegroup) {
      $key=$nodegroup['tagname']."=".$nodegroup['value'];
      $hash[$key]=$nodegroup;
    }
  return $hash;
}
  
//////////////////////////////////////////////////////////// titles
function t_site($site) { return " on site " . $site['name'] . " (" . $site['login_base'] .")"; }
function t_slice ($slice) { return " running slice " . $slice['name'] . " (" . $slice['slice_id'] . ")"; }

//////////////////////////////////////////////////////////// html fragments
function plc_vertical_table ($messages, $class="") {
  // pretty print the cell
  if ( empty( $messages) ) return "";
  $formatted = "";
  $formatted .= "<table";
  if ($class) $formatted .= " class='" . $class . "'";
  $formatted .= ">";
  foreach ($messages as $message) {
    $formatted .= "<tr><td>" . $message . "</td></tr>";
  }
  $formatted .= "</table>";
  return $formatted;
}

function plc_error ($text) {
  // should use the same channel as the php errors..
  print "<div class='plc-error'> Error " . $text . "</div>";
}

function plc_errors ($errors) {
  if ($errors) {
    print( "<div class='plc-error'>" );
    print( "<p>The following errors occured:</p>" );
    print("<ul>");
    foreach( $errors as $error ) 
      print( "<li>$error</li>\n" );
    print( "</ul></div>\n" );
  }
}

function plc_warning_html ($text)	{ return "<span class='plc-warning'>" . $text . "</span>";}
function plc_warning ($text)		{ print plc_warning_html("Warning " . $text); }
function plc_foreign_html($text)	{ return "<span class=plc-foreign>$text</span>"; }

// shows a php variable verbatim with a heading message
function plc_debug ($message,$object) {
  print "<br>" . $message . "<pre>";
  print_r ($object);
  print "</pre>";
}

function truncate ($text,$numb,$etc = "...") {
  if (strlen($text) > $numb) {
    $text = substr($text, 0, $numb);
    $text = $text.$etc;
  }
  return $text;
}
function html_div ($text,$class="") {
  $html="<div";
  if ($class) $html .= " class='$class'";
  $html .= ">$text</div>";
  return $html;
}

if (! function_exists ("drupal_set_error")) {
  function drupal_set_error ($text) {
    drupal_set_message ("<span class=error>$text</span>");
  }
 }

//////////////////////////////////////////////////////////// sort out for obsolete / trash
// builds a table from an array of strings, with the given class
// attempt to normalize the delete buttons and confirmations
function plc_delete_icon($width=15) {
  return "<img width='$width' src='/planetlab/icons/delete.png'>";
}

function plc_bubble($text,$bubble) {
  return "<span title='$bubble'>$text</span>";
}
function plc_delete_icon_bubble ($bubble,$width=15) {
  return plc_bubble(plc_delete_icon($width),$bubble);
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

////////////////////
function plc_redirect ($url) {
  header ("Location: " . $url);
  exit ();
}

?>
