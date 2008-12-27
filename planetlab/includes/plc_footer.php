<?php
//
// PlanetLab footer handling. In a Drupal environment, this file
// outputs nothing.
//
// Mark Huang <mlhuang@cs.princeton.edu>
// Copyright (C) 2006 The Trustees of Princeton University
//
// $Id$ $
//

require_once 'plc_drupal.php';

if (!function_exists('drupal_page_footer')) {
  print <<<EOF
</body>
</html>

EOF;
}

?>