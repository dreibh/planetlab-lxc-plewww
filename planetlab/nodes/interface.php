<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Common functions
require_once 'plc_functions.php';
require_once 'plc_minitabs.php';
require_once 'plc_details.php';
require_once 'plc_tables.php';

require_once 'plc_drupal.php';
include 'plc_header.php';

// purpose : display, update or add an interface

// interface:
// updating : _GET['id'] :  
//	if id is set: display the interface, allows to update/add a new if the user has privileges
// adding: _GET['node_id']: 
//	otherwise, node_id is needed and the form only allows to add

if ( isset ($_GET['id'])) {
  $mode='update';
  $interface_id=intval($_GET['id']);
  $interfaces=$api->GetInterfaces(array('interface_id'=>$interface_id));
  $interface=$interfaces[0];
  $node_id=$interface['node_id'];
 } else if (isset ($_GET['node_id'])) {
  $mode='add';
  $interface=array();
  $node_id=$_GET['node_id'];
 } 
// check
if ( ! $node_id) {
  drupal_set_error('Malformed URL in interface.php, need id or node_id');
  plc_redirect(l_nodes());
  return;
 }

$tabs=array();
$tabs['Back to node']=array('url'=>l_node($node_id),
			    'bubble'=>'Cancel pending changes');
plc_tabs($tabs);

$fields=array( 'method', 'type', 'ip', 'gateway', 'network', 'broadcast', 'netmask', 
	       'dns1', 'dns2', 'hostname', 'mac', 'bwlimit', 'node_id' );

//////////////////////////////
$nodes= $api->GetNodes( array( intval($node_id) ), array( 'node_id', 'hostname', 'site_id' ) );
$node= $nodes[0];
$site_id=$node['site_id'];

$can_update= plc_is_admin() || ( plc_in_site ($site_id) && ( plc_is_pi() || plc_is_tech()));

drupal_set_title("Interface on " . $node['hostname']);

// include javacsript helpers
drupal_set_title ('
<script type="text/javascript" src="/planetlab/prototype/prototype.js"></script>
<script type="text/javascript" src="/planetlab/nodes/interface.js"></script>
');

$details=new PlcDetails($can_update);

// hardwire network type
$form=$details->form_start(l_actions(),array('node_id'=>$node_id,'type'=>"ipv4"));

$details->start();

//>>> GetNetworkMethods()
//[u'static', u'dhcp', u'proxy', u'tap', u'ipmi', u'unknown']
function method_selectors ($api, $method) {
  $builtin_methods=array("static"=>"Static", "dhcp"=>"DHCP", "proxy"=>"Proxy",  
			 "tap"=>"TUN/TAP", "ipmi"=>"IPMI");
  $selectors=array();
  foreach ($builtin_methods as $value=>$display) {
    $selector=array('display'=>$display, 'value'=>$value);
    if ($value == $method) $selector['selected']=true;
    $selectors []= $selector;
  }
  return $selectors;
}
$method_select = $form->select_html ("method",method_selectors($api,$interface['method']),
				     array('id'=>'method','onChange'=>'updateMethodFields()'));
$details->th_td("Method",$method_select,"method",array('input_type'=>'select'));

// dont display the 'type' selector as it contains only ipv4
//>>> GetNetworkTypes()
//[u'ipv4']

$ip_options=array('width'=>15);
$ip_options_plus=array_merge($ip_options,array('onKeyup'=>'networkHelper()',
					       'onChange'=>'networkHelper()',
					       ));
$ip_options_tmp=array_merge($ip_options,array('onKeyup'=>'subnetChecker("dns1")'));

$details->th_td("IP",$interface['ip'],"ip",$ip_options_plus);
$details->th_td("Netmask",$interface['netmask'],"netmask",$ip_options_plus);
$details->th_td("Network",$interface['network'],"network",$ip_options);
$details->th_td("Broadcast",$interface['broadcast'],"broadcast",$ip_options);
$details->th_td("Gateway",$interface['gateway'],"gateway",$ip_options);
$details->th_td("DNS 1",$interface['dns1'],"dns1",$ip_options_tmp);
$details->th_td("DNS 2",$interface['dns2'],"dns2",$ip_options);
$details->space();
$details->th_td("BW limit (bps)",$interface['bwlimit'],"bwlimit",array('width'=>11));
$details->th_td("Hostname",$interface['hostname'],"hostname");
# should the user be allowed to change this ?
$mac=$interface['mac'];
if ($mac) $details->th_td("MAC address",$mac);

// the buttons
$update_button = $form->submit_html ("update-interface","Update");
$add_button = $form->submit_html ("add-interface","Add as new");
$dbg="<input type=button onclick='formSubmit()' value='dbg'>";
switch ($mode) {
 case 'add':
   $details->tr($add_button,"right");
   break;
 case 'update':
   $details->tr($update_button . $add_button . $dbg,"right");
   break;
 }

$details->end();
$form->end();

// no tags if the interface has not been created yet
if ($mode == 'add') return;


//////////////////////////////////////// tags
$form = new PlcForm (l_actions(),array('interface_id'=>$interface_id));
$form->start();

$tags=$api->GetInterfaceTags (array('interface_id'=>$interface_id));
function get_tagname ($tag) { return $tag['tagname'];}
$tagnames = array_map ("get_tagname",$tags);
  
plc_section("Tags");
$headers=array("Name"=>"string",
	       "Value"=>"string",
	       );
if ($can_update) $headers[plc_delete_icon()]="none";
  
$table_options=array("notes_area"=>false,"pagesize_area"=>false,"search_width"=>10);
$table=new PlcTable("interface_tags",$headers,0,$table_options);
$table->start();
if ($tags) foreach ($tags as $tag) {
  $table->row_start();
  $table->cell(l_tag_obj($tag));
  $table->cell($tag['value']);
  // the remove checkbox
  if ($can_update) $table->cell ($form->checkbox_html('interface_tag_ids[]',$tag['interface_tag_id']));
  $table->row_end();
}
  
if ($can_update) {
  $table->tfoot_start();

  // remove tag 
  $table->row_start();
  $table->cell($form->submit_html("delete-interface-tags","Remove Tags"),
	       // use the whole columns and right adjust
	       $table->columns(), "right");
  $table->row_end();

  // set tag area
  $table->row_start();
  // get list of tag names in the interface/* category    
  $all_tags= $api->GetTagTypes( array ("category"=>"interface*"), array("tagname","tag_type_id"));
  // xxx cannot use onchange=submit() - would need to somehow pass action name 
  function tag_selector ($tag) { return array("display"=>$tag['tagname'],"value"=>$tag['tag_type_id']); }
  $selector=array_map("tag_selector",$all_tags);
  $table->cell($form->select_html("tag_type_id",$selector,array('label'=>"Choose")));
  $table->cell($form->text_html("value","",array('width'=>8)));
  $table->cell($form->submit_html("set-tag-on-interface","Set Tag"),2,"left");
  $table->row_end();
 }
  
$table->end();
$form->end();

// Print footer
include 'plc_footer.php';

?>
