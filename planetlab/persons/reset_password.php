<?php
//
// Reset password form
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
drupal_set_title('Reset Password');
include 'plc_header.php';

if (!empty($_REQUEST['id']) && !empty($_REQUEST['key'])) {
  $person_id = intval($_REQUEST['id']);
  if ($adm->ResetPassword($person_id, $_REQUEST['key']) != 1) {
    print '<div class="messages error">' . $adm->error() . '.</div>';
  } else {
    drupal_set_html_head("<meta http-equiv=\"refresh\" content=\"5; URL=/db/common/login.php\"");
    print '<div class="messages status">';
    print "An e-mail has been sent to you with your new temporary password. ";
    print "Please change this password as soon as possible. ";
    print "You will be re-directed to the login page in 5 seconds.";
    print '</div>';
  }
} elseif (!empty($_REQUEST['email'])) {
  if ($adm->ResetPassword($_REQUEST['email']) != 1) {
    print '<div class="messages error">' . $adm->error() . '.</div>';
  } else {
    drupal_set_html_head("<meta http-equiv=\"refresh\" content=\"5; URL=/db/common/login.php\"");
    print '<div class="messages status">';
    print "An e-mail has been sent to " . $_REQUEST['email'] . " with further instructions. ";
    print "You will be re-directed to the login page in 5 seconds.";
    print '</div>';
  }
}

$self = $_SERVER['PHP_SELF'];
if (!empty($_SERVER['QUERY_STRING'])) {
  $self .= "?" . $_SERVER['QUERY_STRING'];
}

// XXX Use our own stylesheet instead of drupal.css
print <<<EOF
<div class="content">
<form action="$self" method="post">

<table border="0" cellpadding="0" cellspacing="0" id="content">
  <tr>
    <td>
      <div class="form-item">
	E-mail: <span class="form-required" title="This field is required.">*</span></label>
	<input type="text" maxlength="60" name="email" id="edit-name" size="30" value="" class="form-text required" />
      </div>
      <input type="submit" name="op" value="Reset password"  class="form-submit" />
    </td>
  </tr>
</table>

</form>
</div>

EOF;

include 'plc_footer.php';

?>
