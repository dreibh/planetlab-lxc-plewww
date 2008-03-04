<?php
//
// Drupal compatibility
//
// Mark Huang <mlhuang@cs.princeton.edu>
// Copyright (C) 2006 The Trustees of Princeton University
//
// $Id: plc_drupal.php 144 2007-03-28 07:52:20Z thierry $ $
//

if (!function_exists('drupal_set_title')) {
  function drupal_set_title($title = NULL)
  {
    static $stored_title;

    if (isset($title)) {
      $stored_title = $title;
    }
    return $stored_title;
  }
}

if (!function_exists('drupal_get_title')) {
  function drupal_get_title()
  {
    return drupal_set_title();
  }
}

if (!function_exists('drupal_set_html_head')) {
  function drupal_set_html_head($data = NULL)
  {
    static $stored_head = '';

    if (!is_null($data)) {
      $stored_head .= $data ."\n";
    }
    return $stored_head;
  }
}

if (!function_exists('drupal_get_html_head')) {
  function drupal_get_html_head()
  {
    $output = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
    // XXX Insert CSS link here
    return $output . drupal_set_html_head();
  }
}

?>
