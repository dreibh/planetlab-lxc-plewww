<?php
//
// Login form
//
// Mark Huang <mlhuang@cs.princeton.edu>
// Copyright (C) 2006 The Trustees of Princeton University
//
// $Id$ $
//

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Login');
include 'plc_header.php';

if (!empty($_REQUEST['email']) &&
    !empty($_REQUEST['password'])) {
  $plc = new PLCSession($_REQUEST['email'], $_REQUEST['password']);

  if ($plc->person) {
    // Login admins to Drupal as the superuser
    if (in_array('admin', $plc->person['roles']) &&
	function_exists('user_load')) {
      global $user;
      $user = user_load(array('uid' => 1));
    }

    if (empty($_REQUEST['url'])) {
      // XXX Redirect to default home page
      Header("Location: /");
      exit();
    } else {
      // Make sure that redirections are always local
      $url = urldecode($_REQUEST['url']);
      if ($url[0] != "/") {
	$url = "/$url";
      }
      Header("Location: $url");
      exit();
    }
  } else {
    // XXX Use our own stylesheet instead of drupal.css
    print '<div class="messages error">Sorry. Unrecognized username or password.</div>';
  }
}

$self = $_SERVER['PHP_SELF'];
if (!empty($_SERVER['QUERY_STRING'])) {
  $self .= "?" . $_SERVER['QUERY_STRING'];
}

$url = htmlspecialchars($_REQUEST['url']);

// XXX Use our own stylesheet instead of drupal.css
print <<<EOF
<div class="content">
<form action="$self" method="post">

<table border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td>
      <div class="form-item">
        <label for="edit-name">E-mail: <span class="form-required" title="This field is required.">*</span></label>
	<input type="text" maxlength="60" name="email" id="edit-name" size="30" value="" class="form-text required" />
      </div>
      <div class="form-item">
	<label for="edit-password">Password: <span class="form-required" title="This field is required.">*</span></label>
	<input type="password" maxlength="" name="password" id="edit-password" size="30" class="form-text required" />
      </div>
      <input type="submit" name="op" value="Log in"  class="form-submit" />
      <p><p><a href="/db/persons/reset_password.php">Forgot your password?</a></p>
      <p><a href="/db/persons/register.php">Create an account</a></p>
      <p><a href="/db/sites/register.php">File a site registration</a></p>
      <input type="hidden" name="url" value="$url" />
    </td>
  </tr>
</table>

</form>
</div>

EOF;

include 'plc_footer.php';

?>
