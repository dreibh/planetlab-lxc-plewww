<?php
// $Id$
//
// Site registration and verification form. 
//
// Thierry Parmentelat -- INRIA 
// based on persons/register.php 
//

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// Get sorting functions
require_once 'plc_sorts.php';

// Print header
require_once 'plc_drupal.php';
drupal_set_title('New Site Registration');
include 'plc_header.php';

include 'site_form.php';

$verbose = FALSE;

// initialize objects
$objectnames = array ('site','pi','tech','address');

$site_form = build_site_form(TRUE);
$input = parse_form ($site_form, $_REQUEST, $input);
$empty_form = $input['is_empty'];

$site=$input['site'];
$pi=$input['pi'];
$tech=$input['tech'];
$address=$input['address'];

if (! $empty_form ) {
  // Look for missing/blank entries
  $error = form_check_required ($site_form, $input);
  $messages= array();
  $verboses= array();

  if (empty($error)) {

    // I considered using begin/commit
    // but first it was incorrectly reporting errors - which I fixed
    // second: you need site_id to perform AddSiteAddress
    // but you cannot write
    // begin()
    // site_id=AddSite()
    // AddSiteAddress(site_id,..)
    // because site_id would not be evaluated at the time you use it
    //
    // and you cannot either write
    // begin
    // AddSite(..)
    // AddSiteAddress('login_base',...)
    // because if the first call fails because login_base is already taken, 
    // then you're going to add the new address to the wrong site
    // 
    // the bottom line is, there's no advantage in using begin/commit at all
    
    // creating the site
    $site['enabled']=FALSE;
    $site_id=$adm->AddSite($site);
    $api_error .= $adm->error();
    if (empty($api_error)) {
      $verboses [] = "Site created as disabled";
    } else {
      $error .= $api_error;
    }
  }
  
  if (empty($error)) {

    // Thierry on august 22 2007
    // use NotifySupport to send this message, and get rid of the fake account

    $subject = "Site registration form : new site " . $site['name'];
    // should use GetMessages and stuff, but I do not trust the way 
    // templates are created in db-config, for upgrades notably
    $template = <<< EOF
We received a site registration form for site name %s

To review, please visit https://%s:%d/db/sites/join_request.php?review=t&site_id=%d.
EOF;
    $body=sprintf($template,$site['name'],PLC_WWW_HOST,PLC_WWW_SSL_PORT,$site_id);
    $adm->NotifySupport($subject,$body);
      
    $messages [] = "Your registration request has been received.";
    $messages [] = "A mail was sent to the operations team, your application should be processed shortly.";
    $messages [] = "Upon approval, the PI will receive an information e-mail";
    $messages [] = "Please send a message to " . PLC_MAIL_SUPPORT_ADDRESS . " if this request is not instructed within a few days.";

  // creating address
    $adm->AddSiteAddress($site_id,$address);
    $api_error = $adm->error();
    if (empty($api_error)) {
      $verboses [] = "Address created";
    } else {
      $error .= $api_error;
    }

    // creating PI
    // Thierry 23 august 2007
    // avoid using a pre-existing federated account
    $known_pi = $adm->GetPersons(array("email"=>$pi['email'],
				       "peer_id"=>NULL),array("person_id"));
    if ($known_pi) {
      $messages [] = " Note: PI was already known";
      $pi_id=$known_pi[0]['person_id'];
    } else {
      $pi['enabled']=FALSE;
      $pi_id=$adm->AddPerson($pi);
      $api_error = $adm->error();
      if (empty($api_error)) {
	$verboses [] = "PI created as disabled</p>";
      } else {
	$error .= $api_error;
      }
    }
    if ($adm->AddPersonToSite($pi_id,$site_id)) {
      $verboses [] = "PI attached to new site";
    }
    if ($adm->AddRoleToPerson('pi',$pi_id)) {
      $verboses [] = $pi['email'] . " granted PI role</p>";
    }
    
    if ($pi['email'] == $tech['email']) {
      // need to assign tech role so the registration request gets filled properly
      if ($adm->AddRoleToPerson('tech',$pi_id)) {
	$verboses [] = $pi['email'] . " granted Tech role</p>";
      }
    } else {
      // creating TECH
      $known_tech = $adm->GetPersons(array("email"=>$tech['email'],
					   "peer_id"=>NULL),array("person_id"));
      if ($known_tech) {
	$messages [] = " Note: Tech was already known";
	$tech_id=$known_tech[0]['person_id'];
      } else {
	$tech['enabled']=FALSE;
	$tech_id=$adm->AddPerson($tech);
	$api_error = $adm->error();
	if (empty($api_error)) {
	  $verboses [] = "Tech created as disabled</p>";
	} else {
	  $error .= $api_error;
	}
      }
      if ($adm->AddPersonToSite($tech_id,$site_id)) {
	$verboses [] = "Tech attached to new site";
      }
      if ($adm->AddRoleToPerson('tech',$tech_id)) {
	$verboses [] = $tech['email'] . " granted Tech role";
      }
      if ( ($tech['user-role']) && $adm->AddRoleToPerson('user',$tech_id) ) {
	$verboses [] = $tech['email'] . " granted User role";
      }
    }
  }
    

  // Show messages
  if (!empty($messages)) {
    print '<div class="messages status"><ul>';
    foreach ($messages as $line) {
      print "<li> $line";
    }
    print "</ul></div>";
  }
	
  if ($verbose && !empty($verboses)) {
    print '<div class="messages status"><ul>';
    print "<p> Verbose messages</p>";
    foreach ($verboses as $line) {
      print "<li> $line";
    }
    print "</ul></div>";
  }
	
  if (!empty($error)) {
    print '<div class="messages error">' . $error . '</div>';
  } else {
    // to prevent resubmit
    $site['site_id']="XXX";
  }
}

$self = $_SERVER['PHP_SELF'];
if (!empty($_SERVER['QUERY_STRING'])) {
  $self .= "?" . $_SERVER['QUERY_STRING'];
}

print <<<EOF
<div class="content">

<form action="$self" method="post">

<table border="0" width="100%" cellspacing="0" cellpadding="3">
EOF;

// Do not allow resubmits
if (empty($site['site_id'])) {
  print '<tr><td colspan=2 align=center><input type="submit" name="op" value="Register"  class="form-submit" /></td></tr>';
}

form_render_table2 ($site_form, $input, ! $empty_form);

// Do not allow resubmits
if (empty($site['site_id'])) {
  print '<tr><td colspan=2> &nbsp; </td></tr>';
  print '<tr><td colspan=2 align=center><input type="submit" name="op" value="Register"  class="form-submit" /></td></tr>';
}

print "</table></form></div>";

include 'plc_footer.php';

?>
