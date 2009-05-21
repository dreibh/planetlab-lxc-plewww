<?php
//
// Site list
//
// Mark Huang <mlhuang@cs.princeton.edu>
// Copyright (C) 2006 The Trustees of Princeton University
//
// $Id$ $
//

// Get API handle
require_once 'plc_session.php';
global $adm;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Sites');
include 'plc_header.php';

?>

<p>The following sites currently host or plan to host <?php echo PLC_NAME; ?> nodes:</p>

<ul>

<?php

// All defined sites
$sites = $adm->GetSites(array('is_public' => TRUE, 'peer_id' => NULL), array('name', 'url'));

foreach ($sites as $site) {
  $name = htmlspecialchars($site['name']);
  $url = $site['url'];
  print "<li>";
  if ($url) {
    print "<a href=\"$url\">$name</a>";
  } else {
    print "$name";
  }
  print "</li>";
}

?>

</ul>

<?php

include 'plc_footer.php';

?>
