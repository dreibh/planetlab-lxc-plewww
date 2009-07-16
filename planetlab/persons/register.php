
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
drupal_set_title('Account Registration');
include 'plc_header.php';

// Drupalish, but does not use Drupal itself to generate the form
$form = array();
$form['first_name'] = array('title' => 'First name', 'required' => TRUE,
			    'maxlength' => 60, 'size' => 15);
$form['last_name'] = array('title' => 'Last name', 'required' => TRUE,
			    'maxlength' => 60, 'size' => 15);
$form['title'] = array('title' => 'Title', 'required' => FALSE,
		       'maxlength' => 60, 'size' => 5);
$form['phone'] = array('title' => 'Telephone', 'required' => FALSE,
		       'maxlength' => 60, 'size' => 20);
$form['email'] = array('title' => 'E-mail', 'required' => TRUE,
		       'maxlength' => 60, 'size' => 30);
$form['password'] = array('title' => 'Password', 'required' => TRUE,
		       'maxlength' => 60, 'size' => 20);
$form['site_ids'] = array('title' => 'Site', 'required' => TRUE);
if (0)
$form['roles'] = array('title' => 'Additional Roles', 'required' => FALSE);

//////////////////// additional messages
$form['email']['comment'] = <<< EOF
Your <b>E-mail</b> address must be able to receive e-mail and will be
used as your $PLC_NAME username
EOF;

$form['site_ids']['comment'] = <<< EOF
Select the site where you belong 
EOF;

if (0)
$form['roles']['comment'] = <<< EOF
Do not select the <b>Principal Investigator</b> or <b>Technical
Contact</b> roles unless you have spoken with the current PI of your
site, and you intend to assume either or both of these roles.
<br> Use Command-Clic to unselect or for multiple selection
EOF;

////////////////////
global $person;
$person = array();
foreach ($form as $name => $item) {
  if (!empty($_REQUEST[$name])) {
    $person[$name] = $_REQUEST[$name];
  }
}

// Filter out "Select a site"
if (!empty($person['site_ids'])) {
  $person['site_ids'] = array_filter($person['site_ids'],
				     create_function('$site_id', 'return intval($site_id) > 0;'));
}

if (!empty($person)) {
  // Look for missing/blank entries
  $missing = array();
  foreach ($form as $name => $item) {
    if ($item['required'] && empty($person[$name])) {
      $missing[] = $item['title'];
    }
  }
  if (!empty($missing)) {
    $error = "<ul>";
    foreach ($missing as $field) {
      $error .= "<li>$field field is required.</li>";
    }
    $error .= "</ul>";
  }

  if (empty($error)) {
    // N.B.: site_ids and roles are ignored by AddPerson()
    $person_id = $adm->AddPerson($person);
    $error = $adm->error();
  }

  if (empty($error)) {
    $adm->begin();

    // Add person to requested sites
    foreach ($person['site_ids'] as $site_id) {
      $adm->AddPersonToSite($person_id, intval($site_id));
      $adm->SetPersonPrimarySite($person_id, intval($site_id));
    }

    // Add requested roles. Always add the user role. 
    $adm->AddRoleToPerson('user', $person_id);
    if (!empty($person['roles'])) {
      foreach ($person['roles'] as $role) {
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
    $error = $adm->error();
  }

  if (!empty($error)) {
    print '<div class="messages error">' . $error . '</div>';
  } else {
    print '<div class="messages status">Your registration request has been received. An e-mail has been sent to ';
    print $person['email'];
    print ' with further instructions.</div>';
  }
}

$PLC_NAME = htmlspecialchars(PLC_NAME);

// E-mail address verified, go ahead and notify the PI (and possibly
// support if a PI role was requested) that a registration request was
// received.
if (!empty($_REQUEST['id']) && !empty($_REQUEST['key'])) {
  $person_id = intval($_REQUEST['id']);
  if ($adm->VerifyPerson($person_id, $_REQUEST['key']) != 1) {
    print '<div class="messages error">' . $adm->error() . '.</div>';
  } else {
    $persons = $adm->GetPersons(array($person_id));
    $person = $persons[0];

    // Remove the password field from the form so that it is not
    // highlighted as missing.
    unset($form['password']);

    print '<div class="messages status">';
    print 'Your e-mail address has been verified. ';
    print 'The PI(s) at your site have been notified of your account registration ';

    if (in_array('pi', $person['roles'])) {
      $support = PLC_MAIL_SUPPORT_ADDRESS;
      print " and should contact <a href=\"mailto:$support\">$PLC_NAME Support <$support></a>. ";
      print " $PLC_NAME Support will enable your account if authorized by your PI(s).";
    } else {
      print ' and are responsible for enabling your account.';
    }

    print '</div>';
  }
}

$self = $_SERVER['PHP_SELF'];
if (!empty($_SERVER['QUERY_STRING'])) {
  $self .= "?" . $_SERVER['QUERY_STRING'];
}

$adm->begin();

// All defined sites
// cannot register with foreign site
$adm->GetSites(array('is_public' => TRUE, 'peer_id' => NULL,'-SORT'=>'name'), 
	       array('site_id', 'name','enabled','peer_id'));
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

  $option = "<option value=\"$site_id\" $selected";
  if ( ! $site['enabled'] )
    $option .= " disabled='disabled'";
  $option .= ">";
  $option .= htmlspecialchars($site['name']);
  # Safari/IE do not implement disabled correctly
  if ( ! $site['enabled'] )
    $option .= " (pending registration)";
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

$self = $_SERVER['PHP_SELF'];
if (!empty($_SERVER['QUERY_STRING'])) {
  $self .= "?" . $_SERVER['QUERY_STRING'];
}

print <<<EOF
<div class="content">

<form action="$self" method="post">

<table border="0" cellpadding="5" cellspacing="0">
EOF;

foreach ($form as $name => $item) {

  if ( ! empty($item['comment'])) {
    $comment=$item['comment'];
    print "<tr><td colspan='2'> &nbsp; </td></tr>";
    print "<tr><td colspan='2'> $comment: </td></tr>";
  }

  $title = $item['title'];
  $required = $item['required'] ? '<span class="form-required" title="This field is required.">*</span>' : "";
  $class = $item['required'] ? "required" : "";
  if ($item['required'] && !empty($person) && empty($person[$name])) {
    $class .= " error";
  }

  // Label part
  print "<tr>";
  print <<<EOF
    <td><label class="$class" for="edit-$name">$title: $required</label></td>\n
EOF;

  // input part
  switch ($name) {

  case 'site_ids':
    print <<<EOF
        <td><select name="site_ids[]" id="edit-site_ids" class="form-select $class">
	  $site_options
        </select></td>\n
EOF;
    break;

  case 'roles':
    if (0) { /* Not letting users select PI or Tech any more.  Its only lead to confusion and abuse. */
    print <<<EOF
        <td><select name="roles[]" multiple="multiple" id="edit-roles" class="form-select $class">
          $role_options
        </select></td>\n
EOF;
    }
    break;

  default:
    $maxlength = $item['maxlength'];
    $size = $item['size'];
    $value = !empty($person[$name]) ? $person[$name] : "";
    $type = $name == 'password' ? "password" : "text";
    print <<<EOF
	<td><input type="$type" maxlength="$maxlength" name="$name" id="edit-$name" size="$size" value="$value" class="form-text $class"></td>\n
EOF;

  }

  print "</tr>\n";
}

// Do not allow resubmits
if (empty($person['person_id'])) {
  print '<tr><td colspan="2"><input type="submit" name="op" value="Register"  class="form-submit" /></td></tr>';
}

print <<<EOF
</table>

</form>
</div>
EOF;

include 'plc_footer.php';

?>
