<?php
//
// PlanetLab footer handling. In a Drupal environment, this file
// outputs nothing.
//
// Mark Huang <mlhuang@cs.princeton.edu>
// Copyright (C) 2006 The Trustees of Princeton University
//
// $Id: plc_footer.php 144 2007-03-28 07:52:20Z thierry $ $
//

require_once 'plc_drupal.php';

if (!function_exists('drupal_page_footer')) {
  print <<<EOF
</body>
</html>

EOF;
}

?>