<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';
  
// if not a admin, pi, or tech then redirect to node index
// xxx does not take site into account
$has_privileges=plc_is_admin() || plc_is_pi() || plc_is_tech();
if( ! $has_privileges) {
  header( "index.php" );
}

// this sets up which box is to be checked the first time the page is loaded
$method= $_POST['method'];
if( $method == "" ) $method= "static";

$model= $_POST['model'];
if( $model == "" ) $model= "Custom";


$submitted = false;

// if submitted validate and add
if ( $_POST['submitted'] )  {
  $submitted = true;

  $errors= array();

  $method = trim($_POST['method']);
  $ip = trim($_POST['ip']);
  $netmask = trim($_POST['netmask']);
  $network = trim($_POST['network']);
  $gateway = trim($_POST['gateway']);
  $broadcast = trim($_POST['broadcast']);
  $dns1 = trim($_POST['dns1']);
  $dns2 = trim($_POST['dns2']);
  $hostname = trim($_POST['hostname']);
  $model= trim($_POST['model']);

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
      $errors[] = "The network address does not coorespond to the netmask";
    }
  }
  
  if( $hostname == "" ) {
    $errors[] = "Hostname is required";
  }
  if( $ip == "" ) {
    $errors[] = "IP is required";
  }
  if( count($errors) == 0 ) {
    $success= 1;

    // add new node and its interface
    $optional_vals= array( "hostname"=>$hostname, "model"=>$model );
    $site_id= plc_my_site_id();
    $node_id= $api->AddNode( intval( $site_id ), $optional_vals );

    if ( $api->error() ) {
       $errors[] = "Hostname already present or not valid";
       $success= 0;
    } else {
      // now, try to add the network.
      $optional_vals= array();
      $optional_vals['is_primary']= true;
      $optional_vals['ip']= $ip;
      $optional_vals['type']= 'ipv4';
      $optional_vals['method']= $method;
    
      if( $method == 'static' ) {
        $optional_vals['gateway']= $gateway;
        $optional_vals['network']= $network;
        $optional_vals['broadcast']= $broadcast;
        $optional_vals['netmask']= $netmask;
        $optional_vals['dns1']= $dns1;
        if (!empty($dns2)) {
          $optional_vals['dns2']= $dns2;
        }
      }

      $interface_id= $api->AddInterface( $node_id, $optional_vals);
      // if AddInterface fails, we have the node created,
      // but no primary interface is present.
      // The primary interface can be added later,
      // but take a look at the possible Methods,
      // if we specify TUN/TAP Method we will have
      // an error on download of the configuration file
    }
  }
}

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Nodes');
include 'plc_header.php';

?>

<script language="javascript">
function updateStaticFields() {
  var is_dhcp= document.fm.method[0].checked;

  document.fm.netmask.disabled= is_dhcp;
  document.fm.network.disabled= is_dhcp;
  document.fm.gateway.disabled= is_dhcp;
  document.fm.broadcast.disabled= is_dhcp;
  document.fm.dns1.disabled= is_dhcp;
  document.fm.dns2.disabled= is_dhcp;
}
</script>

<?php

if ( $success ) {
  $link=l_node2($node_id,"here");
  print <<< EOF
<h2>Node Created</h2>

<p>The node has been successfully added.

<p>View node details and download a configuration 
    file $link.
EOF;
 } else {
  print <<< EOF
<h2>Add A New Node</h2>

<p>This page will allow you to add a new machine to your site. This must
be done before the machine is turned on, as it will allow you to download 
a configuration file when complete for this node.

<p>Even for DHCP, you must enter the IP address of the node.
EOF;

if( count($errors) > 0 ) {
  plc_errors ($errors);
}

$self = $_SERVER['PHP_SELF'];
if (!empty($_SERVER['QUERY_STRING'])) {
  $self .= "?" . $_SERVER['QUERY_STRING'];
}

?>

<form name="fm" method="post" action="<?php echo $self; ?>">
<input type="hidden" name="submitted" value="1">

<h3>Node Details</h3>


<input type="hidden" name="submitted" value="1">

<table width="100%" cellspacing="0" cellpadding="4" border="0">

<tr>
<td width=250>Hostname:</td>
<td><input type="text" name="hostname"
value="<?php print($hostname); ?>" size="40" maxlength="256"></td>
</tr>

<tr>
<td>Model:</td>
<td><input type="text" name="model"
value="<?php print($model); ?>" size="40" maxlength="256"></td>
</tr>

</table>


<h3>Interface Settings</h3>

<table width="100%" cellspacing="0" cellpadding="4" border="0">

<tr>
<td valign='top' width="250">Addressing Method</td>
<td>
<input type="radio" name="method" value="dhcp" onChange='updateStaticFields()'
<?php if($method == 'dhcp') { echo "checked"; } ?>>DHCP 
<input type="radio" name="method" value="static" onChange='updateStaticFields()'
<?php if($method == 'static') { echo "checked"; } ?>>Static
</td>
</tr>

<tr> 
<td valign='top'>IP Address</td>
<td><input type="text" name="ip" value="<?php print($ip); ?>"></td>
</tr>

<tr> 
<td valign='top'>Netmask</font></td>
<td><input type="text" name="netmask" value="<?php print($netmask); ?>"></td>
</tr>

<tr> 
<td valign='top'>Network address</td>
<td><input type="text" name="network" value="<?php print($network); ?>">
</td>
</tr>

<tr> 
<td valign='top'>Gateway Address</td>
<td><input type="text" name="gateway" value="<?php print($gateway); ?>"></td>
</tr>

<tr> 
<td valign='top'>Broadcast address</td>
<td><input type="text" name="broadcast" value="<?php print($broadcast); ?>">
</td>
</tr>

<tr> 
<td valign='top'>Primary DNS</td>
<td><input type="text" name="dns1" value="<?php print($dns1); ?>">
</td>
</tr>

<tr>
<td valign='top'>Secondary DNS (optional)</td>
<td><input type="text" name="dns2" value="<?php print($dns2); ?>">
</td>
</tr>

<tr>
<td></td>
<td><input type="submit" name="Submit" value="Add"></td>
</tr>

</table>
</form>

<?php

}


// Print footer
include 'plc_footer.php';

?>
