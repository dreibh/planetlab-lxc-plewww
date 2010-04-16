<?php

  // $Id$

  // cleaned up, keep only the actions related to downloading stuff
  // REQUIRED : node_id=node_id
  // (*) action='download-node-floppy' : 
  // (*) action='download-node-iso' : 
  // (*) action='download-node-usb' : 
  //				: same as former downloadconf.php with download unset
  //     if in addition POST contains a non-empty field 'download' :
  //				: performs actual node-dep download
  // (*) action='download-generic-iso':
  // (*) action='download-generic-usb':
  //				: performs actual generic download

// delivering node-dependant images requires larger memory limit
// trial and error, based on the current sizes
// generic-ISO 43980800 
// generic-usb 44720128 
// 256M OK
// 128M OK
// 96M OK
// 88M KO
// 80M KO
// 64M KO
// Bottom line is, looks like we need in the order of twice the file size
// so let's play it safe
// Thierry - for 4.2, we need a larger area, was 100 for 4.1
ini_set("memory_limit","150M");

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Common functions
require_once 'plc_functions.php';
require_once 'details.php';

// NOTE: this function exits() after it completes its job, 
// simply returning leads to html decorations being added around the contents
function deliver_and_unlink ($filename) {
  
  // for security reasons we want to be able to unlink the resulting file once downloaded
  // so simply redirecting through a header("Location:") is not good enough

  $size= filesize($filename);

  // headers
  header ("Content-Type: application/octet-stream");
  header ("Content-Transfer-Encoding: binary");
  header ("Content-Disposition: attachment; filename=" . basename($filename) );
  header ("Content-Length: " . $size );
  // for getting IE to work properly
  // from princeton cvs new_plc_www/planetlab/nodes/downloadconf.php 1.2->1.3
  header ("Pragma: hack");
  header ("Cache-Control: public, must-revalidate");

  // turn off output buffering
  ob_end_flush();
  // outputs the whole file contents without copying it to memory
  readfile($filename);
 
  // unlink the file
  if (! unlink ($filename) ) {
    // cannot unlink, but how can we notify this ?
    // certainly not by printing
  }
  exit();
}

function show_download_confirm_button ($api, $node_id, $action, $can_gen_config, $show_details) {

  if( $can_gen_config ) {
    if ($show_details) {
      $preview=$api->GetBootMedium($node_id,"node-preview","");
      print ("<hr />");
      print ("<h3 class='node_download'>Configuration file contents</h3>");
      print ("<pre>\n$preview</pre>\n");
      print ("<hr />");
    }
    $action_labels = array ('download-node-floppy' => 'textual node config (for floppy)' ,
			    'download-node-iso' => 'ISO image',
			    'download-node-usb' => 'USB image' );
    
    $format = $action_labels [ $action ] ;
    print( "<div id='download_button'> <form method='post' action='node_downloads.php'>");
    print ("<input type='hidden' name='node_id' value='$node_id'>");
    print ("<input type='hidden' name='action' value='$action'>");
    print ("<input type='hidden' name='download' value='1'>");
    print( "<input type='submit' value='Download $format'>" );
    print( "</form></div>\n" );
  } else {
    echo "<p><font color=red>Configuration file cannot be created until missing values above are updated.</font>";
  }
}

// check arguments

if (empty($_POST['node_id'])) {
  plc_redirect (l_nodes());
 } else {
  $node_id = intval($_POST['node_id']);
}

$action=$_POST['action'];

switch ($action) {

 case "download-generic-iso":
 case "download-generic-usb":
   
   if ($action=="download-generic-iso") {
     $boot_action="generic-iso";
   } else {
     $boot_action="generic-usb";
   }

   // place the result in a random-named sub dir for clear filenames
   $filename = $api->GetBootMedium ($node_id, $boot_action, "%d/%n-%p-%a-%v%s");
   $error=$api->error();
   // NOTE. for some reason, GetBootMedium sometimes does not report an error but the
   // file is not created - this happens e.g. when directory owmer/modes are wrong 
   // in this case we get an empty filename
   // see /etc/httpd/logs/error_log in this case
   if (empty($error) && empty($filename)) {
     $error="Unexpected error from GetBootMedium - probably wrong directory modes";
   }    
   if (! empty($error)) {
     print ("<div class='plc-error'> $error </div>\n");
     print ("<p><a href='/db/nodes/index.php?id=$node_id'>Back to node </a>\n");
     return ;
   } else {
     deliver_and_unlink ($filename);
     exit();
   }
   break;

   // ACTION: download-node
   // from former downloadconf.php
   
 case "download-node-floppy":
 case "download-node-iso":
 case "download-node-usb":
 case "download-node-usb-partition":

   
   $nodes = $api->GetNodes( array( $node_id ) );
   $node = $nodes[0];

   // non-admin people need to be affiliated with the right site
   if( ! plc_is_admin() ) {
     $node_site_id = $node['site_id'];
     $in_site = plc_in_site($node_site_id);
     if( ! $in_site) {
       $error= "Insufficient permission. You cannot create configuration files for this node.";
     }
   }

   $hostname= $node['hostname'];
   // search for the primary interface
   $interfaces = $api->GetInterfaces( array( "node_id" => $node_id, "is_primary"=>true ), NULL );
   
   $can_gen_config= 1;

   if ( ! $interfaces ) {
     $can_gen_config= 0;
   } else {
     $interface = $interfaces[0];
     if ( $node['hostname'] == "" ) {
       $can_gen_config= 0;
       $node['hostname']= plc_warning("Missing");
     }
     
     # fields to check
     $fields= array("method","ip");
     if ( $interface['method'] == "static" ) 
       $fields = array_merge ( $fields, array("gateway","netmask","network","broadcast","dns1"));
     foreach( $fields as $field ) {
       if( $interface[$field] == "" ) {
	 $can_gen_config= 0;
	 $interface[$field]= plc_warning("Missing");
       }
     }

     if(    $interface['method'] != "static" 
	 && $interface['method'] != "dhcp" ) {
       $can_gen_config= 0;
       $interface['method']= "<i>Unknown method</i>";
     }
   }

   $download= $_POST['download'];
   
   if( $can_gen_config && !empty($download) ) {
     switch ($action) {
     case 'download-node-floppy':
       $boot_action='node-floppy'; 
       $location = "%d/%n-%v-rename-into-plnode%s";
       $options = array();
       break;
     case 'download-node-iso':
       $boot_action='node-iso';
       $location = "%d/%n-%a-%v%s";
       $options = array();
       break;
     case 'download-node-usb':
       $boot_action='node-usb';
       $location = "%d/%n-%a-%v%s";
       $options = array();
       break;
     case "download-node-usb-partition":
       $boot_action='node-usb';
       $location = "%d/%n-%a-%v-partition%s";
       $options = array('partition');
       break;
     }	 

     $filename=$api->GetBootMedium($node_id,$boot_action,$location,$options);
     $error=$api->error();
     if (empty($error) && empty($filename)) {
       $error="Unexpected error from GetBootMedium - probably wrong directory modes";
     }    
     if (! empty($error)) {
       print ("<div class='plc-error'> $error </div>\n");
       print ("<p><a href='/db/nodes/index.php?id=$node_id'>Back to node </a>\n");
       return ;
     } else {
       deliver_and_unlink ($filename);
       exit();
     }
   }

   drupal_set_title("Download boot medium for $hostname");

   $header= <<<EOF
<p class='node_download'>
<span class='bold'>WARNING:</span> Downloading a new boot medium
for this node will generate <span class='bold'>a new node key</span>, and any 
formerly downloaded boot medium for that node will become <span class='bold'>outdated</span>.
<br/>
<br/>
In order to create a configuration file for this node using this page,
the interface settings must be up to date. Below is summary of these
values. 
</p>

EOF;

   echo $header;

   show_download_confirm_button($api, $node_id, $action, $can_gen_config, false);
   print ("<hr />");
   print ("<h3 class='node_download'>Current node configuration</h3>");

   $details = new PlekitDetails (false);
   $details->start();

   if( ! $interface ) {
     print ("<center>");
     print (plc_warning("This node has no configured primary interface."));
     print ("You can add one " . href(l_interface_add($node_id), "here "));
     print ("</center>");
   } else {
     $details->tr(l_node_t($node_id,"Node details"),"center");
     $details->th_td("node_id",$node_id);
     $details->th_td("Hostname",$node['hostname']);

     $interface_id = $interface['interface_id'];
     $details->tr(l_interface_t($interface_id,"Interface Details"),"center");
     $details->th_td("Method",$interface['method']);
     $details->th_td("IP",$interface['ip']);

     if( $interface['method'] == "static" ) {
       $details->th_td("Gateway",$interface['gateway']);
       $details->th_td("Network mask",$interface['netmask']);
       $details->th_td("Network address",$interface['network']);
       $details->th_td("Broadcast address",$interface['broadcast']);
       $details->th_td("DNS 1",$interface['dns1']);
       if ($interface['dns2'])
	 $details->th_td("DNS 2",$interface['dns2']);
      }

     $details->tr(href(l_interface_tags($interface_id),"Interface extra settings (tags)"),"center");
     $interface_id = $interface['interface_id'];
     $settings=$api->GetInterfaceTags(array("interface_id" => array($interface_id)));
     if ( ! $settings) {
       $details->tr("no tag set","center");
     } else foreach ($settings as $setting) {
       $category=$setting['category'];
       $name=$setting['tagname'];
       $value=$setting['value'];
       $details->th_td($category . " " . $name,$value);
     }
   }
   $details->end();

 show_download_confirm_button($api, $node_id, $action, $can_gen_config, true);
 break;
 
 default:
   drupal-set_error("Unkown action $action in node_downloads.php");
   plc_redirect (l_node($node_id));
   break;
 }

?>
