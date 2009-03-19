<?php
//
// Slice list
//
// Mark Huang <mlhuang@cs.princeton.edu>
// Copyright (C) 2006 The Trustees of Princeton University
//
// $Id$ $
//

// Get API handle
require_once 'plc_session.php';
global $adm;

// Get PlanetFlow stats
global $planetflow, $active_bytes;
include_once '_gen_planetflow.php';

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Projects');
include 'plc_header.php';

if (isset($_REQUEST['active'])) {

?>

<p>A slice is essentially a login account on a set of nodes. The
following is a list of the most active slices; you may also view a
list of <a href="/db/pub/slices.php">all slices</a>.</p>

<?php } else { ?>

<p>A slice is essentially a login account on a set of nodes. The
following is a list of all slices; you may also view a list of <a
href="/db/pub/slices.php?active">just the most active slices</a>.</p>

<?php } ?>

<p>To provide feedback to the principals responsible for each slice,
check the <b>Feedback</b> box for each slice that you would like to
comment on, then click <b>Provide Feedback</b>.</p>

<form method="post" action="/db/pub/feedback.php">

<table cellspacing="0">
  <thead>
    <tr>
      <th>Slice</th>
      <th>Description</th>
      <th>Site</th>
      <th>MBytes/day</th>
      <th>Feedback</th>
    </tr>
  </thead>
  <tbody>

<?php

// Get all sites
$sites = array();
foreach ($adm->GetSites(NULL, array('abbreviated_name', 'site_id'))
	 as $site) {
  $sites[$site['site_id']] = $site;
}

// Get all slices
$slices = $adm->GetSlices(NULL, array('name', 'url', 'description', 'site_id'));

// List just the active slices
if (isset($_REQUEST['active'])) {
  // Byte threshold to be considered "active"
  if (!empty($_REQUEST['mbytes'])) {
    $active_bytes = intval($_REQUEST['mbytes'])*1024*1024;
  } else {
    $active_bytes = 100*1024*1024;
  }

  // Filter just the active slices
  function __active_slice($slice) {
    global $planetflow, $active_bytes;
    return isset($planetflow[$slice['name']]) &&
      ($planetflow[$slice['name']]['bytes'] >= $active_bytes);
  }
  $slices = array_filter($slices, '__active_slice');
  
  // Sort active slices by bytes in descending order
  function __cmp_slices_by_bytes($slicea, $sliceb) {
    global $planetflow;
    return ($planetflow[$slicea['name']]['bytes'] > $planetflow[$sliceb['name']]['bytes']) ? -1 : 1;
  }
  usort($slices, '__cmp_slices_by_bytes');
} else {
  // Alphabetically sort slices
  sort_slices($slices);
}

$class = "";  
foreach ($slices as $slice) {
  print "<tr class=\"$class\">";

  print '<td valign="top"><a name="' .
    htmlspecialchars($slice['name']) .
    '">' .
    htmlspecialchars($slice['name']) .
    '</a></td>';

  print '<td valign="top">';
  print htmlspecialchars(trim($slice['description']));
  if (!empty($slice['url'])) {
    if (strncasecmp($slice['url'], "http", 4) != 0) {
      $slice['url'] = "http://" . $slice['url'];
    }
    print '<br /><a href="' . htmlspecialchars($slice['url']) . '">More details...</a>';
  }
  print '</td>';

  print '<td valign="top">';
  if (isset($sites[$slice['site_id']])) {
    $site = $sites[$slice['site_id']];
    print htmlspecialchars($site['abbreviated_name']);
  }
  print '</td>';

  print '<td valign="top">';
  if (isset($planetflow[$slice['name']]) &&
      isset($planetflow[$slice['name']]['bytes']) &&
      $planetflow[$slice['name']]['bytes']/1024/1024 >= 1) {
    print number_format($planetflow[$slice['name']]['bytes']/1024/1024, 0, '.', ',');
  }
  print '</td>';

  print '<td valign="top">';
  print '<input type="checkbox" name="slices[]" value="' . htmlspecialchars($slice['name']) . '" />';
  print '</td>';

  print '</tr>';

  $class = $class == "oddrow" ? "" : "oddrow";
}

?>

  </tbody>
</table>

<input type="submit" value="Provide Feedback" />

</form>

<?php

include 'plc_footer.php';

?>
