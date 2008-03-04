<?php
//
// Require this file to require login to a page, e.g.
//
// require_once 'plc_login.php';
//
// Mark Huang <mlhuang@cs.princeton.edu>
// Copyright (C) 2006 The Trustees of Princeton University
//
// $Id: plc_login.php 144 2007-03-28 07:52:20Z thierry $ $
//

require_once 'plc_session.php';
global $plc, $api;

if (!$plc->person) {
  // Where they were trying to go
  $url = $_SERVER['PHP_SELF'];
  if (!empty($_SERVER['QUERY_STRING'])) {
    $url .= "?" . $_SERVER['QUERY_STRING'];
  }

  Header("Location: /db/login.php?url=" . urlencode($url));
  exit();
}

?>
