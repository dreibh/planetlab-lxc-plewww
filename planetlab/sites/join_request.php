<?php
// $Id$
//
// page for administration of pending site registration requests
//

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Common functions
require_once 'plc_functions.php';

// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

include 'site_form.php';

////////////////////////////////////////
function render_all_join_requests($api) {
  $sites = $api->GetSites( array("enabled" => False, "peer_id" => NULL));
  if (!empty($sites)) {
    print("<table width=100%>");
    print("<tr><th>Site Name</th><th>site_id</th><th>Submitted</th></tr>");
    foreach($sites as $site) {
      printf("<tr><td><a href=/db/sites/join_request.php?review=t&site_id=%d> %s </a> </td>", $site['site_id'], $site['name']);
      printf("<td><a href=/db/sites/join_request.php?review=t&site_id=%d> %d </a> </td>", $site['site_id'], $site['site_id']);
      printf("<td> %s </td> </tr>", date("d F Y, G:i",$site['date_created']));
    }
    print("</table>");
  } else {
    print("<p> No open join requests </p>");
  }
}

function render_join_request_review($api, $site_id) {
  $sites = $api->GetSites( array(intval($site_id)) );
  if (empty($sites)) {
      print("<p class='plc-warning'> Invalid request with site_id=$site_id</p> ");
      return;
  }
  $site = $sites[0];
  if ($site['enabled']) {
    print("<p class='plc-warning'> This site is already enabled </p>");
    return;
  }
  $addresses = $api->GetAddresses ($site['address_ids']);
  if (empty ($addresses)) {
      print("<p class='plc-warning'> No address found for site_id=$site_id</p> ");
      return ;
  }
  $address = $addresses[0];
  $address_id=$address['address_id'];
# just in case there is no person attached yet
  if (empty ($site['person_ids'])) {
    $persons=array();
  } else {
    $person_ids = $site['person_ids'];
    $persons = $api->GetPersons( $person_ids, array( "person_id", "role_ids", "first_name", "last_name", "email" , "phone") );
  }
  $tech = Null;
  $pi = Null;
  foreach($persons as $person) {
    if ( in_array('20',  $person['role_ids']) ) {
      $pi = $person;
    }
    if ( in_array('40',  $person['role_ids']) ) {
      $tech = $person;
    }
  }
  $pi_id = $pi['person_id'];
  $tech_id = $tech['person_id'];
    
  print <<< EOF
<p> Please review the join request below.</p>
    <p> <b> Warning:</b> the PI email address that was provided in this form will <b> not be checked</b> automatically. We expect that as part of the handshake with the site, the support team has had an opportunity to use this address so it can be considered safe. Please check it manually if this is not the case.</p>
<form name="join_request" method="post" action="/db/sites/join_request.php">
<input type="hidden" name="pi_id" value="$pi_id">
<input type="hidden" name="address_id" value="$address_id">
<input type="hidden" name="tech_id" value="$tech_id">
<input type="hidden" name="site_id" value="$site_id">

<table border="0" width="100%" cellspacing="0" cellpadding="3">
EOF;

  $site_form = build_site_form(FALSE);
  $input = array ('site' => $site, 'address'=> $address, 'pi' => $pi, 'tech' => $tech);
  
  // display the buttons 
  print <<< EOF
    <tr>
    <td colspan='2'>
    <table width="100%" border=0 cellspacing="0" cellpadding="5"> <tr> 
    <td align=center><input type="submit" name="submitted" value="Delete"></td>
    <td align=center><input type="submit" name="submitted" value="Update"></td>
    <td align=center><input type="submit" name="submitted" value="Approve"></td>
    </tr> </table>
    </tr>
EOF;

  // render the form - not supposed to be empty
  form_render_table2 ($site_form, $input, TRUE);

  // display the buttons 
  print <<< EOF
    <tr>
    <td colspan='2'>
    <table width="100%" border=0 cellspacing="0" cellpadding="5"> <tr> 
    <td align=center><input type="submit" name="submitted" value="Delete"></td>
    <td align=center><input type="submit" name="submitted" value="Update"></td>
    <td align=center><input type="submit" name="submitted" value="Approve"></td>
    </tr> </table>
    </tr>
EOF;

  print "</table></form>";
}

function notify_enabled_pi ($api, $pi_id, $pi, $site_id, $site) {
  // notify the PI
  $template= <<<EOF
You have filed a site registration with the %s platform.

This registration has been approved, and your account was enabled
You were granted a PI role, meaning that you will be responsible 
for managing personal accounts and slices for your site

You can now enter the system at
https://%s:%d/
with %s as a login, 
and the password that you provided at registration-time

You can directly access your site information at
https://%s:%d/db/sites/index.php?id=%d

Please start with adding nodes for this site with
https://%s:%d/db/nodes/node_add.php

Our support team will be glad to answer any question that you might have
They are reachable at mailto:%s
EOF;
 
 $body=sprintf($template,
	       PLC_NAME,
	       PLC_WWW_HOST,PLC_WWW_SSL_PORT,
	       $pi['email'],
	       PLC_WWW_HOST,PLC_WWW_SSL_PORT,$site_id,
	       PLC_WWW_HOST,PLC_WWW_SSL_PORT,
	       PLC_MAIL_SUPPORT_ADDRESS);
   
 $subject="Site registration for " . $site['name'] . " has been approved by " . PLC_NAME;
 $api->NotifyPersons(array($pi_id),$subject,$body);
}


// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

// only admins are allowed to view this page
if( !in_array( '10', $_roles ) ) {

    print("<p> not allowed to view this page </p>");
}
else if ($_GET['review'])
{

    //print review page
    drupal_set_title('Join Request - Review');
    render_join_request_review($api, $_GET['site_id']);
	
}
else if ($_POST['submitted'] )
{

  // parse the form
  $site_form = build_site_form(FALSE);
  $input = parse_form ($site_form, $_REQUEST, $input);
  // xxx should not happen ?
  $empty_form = $input['is_empty'];
  $error = "";
  $messages= array();
  if ( $empty_form ) {
    $error .= '<div class-"plc-warning">Internal error - empty form !?!</div>';
  }
  if (empty ($error)) {
    $site=$input['site'];
    $address=$input['address'];
    $pi=$input['pi'];
    $tech=$input['tech'];

    // Look for missing/blank entries
    $error .= form_check_required ($site_form, $input);
  }

  if (empty($error)) {
    // get objects id from the request
    $site_id = intval(trim($_POST['site_id']));
    $address_id = intval(trim($_POST['address_id']));
    $pi_id = intval(trim($_POST['pi_id']));
    $tech_id = intval(trim($_POST['tech_id']));

    switch ($_POST['submitted']) {
    case 'Delete': {
      $api->DeleteSite ($site_id);
      $api_error=$api->error();
      if (!empty($api_error)) {
	$error .= $api->error();
      } else {
	$messages [] = "Site " . $site['name'] . " deleted";
      }
      break;
    }
    case 'Update': {
      $api->begin();
      $api->UpdateSite($site_id,$site);
      $api->UpdateAddress($address_id,$address);
      $api->UpdatePerson($pi_id,$pi);
      $api->UpdatePerson($tech_id,$tech);
      $api->commit();
      $api_error=$api->error();
      if (!empty($api_error)) {
	$error .= $api->error();
      } else {
	$messages [] = "Join request updated for site " . $site['name'] ;
      }
      
      break;
    }
    case 'Approve': {
      // Thierry - august 22 2007
      // keep it simple - the admin who approves is now supposed to check 
      // the PI's email address, which makes the whole thing *a lot* simpler
      // enable the site, enable the PI, and VerifyPerson the thec if different from the PI
      $site['enabled'] = True;
      $api->UpdateSite ($site_id,$site);
      $api_error=$api->error();
      if (!empty($api_error)) {
	$error .= $api->error();
	$messages [] = "Could not enable site";
      } else {
	$messages[] = "Site " . $site['name'] . " enabled";
      }
      
      if (empty ($error)) {
	// Update Address
	$api->UpdateAddress($address_id,$address);
	$api_error=$api->error();
	if ( ! empty($api_error)) {
	  $error .= $api->error();
	  $messages [] = "Could not update address";
	}
	
	foreach ( array("Billing","Shipping") as $address_type) {
	  $api->AddAddressTypeToAddress($address_type,$address_id);
	  $api_error=$api->error();
	  if ( ! empty($api_error)) {
	    $error .= $api->error();
	    $messages [] = "Could not add address type " . $address_type;
	  }
	}
	  
	// Update pi, and enable him
	$api->UpdatePerson ($pi_id,$pi);
	if ( $pi ['enabled' ] ) {
	  $messages [] = "PI already enabled";
	} else {
	  $api->UpdatePerson ($pi_id,array("enabled"=>True));
	  $api_error=$api->error();
	  if (empty($api_error)) {
	    $messages[] = "Enabled PI as " . $pi['email'] ;
	    notify_enabled_pi ($api, $pi_id,$pi,$site_id, $site);
	    $messages[] = "Notified PI by email";
	  } else {
	    $error .= $api->error();
	    $messages [] = "Could not update PI";
	  }
	}

	if ($pi_id != $tech_id) {
	  // Update tech, and VerifyPerson him if needed
	  $api->UpdatePerson ($tech_id,$tech);
	  if ( $tech [ 'enabled' ] ) {
	    $messages [] = "Tech already enabled";
	  } else {
	    $api->VerifyPerson($tech_id);
	    $api_error=$api->error();
	    if (empty($api_error)) {
	      $messages[] = "Created account registration for Tech as " . $tech['email'];
	    } else {
	      $error .= $api->error();
	      $messages [] = "Could not verify Tech";
	    }
	  }
	}
      }

      break;
    }
    default: {
      $error .= '<div class-"plc-warning">Internal error - unexpected request</div>';
      break;
    }

    } // end switch
  }

  // Show messages
  if (!empty($messages)) {
    print '<div class="messages status"><ul>';
    foreach ($messages as $line) {
      print "<li> $line";
    }
    print "</ul></div>";
  }
	
  // Show errors if any
  if (!empty($error)) {
    print '<div class="messages error">' . $error . '</div>';
    drupal_set_title('Join Request - Review');
    render_join_request_review($api, $_POST['site_id']);    
  } else {
    drupal_set_title('All currently pending join requests');
    render_all_join_requests($api);
  }

 }
 else // list all pending requests
{

    drupal_set_title('All currently pending join requests');
    render_all_join_requests($api);
}

// Print footer
include 'plc_footer.php';

?>
