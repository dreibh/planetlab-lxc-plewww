<?php
//
// Logout form
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
#drupal_set_title('Login');
include 'plc_header.php';

// Invalidate session
if ($plc->person) {
  $plc->BecomeSelf();
}


Header("Location: /db/persons/index.php");

?>
