<?php
//
// Provide feedback about slice(s)
//
// Mark Huang <mlhuang@cs.princeton.edu>
// Copyright (C) 2006 The Trustees of Princeton University
//
// $Id: feedback.php 144 2007-03-28 07:52:20Z thierry $
//

// Get API handle
require_once 'plc_session.php';
global $adm;

// Get sorting functions
require_once 'plc_sorts.php';

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Slice Feedback');
include 'plc_header.php';

if (isset($_REQUEST['slices']) && is_array($_REQUEST['slices'])) {
  $slice_names = $_REQUEST['slices'];
  $selected = 'selected="selected"';
} else {
  $slice_names = array();
}

// Return all slices and users of those slices
$slices = $adm->GetSlices($slice_names, array('name', 'person_ids'));

$email = "";
if (isset($_REQUEST['email'])) {
  // Email Address Verification with PHP
  // http://www.devshed.com/c/a/PHP/Email-Address-Verification-with-PHP/
  // checks proper syntax
  $email = $_REQUEST['email'];  
  if (preg_match("/^[a-zA-Z0-9_\.-]+@[a-zA-Z0-9\.-]+$/", $email)) {
    list($username, $domain) = split('@', $email);
    // prevent default domain from being appended
    $domain .= ".";
    // check if the domain has MX records, or is at least resolvable
    if (getmxrr($domain, $mxhosts) || checkdnsrr($domain, 'ANY')) {
      $valid_email = $email;
    }
  }
}

$comments = "";
if (isset($_REQUEST['comments'])) {
  $comments = $_REQUEST['comments'];
}

if (isset($_REQUEST['submitted']) && isset($valid_email) && $slices) {
  foreach ($slices as $slice) {
    $to = array();

    if (defined('PLC_MAIL_SLICE_ADDRESS')) {
      $to[] = str_replace('SLICE', $slice['name'], PLC_MAIL_SLICE_ADDRESS);
    } else {
      // Get all users in the slices
      foreach ($adm->GetPersons($slice['person_ids'], array('person_id', 'email'))
	       as $person) {
	$to[] = $person['email'];
      }
    }

    $to = array_unique($to);

    $headers  = "Content-type: text/plain\r\n";
    $headers .= "From: $valid_email\r\n";
    $headers .= "Reply-To: $valid_email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $subject  = PLC_NAME . " Slice Feedback - " . $slice['name'];

    $message  = $subject . "\r\n";
    $message .= "\r\n";

    // Print comments
    $message .= PLC_NAME . " has received feedback from $valid_email regarding\r\n";
    $message .= "your slice:\r\n";
    $message .= "\r\n";
    $message .= strip_tags($comments) . "\r\n";
    $message .= "\r\n";
    
    $message .= "Please contact $valid_email at your convenience.\r\n";

    // XXX API should handle this, since it handles Unicode
    // properly. Since neither PHP4 nor PHP5 can pass Unicode via XML-RPC right
    // now anyway, though, it doesn't really ematter.
    if (PLC_MAIL_ENABLED) {
      mail(implode(", ", $to), $subject, $message, $headers);
    }
  }
   
  // Pause to limit spams
  sleep(5);

  echo <<<EOF

<h1>Thank You</h1>

<p>Thank you for your feedback. The principals responsible for each
slice will respond to your concerns as soon as possible.</p>

EOF;

  if (PLC_WWW_DEBUG) {
    print '<pre>';
    print "To: " . implode(", ", $to) . "\r\n";
    print htmlspecialchars($headers);
    print "\r\n";
    print htmlspecialchars($message);
    print '</pre>';
  }

} else {

  $self = $_SERVER['PHP_SELF'];
  if (!empty($_SERVER['QUERY_STRING'])) {
    $self .= "?" . $_SERVER['QUERY_STRING'];
  }

  echo <<<EOF

<p>Select the slices for which you would like to provide feedback.</p>

<form method="post" action="$self">

EOF;

$select_size = min(10, count($slices));
print "<select name=\"slices[]\" size=\"$select_size\" multiple=\"multiple\">";
foreach ($slices as $slice) {
  $slice_name = htmlspecialchars($slice['name']);
  print "<option $selected value=\"$slice_name\">$slice_name</option>";
}
print '</select>';

$email = htmlspecialchars($email);
$comments = htmlspecialchars($comments);

echo <<<EOF

<p>Briefly describe your concerns in the <b>Comments</b> field, provide a
<b>Contact E-mail</b> address, and click <b>Send</b>.</p>

<p>
<b>Contact E-mail</b><br />
<input type="text" name="email" size="40" value="$email" /><br />

<b>Comments</b><br />
<textarea rows="10" cols="80" name="comments">$comments</textarea><br />

<input type="submit" name="submitted" value="Send" />

</form>

EOF;

}

include 'plc_footer.php';
