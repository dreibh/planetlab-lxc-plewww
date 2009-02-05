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
require_once 'plc_sorts.php';

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

  // outputs the whole file contents
  print (file_get_contents($filename));
  
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
      print ("<hr /> <h3>Current node configuration contents</h3>");
      print ("<pre>\n$preview</pre>\n");
      print ("<hr />");
    }
    $action_labels = array ('download-node-floppy' => 'textual node config (for floppy)' ,
			    'download-node-iso' => 'ISO image',
			    'download-node-usb' => 'USB image' );
    
    $format = $action_labels [ $action ] ;
    print( "<p><form method='post' action='node_downloads.php'>\n");
    print ("<input type='hidden' name='node_id' value='$node_id'>\n");
    print ("<input type='hidden' name='action' value='$action'>\n");
    print ("<input type='hidden' name='download' value='1'>\n");
    print( "<input type='submit' value='Download $format'>\n" );
    print( "</form>\n" );
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
   
   $return= $api->GetNodes( array( $node_id ) );
   $node_detail= $return[0];

   // non-admin people need to be affiliated with the right site
   if( ! plc_is_admin() ) {
     $node_site_id = $node_detail['site_id'];
     $in_site = plc_in_site($node_site_id);
     if( ! $in_site) {
       $error= "Insufficient permission. You cannot create configuration files for this node.";
     }
   }

   $hostname= $node_detail['hostname'];
   $return= $api->GetInterfaces( array( "node_id" => $node_id ), NULL );
   
   $can_gen_config= 1;
   $has_primary= 0;
   
   if( count($return) > 0 ) {
     foreach( $return as $interface_detail ) {
       if( $interface_detail['is_primary'] == true ) {
	 $has_primary= 1;
	 break;
       }
     }
   }

   if( !$has_primary ) {
     $can_gen_config= 0;
   } else {
     if( $node_detail['hostname'] == "" ) {
       $can_gen_config= 0;
       $node_detail['hostname']= "<i>Missing</i>";
     }
     
     $fields= array("method","ip");
     foreach( $fields as $field ) {
       if( $interface_detail[$field] == "" ) {
	 $can_gen_config= 0;
	 $interface_detail[$field]= "<i>Missing</i>";
       }
     }

     if( $interface_detail['method'] == "static" ) {
       $fields= array("gateway","netmask","network","broadcast","dns1");
       foreach( $fields as $field ) {
	 if( $interface_detail[$field] == "" ) {
	   $can_gen_config= 0;
	   $interface_detail[$field]= "<i>Missing</i>";
	 }
       }
     }

     if(    $interface_detail['method'] != "static" 
	 && $interface_detail['method'] != "dhcp" ) {
       $can_gen_config= 0;
       $interface_detail['method']= "<i>Unknown method</i>";
     }
   }

   $download= $_POST['download'];
   
   if( $can_gen_config && !empty($download) ) {
     switch ($action) {
     case 'download-node-floppy':
       $boot_action='node-floppy'; 
       $location = "%d/%n-%v-rename-into-plnode%s";
       break;
     case 'download-node-iso':
       $boot_action='node-iso';
       $location = "%d/%n-%a-%v%s";
       break;
     case 'download-node-usb':
       $boot_action='node-usb';
       $location = "%d/%n-%a-%v%s";
       break;
     }	 

     $filename=$api->GetBootMedium($node_id,$boot_action,$location);
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

   drupal_set_title("Download boot material for $hostname");

   $header= <<<EOF

WARNING: Creating a new configuration file for this node will generate
a new node key, and any existing configuration file will be unusable and
 must be updated before the node can successfully boot, install, or
go into debug mode.

<p>In order to create a configuration file for this node using this page,
all the interface settings must be up to date. Below is summary of these
values. Any missing values must be entered before this can be used.

EOF;

   echo $header;

   show_download_confirm_button($api, $node_id, $action, $can_gen_config, false);
   print ("<p>");
   print ("<h3>Current interface settings</h3>\n");
   
if( $has_primary ) {
  print( "<table border=\"0\" cellspacing=\"4\">\n" );
  
  print( "<tr><th colspan=2><a href='index.php?id=$node_id'>Node Details</a></th></tr>" );
  print( "<tr><th>node_id:</th>" );
  print( "<td>$node_id</td></tr>\n" );
  print( "<tr><th>Hostname:</th>" );
  print( "<td>" . $node_detail['hostname'] . "</td></tr>\n" );

  $nn_id = $interface_detail['interface_id'];
  print( "<tr><th colspan=2><a href='interfaces.php?id=$nn_id'>Interface Details</a></th></tr>" );

  print( "<tr><th>Method:</th>" );
  print( "<td>" . $interface_detail['method'] . "</td></tr>\n" );
  print( "<tr><th>IP:</th>" );
  print( "<td>" . $interface_detail['ip'] . "</td></tr>\n" );

  if( $interface_detail['method'] == "static" ) {
      print( "<tr><th>Gateway:</th>" );
      print( "<td>" . $interface_detail['gateway'] . "</td></tr>\n" );
      print( "<tr><th>Network mask:</th>" );
      print( "<td>" . $interface_detail['netmask'] . "</td></tr>\n" );
      print( "<tr><th>Network address:</th>" );
      print( "<td>" . $interface_detail['network'] . "</td></tr>\n" );
      print( "<tr><th>Broadcast address:</th>" );
      print( "<td>" . $interface_detail['broadcast'] . "</td></tr>\n" );
      print( "<tr><th>DNS 1:</th>" );
      print( "<td>" . $interface_detail['dns1'] . "</td></tr>\n" );
      print( "<tr><th>DNS 2:</th>" );
      if( $interface_detail['dns2'] == "" ) {
	print( "<td><i>Optional, missing</i></td></tr>\n" );
      } else {
	print( "<td>" . $interface_detail['dns2'] . "</td></tr>\n" );
      }
    }

  print ("<tr><th colspan=2><a href='interfaces.php?id=$nn_id'>Additional Settings</a></th></tr>\n");
  $nn_id = $interface_detail['interface_id'];
  $settings=$api->GetInterfaceTags(array("interface_id" => array($nn_id)));
  foreach ($settings as $setting) {
    $category=$setting['category'];
    $name=$setting['tagname'];
    $value=$setting['value'];
    print (" <tr><th> $category $name </th><td> $value </td></tr>\n");
  }

  print( "</table>\n" );
} else {
  print( "<p class='plc-warning'>This node has no configured primary interface.</p>\n" );
}

 show_download_confirm_button($api, $node_id, $action, $can_gen_config, true);
 break;
 
 default:
   drupal-set_error("Unkown action $action in node_downloads.php");
   plc_redirect (l_node($node_id));
   break;
 }

?>
