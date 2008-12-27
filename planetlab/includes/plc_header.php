<?php
//
// PlanetLab header handling. In a Drupal environment, this file
// outputs nothing.
//
// Mark Huang <mlhuang@cs.princeton.edu>
// Copyright (C) 2006 The Trustees of Princeton University
//
// $Id$ $
//

require_once 'plc_drupal.php';
drupal_set_html_head('<link href="/planetlab/css/plc_style.css" rel="stylesheet" type="text/css"/>');

if (!function_exists('drupal_page_header')) {
  $title = drupal_get_title();
  $head = drupal_get_html_head();

  print <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">

<head>
  <title>$title</title>
  $head
</head>

<body>

EOF;
}

?>
