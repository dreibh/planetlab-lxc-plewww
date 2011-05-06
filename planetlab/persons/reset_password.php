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

// Only display dialogs if the user is not logged in.
if ( !$plc->person) {
    // Print header
    require_once 'plc_drupal.php';
    include 'plc_header.php';

    if (!empty($_REQUEST['id']) && !empty($_REQUEST['key'])) {
      $person_id = intval($_REQUEST['id']);
      drupal_set_title('Password Reset: Confirmed');
      if ($adm->ResetPassword($person_id, $_REQUEST['key']) != 1) {
        print '<div class="messages error">' . $adm->error() . '.</div>';
      } else {
        drupal_set_html_head("<meta http-equiv=\"refresh\" content=\"60; URL=/\"");

        print '<div class="messages status">';
        print "Success!  We've sent you another e-mail with your new temporary password. <br/>"; 
        print "You can login using this temporaray password.  <br/>"; 
        print "Please change it once you login by visiting 'My Account' and updating your password. ";
        print '</div>';
      }
    } elseif (!empty($_REQUEST['email'])) {
      drupal_set_title('Password Reset: Request Sent');
      if ($adm->ResetPassword($_REQUEST['email']) != 1) {
        print '<div class="messages error">' . $adm->error() . '.</div>';
      } else {
        print '<div class="messages status">';
        print "We've sent an e-mail to " . $_REQUEST['email'] . " that will allow you to confirm the password reset. <br/>";
        print "Please check your email now and follow the link contained there to reset your password. ";
        print '</div>';
      }
    } else {

        drupal_set_title('Password Reset');
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

    }
    include 'plc_footer.php';

} else {
  Header("Location: /db/persons/index.php?id=" . $plc->person['person_id']);
  exit();
}


?>
