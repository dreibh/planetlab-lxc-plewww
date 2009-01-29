<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';

// add or update
if ( $_GET['action'] ) {
  include 'tag_form.php';
// list all
 } else if ( ! $_GET['add'] ) {
  include 'tags.php';
// actually set a tag on an object
 } else {
  include 'tag_set.php';
 }

// Print footer
include 'plc_footer.php';

?>
