<?php

// $Id$ 

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Common functions
require_once 'plc_functions.php';
require_once 'details.php';
require_once 'form.php';
  
// if not a admin, pi, or tech then redirect to node index
// xxx does not take site into account
$has_privileges=plc_is_admin() || plc_is_pi() || plc_is_tech();
if( ! $has_privileges) {
  drupal_set_error ("Unsufficient provileges to add a node");
  header( "index.php" );
}

//plc_debug('POST',$_POST);

// this sets up which box is to be checked the first time the page is loaded
// start with static; starting with dhcp does not disable the useless fields
$method= $_POST['method'];
if( $method == "" ) $method= "static";

$model= $_POST['model'];
if( $model == "" ) $model= "Custom";

// if submitted validate and add
// could go in actions.php but OTOH when things fail it's more convenient 
// to show the current values again
if ( $_POST['add-node'] )  {

  $errors= array();

  $hostname = trim($_POST['hostname']);
  $model= trim($_POST['model']);
  $ip = trim($_POST['ip']);
  $method = trim($_POST['method']);
  $netmask = trim($_POST['netmask']);
  $network = trim($_POST['network']);
  $broadcast = trim($_POST['broadcast']);
  $gateway = trim($_POST['gateway']);
  $dns1 = trim($_POST['dns1']);
  $dns2 = trim($_POST['dns2']);
  $bwlimit = trim($_POST['bwlimit']);

  // used to generate error strings for static fields only
  $static_fields= array();
  $static_fields['netmask']= "Netmask address";
  $static_fields['network']= "Network address";
  $static_fields['gateway']= "Gateway address";
  $static_fields['broadcast']= "Broadcast address";
  $static_fields['dns1']= "Primary DNS address";
  
  if( $method == 'static' ) {
    foreach( $static_fields as $field => $desc ) {
      if( trim($_POST[$field]) == "" ) {
        $errors[] = "$desc is required";
      } elseif( !is_valid_ip(trim($_POST[$field])) ) {
        $errors[] = "$desc is not a valid address";
      }
    }
    
    if( !is_valid_network_addr($network,$netmask) ) {
      $errors[] = "The network address does not match the netmask";
    }
  }
  
  if( $hostname == "" ) {
    $errors[] = "Hostname is required";
  }
  if( $ip == "" ) {
    $errors[] = "IP is required";
  } else if ( ! is_valid_ip ($ip)) {
    $errors []= "Invalid IP $ip";
  }
  
  if( !empty($errors) ) {
    drupal_set_error(plc_itemize($errors));
  } else {
    // add new node and its interface
    $node_fields= array( "hostname"=>$hostname, "model"=>$model );
    $site_id= plc_my_site_id();
    $node_id= $api->AddNode( intval( $site_id ), $node_fields );

    if ( empty($node_id) || ($node_id < 0) ) {
      drupal_set_error ("AddNode failed - hostname already present, or not valid ?");
    } else {
      // now, try to add the network.
      $interface_fields= array();
      $interface_fields['is_primary']= true;
      $interface_fields['ip']= $ip;
      $interface_fields['type']= $_POST['type'];
      $interface_fields['method']= $method;
      if (!empty($bwlimit)) 
	$interface_fields['bwlimit']=$bwlimit;
    
      if ( $method == 'static' ) {
        $interface_fields['netmask']= $netmask;
        $interface_fields['network']= $network;
        $interface_fields['broadcast']= $broadcast;
        $interface_fields['gateway']= $gateway;
        $interface_fields['dns1']= $dns1;
        if (!empty($dns2)) 
          $interface_fields['dns2']= $dns2;
      }

      $interface_id= $api->AddInterface( $node_id, $interface_fields);
      if ($interface_id > 0) {
	drupal_set_message ("Node successfully created");
	drupal_set_message ("Download a boot image in the 'Download' drop down below");
	plc_redirect (l_node($node_id));
      } else {
	// if AddInterface fails, we have the node created,
	// but no primary interface is present.
	// The primary interface can be added later,
	// but take a look at the possible Methods,
	// if we specify TUN/TAP Method we will have
	// an error on download of the configuration file
	drupal_set_message ("Node created");
	drupal_set_error ("But without an interface");
	drupal_set_error ("Please review manually");
	plc_redirect (l_node($node_id));
      }
    }
  }
}

// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

// include javacsript helpers
require_once 'prototype.php';
drupal_set_html_head ('
<script type="text/javascript" src="/planetlab/nodes/interface.js"></script>
');

$sites=$api->GetSites(array(plc_my_site_id()));
$sitename=$sites[0]['name'];
		       
drupal_set_title('Add a new node in site "' . $sitename . '"');

print <<< EOF
<p class='node_add'>
This page lets you declare a new machine in your site. 
This must be done before the machine is turned on, as it will allow you to download a boot image when complete for this node.
<br/>
You must enter an IP address even if you use DHCP.
</p>
EOF;

$details=new PlekitDetails($has_privileges);

// xxx hardwire network type for now
$form_variables = array('type'=>"ipv4");
//$form=$details->form_start(l_actions(),$form_variables);
$form=$details->form_start('/db/nodes/node_add.php',$form_variables);

$details->start();

$details->th_td("Hostname",$hostname,"hostname");
$details->th_td("Model",$model,"model");
$method_select = $form->select_html ("method",
				     interface_method_selectors($api,$method,true),
				     array('id'=>'method',
					   'onChange'=>'updateMethodFields()'));
$details->th_td("Method",$method_select,"method",
		array('input_type'=>'select','value'=>$interface['method']));

// dont display the 'type' selector as it contains only ipv4
//>>> GetNetworkTypes()
//[u'ipv4']

$details->th_td("IP address",$ip,"ip",array('width'=>15,
					    'onKeyup'=>'networkHelper()',
					    'onChange'=>'networkHelper()'));
$details->th_td("Netmask",$netmask,"netmask",array('width'=>15,
						   'onKeyup'=>'networkHelper()',
						   'onChange'=>'networkHelper()'));
$details->th_td("Network",$network,"network",array('width'=>15));
$details->th_td("Broadcast",$broadcast,"broadcast",array('width'=>15));
$details->th_td("Gateway",$gateway,"gateway",array('width'=>15,
						   'onChange'=>'subnetChecker("gateway",false)'));
$details->th_td("DNS 1",$dns1,"dns1",array('width'=>15,
					   'onChange'=>'subnetChecker("dns1",false)'));
$details->th_td("DNS 2",$dns2,"dns2",array('width'=>15,
					   'onChange'=>'subnetChecker("dns2",true)'));
$details->space();
$details->th_td("BW limit (bps)",$bwlimit,"bwlimit",array('width'=>11));

// the buttons
$add_button = $form->submit_html ("add-node","Add New Node",
				  array('onSubmit'=>'interfaceSubmit()'));
$details->tr($add_button,"right");

$details->end();
$form->end();

// Print footer
include 'plc_footer.php';

?>
