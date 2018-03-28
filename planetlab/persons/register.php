<?php
// $Id$
//
// Account registration and verification form. This form can be called
// in one of two ways:
//
// 1. ?first_name=FIRST_NAME&last_name=LAST_NAME&email=EMAIL...
//
// Called by the form at the bottom of the page to register a new
// account. If any required fields are missing, AddPerson() will fault
// and the specified fields will be highlighted. Otherwise, the
// account is registered (but not enabled), and VerifyPerson() sends
// the user a link back to this page.
//
// 2. ?id=PERSON_ID&key=VERIFICATION_KEY...
//
// Sent to the specified user by VerifyPerson(). If the user receives
// the message, then the registered e-mail address is considered
// valid, and registration can continue. VerifyPerson() is called
// again, and sends the current PI(s) (and support if the user is
// requesting a PI role) a link to the user's Account Details page to
// enable the account.
//
// Mark Huang <mlhuang@cs.princeton.edu>
// Copyright (C) 2007 The Trustees of Princeton University
//
// $Id$ $
//

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

require_once 'plc_functions.php';
require_once 'nifty.php';
require_once 'details.php';

drupal_set_title('Account Registration');

// Drupalish, but does not use Drupal itself to generate the form
global $person_form;

$person_form = array();
$person_form['first_name'] = array('title' => 'First name', 'required' => TRUE,
			    'maxlength' => 60, 'size' => 15);
$person_form['last_name'] = array('title' => 'Last name', 'required' => TRUE,
			    'maxlength' => 60, 'size' => 15);
$person_form['title'] = array('title' => 'Title', 'required' => FALSE,
		       'maxlength' => 60, 'size' => 5);
$person_form['phone'] = array('title' => 'Telephone', 'required' => FALSE,
		       'maxlength' => 60, 'size' => 20);
$person_form['email'] = array('title' => 'E-mail', 'required' => TRUE,
		       'maxlength' => 60, 'size' => 30);
$person_form['password'] = array('title' => 'Password', 'required' => TRUE,
		       'maxlength' => 60, 'size' => 20);
$person_form['site_ids'] = array('title' => 'Site', 'required' => TRUE);
if (0)
  $person_form['roles'] = array('title' => 'Additional Roles', 'required' => FALSE);

//////////////////// additional messages
$person_form['email']['comment'] = <<< EOF
Your <b>E-mail</b> address must be able to receive e-mail and will be
used as your $PLC_NAME username
EOF;


// dirty hack feb 2018; if this file can be found,
// its contents is used instead of the hard-wired message
// it is searched along php's include path, so it should be
// allright to save it as /etc/planetlab/php/person-registration.txt
// of course html tags like <code> and <br /> are OK
global $message_filename;
$message_filename = "person-registration.txt";

try {
    $person_form['site_ids']['comment'] = file_get_contents($message_filename, TRUE);
} catch (Exception $e) {
     $person_form['site_ids']['comment'] = "Select the site where you belong";
}

if (0)
  $person_form['roles']['comment'] = <<< EOF
Do not select the <b>Principal Investigator</b> or <b>Technical
Contact</b> roles unless you have spoken with the current PI of your
site, and you intend to assume either or both of these roles.
<br> Use Command-Clic to unselect or for multiple selection
EOF;

//////////////////// parse form values and store in $person
global $person;
$person = array();
foreach ($person_form as $name => $item) {
  if (!empty($_REQUEST[$name])) {
    $person[$name] = $_REQUEST[$name];
  }
}

// Filter out "Select a site"
if (!empty($person['site_ids'])) {
  $person['site_ids'] = array_filter($person['site_ids'],
				     create_function('$site_id', 'return intval($site_id) > 0;'));
}

//////////////////// minimal checking
function check_form ($person) {
  global $person_form;
  global $adm;

  // Look for missing/blank entries
  $missing = array();
  foreach ($person_form as $name => $item) {
    if ($item['required'] && empty($person[$name])) {
      $missing[] = $item['title'];
    }
  }

  // missing fields
  if (!empty($missing)) {
    $warnings=array();
    foreach ($missing as $field) $warnings []= "$field field is required.";
    print html_div(plc_itemize($warnings),"messages error");
    return FALSE;
  }

  // check that the email address is not already used on this peer
  $email=$person['email'];
  $already = $adm->GetPersons ( array('email'=>$person['email'],'peer_id'=>NULL) );
  if ( ! empty ($already) ) {
    print html_div(plc_itemize(array("Email $email already used !")),"messages error");
    return FALSE;
  }

  return TRUE;
}

//////////////////// perform api calls
function register_person ($person) {

  global $adm;

  $errors = errors_init ();

  // jan-2013 with improvements in plcapi-5.1-6 about managing persons and tags,
  // AddPerson has gone more picky and we need to remove some fields
  // that no longer are silently ignored by AddPerson
  $site_ids=$person['site_ids'];
  unset ($person['site_ids']);
  $roles=$person['roles'];
  unset ($person['roles']);

  $person_id = $adm->AddPerson($person);
  $errors = errors_record ($adm,$errors);

  if (empty($errors)) {
    $adm->begin();

    // Add person to requested sites
    foreach ($site_ids as $site_id) {
      $adm->AddPersonToSite($person_id, intval($site_id));
      $adm->SetPersonPrimarySite($person_id, intval($site_id));
    }

    // Add requested roles. Always add the user role.
    $adm->AddRoleToPerson('user', $person_id);
    if (!empty($roles)) {
      foreach ($roles as $role) {
	$adm->AddRoleToPerson($role, $person_id);
      }
    }

    // Send an e-mail containing a link back to this page, which will
    // verify the given e-mail address as valid. PIs can still create
    // and enable accounts on behalf of their users, they just have to
    // find and enable the accounts manually after registering them.
    $adm->VerifyPerson($person_id);

    // Disable submit button
    $person['person_id'] = $person_id;

    $adm->commit();
    $errors = errors_record ($adm,$errors);
  }

  if (!empty($errors)) {
    errors_display($errors);
  } else {
    $email=$person['email'];
    $text="Your registration request has been received. An e-mail has been sent to $email with further instructions.";
    print html_div($text,"messages status");
  }
}

////////////////////
if (!empty($person) && check_form ($person))
  register_person($person);

$PLC_NAME = htmlspecialchars(PLC_NAME);

// E-mail address verified, go ahead and notify the PI (and possibly
// support if a PI role was requested) that a registration request was
// received.
if (!empty($_REQUEST['id']) && !empty($_REQUEST['key'])) {
  $person_id = intval($_REQUEST['id']);
  if ($adm->VerifyPerson($person_id, $_REQUEST['key']) != 1) {
    plc_error($adm->error());
  } else {
    $persons = $adm->GetPersons(array($person_id));
    $person = $persons[0];

    // Remove the password field from the form so that it is not
    // highlighted as missing.
    unset($person_form['password']);

    $messages = array();
    $messages []= 'Your e-mail address has been verified. ';
    $messages []= 'The PI(s) at your site have been notified of your account registration ';

    if (! in_array('pi', $person['roles'])) {
      $messages []= 'They are responsible for enabling your account.';
    } else {
      $support = PLC_MAIL_SUPPORT_ADDRESS;
      $messages []= "They should contact <a href=\"mailto:$support\">$PLC_NAME Support <$support></a>. ";
      $messages []= "$PLC_NAME Support will enable your account once authorized by your PI(s).";
    }

    plc_itemize($messages,"messages status");
  }
}

$adm->begin();

// All defined sites
// cannot register with foreign site
// we also hide sites that are created by sfa
// as well as the ones that have a disabled_registration tag set
$adm->GetSites(array('is_public' => TRUE, 'peer_id' => NULL,
                     'sfa_created' => NULL,
                     'disabled_registration' => NULL,
                     '-SORT'=>'name'),
	           array('site_id', 'name', 'enabled', 'peer_id',
                     'sfa_created', 'disabled_registration'));
// All defined roles
$adm->GetRoles();

list($sites, $roles) = $adm->commit();

// Prepend invalid site to beginning of list to force user to select a site
$sites = array_merge(array(array('site_id' => -1, 'name' => 'Select a site', 'enabled' => TRUE)), $sites);

// Drop down selection box for each site
function site_option($site) {
  global $person;

  $site_id = $site['site_id'];

  if (!empty($person['site_ids']) && in_array($site_id, $person['site_ids'])) {
    $selected = 'selected="selected"';
  } else {
    $selected = "";
  }

  $option = "<option value='$site_id' $selected";
  if ( ! $site['enabled'] )
    $option .= " disabled='disabled'";
  $option .= ">";
  $option .= htmlspecialchars($site['name']);
  # Safari/IE do not implement disabled correctly
  if ( ! $site['enabled'] )
    $option .= " (disabled, or pending registration)";
  $option .= "</option>";
  return $option;
}
$site_options = implode("\n", array_map('site_option', $sites));

// Do not tempt users to register for the admin role. Administrators
// should register normally, then be granted the admin role by another
// admin. Also, all accounts should have the user role (see above).
foreach ($roles as $i => $role) {
  if ($role['name'] == 'admin' || $role['name'] == 'user') {
    unset($roles[$i]);
  }
}

// Standard roles
global $standard_roles;
$standard_roles = array('user' => 'User',
			'pi' => 'Principal Investigator',
			'tech' => 'Technical Contact',
			'admin' => 'Administrator');

// Drop down selection box for each role
function role_option($role) {
  global $standard_roles, $selected_roles;

  $name = $role['name'];

  if (!empty($person['roles']) && in_array($name, $person['roles'])) {
    $selected = 'selected="selected"';
  } else {
    $selected = "";
  }

  $display = array_key_exists($name, $standard_roles) ? $standard_roles[$name] : $name;

  $option = "<option value=\"$name\" $selected>";
  $option .= htmlspecialchars($display);
  $option .= "</option>";
  return $option;
}
$role_options = implode("\n", array_map('role_option', $roles));


$nifty=new PlekitNifty ('register','person-register','medium');
$nifty->start();
$details = new PlekitDetails(TRUE);
$details -> start();
$details->form_start(l_person_register(),array());

// Do not allow resubmits
$register_button="<input type='submit' name='op' value='Register'  class='form-submit' />";
if (empty($person['person_id'])) {
  $details->tr($register_button,'center');
  $details->space();
}

foreach ($person_form as $name => $item) {

  if ( ! empty($item['comment'])) {
    $details->space();
    $details->tr($item['comment'] . ":");
  }

  $title = $item['title'];
  $required = $item['required'] ? '<span class="form-required" title="This field is required.">*</span>' : "";
  $class = $item['required'] ? "required" : "";
  if ($item['required'] && !empty($person) && empty($person[$name])) {
    $class .= " error";
  }

  // Label part
  $left_part = "<label class='$class' for='edit-$name'>$title: $required</label>";

  // input part
  switch ($name) {

  case 'site_ids':
    $right_part= "<select name='site_ids[]' id='edit-site_ids' class='form-select $class'> $site_options </select>";
    break;

  case 'roles':
    $right_part="";
    if (0) { /* Not letting users select PI or Tech any more.  Its only lead to confusion and abuse. */
      $right_part = "<select name='roles[]' multiple='multiple' id='edit-roles' class='form-select $class'> $role_options </select>";
    }
    break;

  default:
    $maxlength = $item['maxlength'];
    $size = $item['size'];
    $value = !empty($person[$name]) ? $person[$name] : "";
    $type = $name == 'password' ? "password" : "text";
    $right_part = "<input type='$type' maxlength='$maxlength' name='$name' id='edit-$name' size='$size' value='$value' class='form-text $class'>";
    break;
  }

  $details->th_td($left_part,$right_part);
}

// Do not allow resubmits
if (empty($person['person_id'])) {
  $details->space();
  $details->tr($register_button,'center');
}

$details->form_end();
$details->end();
$nifty->end();

include 'plc_footer.php';

?>
