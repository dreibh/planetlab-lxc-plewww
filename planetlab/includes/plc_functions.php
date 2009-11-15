<?php

// $Id$

# note: this needs to be consistent with the value in Monitor/monitor/wrapper/plc.py
global $PENDING_CONSORTIUM_ID;
$PENDING_CONSORTIUM_ID = 0;
# this one does not matter that much, just picking one
global $APPROVED_CONSORTIUM_ID;
$APPROVED_CONSORTIUM_ID = 999999;

// utility
function my_is_int ($x) {
    return (is_numeric($x) ? intval($x) == $x : false);
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
function plc_is_user () {
  global $plc;
  return in_array( 30, $plc->person['role_ids']);
}
function plc_is_tech () {
  global $plc;
  return in_array( 40, $plc->person['role_ids']);
}
function plc_in_site ($site_id) {
  global $plc;
  return in_array( $site_id, $plc->person['site_ids']);
}

function plc_my_site_ids () {
  global $plc;
  return $plc->person['site_ids'];
}
function plc_my_sites () {
  global $plc;
  global $api;
  return $api->GetSites($plc->person['site_ids'], array('site_id', 'abbreviated_name'));
}

function plc_my_site_id () {
  global $plc;
  return $plc->person['site_ids'][0];
}

function plc_my_person () {
  return $plc->person;
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

function l_actions ()			{ return "/db/common/actions.php"; }
// some complex node actions are kept separate, e.g. the ones related to getbootmedium
function l_actions_download ()		{ return "/db/nodes/node_downloads.php"; }
function l_register_node ()		{ return "/registerwizard/index.php"; }
function l_pcu_add ()			{ return "/registerwizard/index.php/register/stage1_addpcu"; }
function l_pcu ($pcu_id)		{ return "/db/sites/pcu.php?id=$pcu_id"; }

function l_nodes ()			{ return "/db/nodes/index.php"; }
function l_nodes_peer ($peer_id)	{ return "/db/nodes/index.php?peerscope=$peer_id"; }
function l_node ($node_id)		{ return "/db/nodes/node.php?id=$node_id"; }
function l_node_t ($node_id,$text)	{ return href (l_node($node_id),$text); }
function l_node_obj($node)		{ return href(l_node($node['node_id']),$node['hostname']); }
function l_node_add ()			{ return "/db/nodes/node_add.php"; }
function l_nodes_site ($site_id)	{ return "/db/nodes/index.php?site_id=$site_id"; }
function l_nodes_my_site ()             { return l_nodes_site(plc_my_site_id()) . "&active_line_tab=My site nodes"; }
function l_nodes_all_my_site ()         { return l_nodes_person(plc_my_person_id()) . "&active_line_tab=All My site nodes"; }
function l_nodes_person ($person_id)	{ return "/db/nodes/index.php?person_id=$person_id"; }
function l_nodes_slice ($slice_id)	{ return "/db/nodes/index.php?slice_id=$slice_id"; }

function l_interface ($interface_id)	{ return "/db/nodes/interface.php?id=$interface_id"; }
function l_interface_tags($interface_id){ return "/db/nodes/interface.php?id=$interface_id&show_details=0&show_tags=1"; }
function l_interface_t ($interface_id,$text) { 
					  return href (l_interface($interface_id),$text); }
function l_interface_add($node_id)	{ return "/db/nodes/interface.php?node_id=$node_id"; }

function l_sites ()			{ return "/db/sites/index.php"; }
function l_sites_peer ($peer_id)	{ return "/db/sites/index.php?peerscope=$peer_id"; }
function l_site ($site_id)		{ return "/db/sites/index.php?id=$site_id"; }
function l_site_t ($site_id,$text)	{ return href (l_site($site_id),$text); }
function l_site_obj($site)		{ return href (l_site($site['site_id']),$site['name']); }

function l_slices ()			{ return "/db/slices/index.php"; }
function l_slices_peer ($peer_id)	{ return "/db/slices/index.php?peerscope=$peer_id"; }
function l_slice ($slice_id)		{ return "/db/slices/index.php?id=$slice_id"; }
function l_slice_nodes ($slice_id)	{ return "/db/slices/index.php?id=$slice_id&show_details=0&show_nodes=1&show_nodes_current=1&show_nodes_add=1"; }
function l_slice_t ($slice_id,$text)	{ return href (l_slice($slice_id),$text); }
function l_slice_add ()			{ return "/db/slices/slice_add.php"; }
function l_slices_site($site_id)	{ return "/db/slices/index.php?site_id=$site_id"; }
function l_slices_my_site()     	{ return l_slices_site(plc_my_site_id()) . "&active_line_tab=My site slices"; }
function l_slices_person($person_id)    { return "/db/slices/index.php?person_id=$person_id"; }
function l_slices_local()		{ return "/db/slices/index.php?peerscope=local"; }
// from an object
function l_slice_obj ($slice)		{ return l_slice_t ($slice['slice_id'],$slice['name']); }

function l_sliver ($node_id,$slice_id)	{ return "/db/nodes/slivers.php?node_id=$node_id&slice_id=$slice_id"; }
function l_sliver_t ($node_id,$slice_id,$text) { 
					  return href (l_sliver($node_id,$slice_id),$text) ; }

function l_persons ()			{ return "/db/persons/index.php&active_line_tab=All Accounts"; }
function l_persons_peer ($peer_id)	{ return "/db/persons/index.php?peerscope=$peer_id&active_line_tab=Local Accounts"; }
function l_person ($person_id)		{ return "/db/persons/index.php?id=$person_id"; }
function l_person_t ($person_id,$text)	{ return href (l_person($person_id),$text); }
function l_persons_site ($site_id)	{ return "/db/persons/index.php?site_id=$site_id"; }
function l_persons_slice ($slice_id)	{ return "/db/persons/index.php?slice_id=$slice_id"; }
function l_person_obj ($person)		{ return l_person_t($person['person_id'],$person['email']); }

function l_tags ()			{ return "/db/tags/index.php"; }
function l_tag ($tag_type_id)		{ return "/db/tags/index.php?id=$tag_type_id"; }
function l_tag_obj ($tag)		{ return href(l_tag($tag['tag_type_id']),$tag['tagname']); }

function l_nodegroups ()		{ return "/db/tags/nodegroups.php"; }
function l_nodegroup ($nodegroup_id)	{ return "/db/tags/nodegroup.php?id=$nodegroup_id"; }
function l_nodegroup_t ($nodegroup_id,$text) { 
					  return href(l_nodegroup($nodegroup_id),$text); }
function l_nodegroup_obj ($nodegroup) { 
					  return href(l_nodegroup($nodegroup['nodegroup_id']),$nodegroup['groupname']); }

function l_events ()			{ return "/db/events/index.php"; }
function l_event ($type,$param,$id)	{ return "/db/events/index.php?type=$type&$param=$id"; }

function l_peers()			{ return "/db/peers/index.php"; }
function l_peer($peer_id)		{ return "/db/peers/index.php?id=$peer_id"; }
function l_peer_t($peer_id,$text)	{ return href(l_peer($peer_id),$text); }

function l_comon($id_name,$id_value)	{ return "/db/nodes/comon.php?$id_name=$id_value"; }
function l_sirius()			{ return "/db/sirius/index.php"; }
function l_about()			{ return "/db/common/about.php"; }
function l_doc_plcapi()			{ return "/db/doc/PLCAPI.php"; }
function l_doc_nmapi()			{ return "/db/doc/NMAPI.php"; }
function l_admin()			{ return "/db/common/adminsearch.php"; }

function l_login()			{ return "/db/common/login.php"; }
function l_logout()			{ return "/planetlab/common/logout.php"; }
function l_sulogout()			{ return "/planetlab/common/sulogout.php"; }
function l_reset_password()		{ return "/db/persons/reset_password.php"; }
function l_person_register()		{ return "/db/persons/register.php"; }
function l_site_register()		{ return "/db/sites/register.php"; }
function l_sites_pending()		{ return "/db/sites/join_request.php"; }
function l_site_review_pending($site_id){ return "/db/sites/join_request.php?review=t&site_id=$site_id"; }


//////////////////////////////////////////////////////////// nav tabs
function tab_nodes ()		{ return array ('label'=>'All nodes','url'=>l_nodes(), 
						'bubble'=>'Display nodes from all peers'); }
function tab_nodes_local ()	{ return array ('label'=>'Local nodes', 'url'=>l_nodes_peer('local'), 
						'bubble'=>'Display all nodes local to this peer'); }
function tab_nodes_site($site_id){ return array ('label'=>'Site nodes', 'url'=>l_nodes_site($site_id), 
						 'bubble'=>'Display nodes on that site'); }
function tab_nodes_slice($slice_id){ return array ('label'=>'Slice nodes', 'url'=>l_nodes_slice($slice_id), 
						 'bubble'=>'Display nodes for that slice'); }
function tab_nodes_mysite ()	{ return array ('label'=>'My site nodes', 'url'=>l_nodes_my_site(), 
						'bubble'=>'Display nodes on my site'); }
function tab_nodes_all_mysite (){ return array ('label'=>'All My site nodes', 'url'=>l_nodes_all_my_site(),
						'bubble'=>'Display nodes on all my sites'); }
function tab_node($node)	{ return array ('label'=>'Node '.$node['hostname'], 'url'=>l_node($node['node_id']),
						'bubble'=>'Details for ' . $node['hostname']); }
//////////
function tab_site($site)	{ return array ('label'=>'Site '.$site['login_base'], 'url'=>l_site($site['site_id']),
						'bubble'=>'Details for ' . $site['name']); }
function tab_mysite()		{ return array ('label'=>'My site', 'url'=>l_site(plc_my_site_id()),
						'bubble'=>'Details for site ' . plc_my_site_id()); }
function tab_sites ()		{ return array ('label'=>'All sites' , 'url'=>l_sites(), 'bubble'=> 'Display all sites'); }
function tab_sites_local ()	{ return array ('label'=>'Local sites' , 'url'=>l_sites_peer('local'), 
						'bubble'=> 'Display all siteslocal to this peer'); }
//////////
function tab_slices()		{ return array ('label'=>'All slices', 'url'=>l_slices(),
						'bubble' => 'Display all slices'); }
function tab_slices_person()	{ return array ('label'=>'My slices', 'url'=>l_slices_person(plc_my_person_id()),
						'bubble' => 'Display my all slices'); }
function tab_slices_mysite ()	{ return array ('label'=>'My site slices', 'url'=>l_slices_my_site(), 
						'bubble'=>'Display all slices on my site'); }
function tab_slices_local ()	{ return array ('label'=>'Local slices', 'url'=>l_slices_local(), 
						'bubble'=>'Display all slices local to this peer'); }
function tab_slice($slice)	{ return array ('label'=>'Slice '.$slice['name'], 'url'=>l_slice($slice['slice_id']),
						'bubble' => 'Details for ' . $slice['name']); }
//////////
function tab_persons()		{ return array ('label'=>'All Accounts', 'url'=>l_persons(),
						'bubble'=>'Display users from all peers'); }
function tab_persons_local()	{ return array ('label'=>'Local Accounts', 'url'=>l_persons_peer('local'),
						'bubble'=>'Display all users local to this peer'); }
function tab_persons_mysite()	{ return array ('label'=>'My site accounts' , 'url'=>l_persons_site(plc_my_site_id()),
						'bubble'=>'Display accounts on site ' . plc_my_site_id()); }
function tab_person($person)	{ return array ('label'=>'Account '.$person['email'], 'url'=>l_person($person['person_id']),
						'bubble'=>'Details for ' . $person['email']); }
//////////
function tab_tags()		{ return array ('label'=>'Tag Types', 'url'=>l_tags(),
						'bubble' => 'Display and create tag types'); }
function tab_nodegroups()	{ return array ('label'=>'Nodegroups', 'url'=>l_nodegroups(),
						'bubble' => 'Display and create nodegroups'); }

// only partial tab
function tablook_event()	{ return array('image'=>'/planetlab/icons/event.png','height'=>18);}
function tablook_comon()	{ return array('image'=>'/planetlab/icons/comon.png','height'=>18);}



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
function t_person ($person) { return " belonging to " . $person['email'] . " (" . $person['person_id'] . ")"; }

//////////////////////////////////////////////////////////// html fragments
function plc_vertical_table ($messages, $class="") {
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
function plc_itemize ($messages, $class="") {
  if ( empty( $messages) ) return "";
  $formatted = "";
  $formatted .= "<ul";
  if ($class) $formatted .= " class='" . $class . "'";
  $formatted .= ">";
  foreach ($messages as $message) {
    $formatted .= "<li>" . $message . "</li>";
  }
  $formatted .= "</ul>";
  return $formatted;
}

//////////
function truncate ($text,$numb,$etc = "...") {
  if (strlen($text) > $numb) {
    $text = substr($text, 0, $numb);
    $text = $text.$etc;
  }
  return $text;
}
// generates <(atom) class=(class)> (text) </(atom)>
function html_atom ($atom,$text,$class="") {
  $html="<$atom";
  if ($class) $html .= " class='$class'";
  $html .= ">$text</$atom>";
  return $html;
}
function html_div ($text,$class="") { return html_atom ('div',$text,$class); }
function html_span ($text,$class="") { return html_atom ('span',$text,$class); }

// should use the same channel as the php errors..
function plc_error_html ($text)		{ return  html_div ($text,'plc-error'); }
function plc_error ($text)		{ print plc_error_html ("Error " . $text); }

function errors_init() { return array();}
function errors_record ($adm, $errors) {
  if ($adm->error()) {
    $tmp=$adm->error();
    $errors []= $tmp;
  }
  return $errors;
}

function errors_display ($errors) {
  if ($errors) {
    print( "<div class='plc-error'>" );
    print( "<p>The following errors occured:</p>" );
    print("<ul>");
    foreach( $errors as $error ) 
      print( "<li>$error</li>\n" );
    print( "</ul></div>\n" );
  }
}

function plc_warning_html ($text)	{ return html_span($text,'plc-warning'); }
function plc_warning ($text)		{ print plc_warning_html("Warning " . $text); }

function bold_html ($text)		{ return html_span($text,'bold'); }

// shows a php variable verbatim with a heading message
function plc_debug ($message,$object) {
  print "<br>" . $message . "<pre>";
  print_r ($object);
  print "</pre>";
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

function plc_add_icon($width=15) {
  return "<img width='$width' src='/planetlab/icons/add.png'>";
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

//////////////////// the options for an interface - suitable for plekit/form
//>>> GetNetworkMethods()
//[u'static', u'dhcp', u'proxy', u'tap', u'ipmi', u'unknown']
function interface_method_selectors ($api, $method, $primary) {
  if ($primary) {
    $builtin_methods=array("static"=>"Static",
			   "dhcp"=>"DHCP");
  } else {
    $builtin_methods=array("static"=>"Static",
			   "dhcp"=>"DHCP", 
			   "proxy"=>"Proxy",  
			   "tap"=>"TUN/TAP",
			   "ipmi"=>"IPMI");
  }
  $selectors=array();
  foreach ($builtin_methods as $value=>$display) {
    $selector=array('display'=>$display, 'value'=>$value);
    if ($value == $method) $selector['selected']=true;
    $selectors []= $selector;
  }
  return $selectors;
}

// displays bandwidth with kbps Mbps Gbps as needed
function pretty_bandwidth ($bw) {
  if ($bw < 1000)		return $bw;
  if ($bw < 1000000)		return strval($bw/1000) . " kbps";
  if ($bw < 1000000000)		return strval($bw/1000000) . " Mbps";
  else				return strval($bw/1000000000) . " Gbps";
}

//////////////////// 
function instantiation_label ($slice) {
  $instantiation_labels = array ('not-instantiated'=>'NOT',
				 'plc-instantiated'=>'PLC',
				 'delegated' => 'DEL',
				 'nm-controller' => 'NM');
  $result=$instantiation_labels[$slice['instantiation']];
  if (!$result) $result = $slice['instantiation'];
  if (!$result) $result = '??';
  return $result;
}
  
//////////////////// toggle areas
// get_arg ('show_persons',false) returns $_GET['show_persons'] if set and false otherwise
function get_arg ($name,$default,$method='get') {
  if ($method == 'get') $var=$_GET; else $var=$_POST;
  if (isset ($var[$name])) return $var[$name];
  else return $default;
}

//////////////////// number of ...
function count_english ($objs,$name) {
  $count=count($objs);
  if ($count == 0) return 'No ' . $name;
  else if ($count == 1) return 'One ' . $name;
  else return $count . ' ' . $name . 's';
}
function count_english_warning ($objs, $name) {
  $x=count_english ($objs,$name);
  if (count ($objs) == 0) $x=plc_warning_html($x . ' !!');
  return $x;
}
?>
