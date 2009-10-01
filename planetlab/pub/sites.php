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


<div id='public_sites'>
<p>The following sites currently host or plan to host <?php echo PLC_NAME; ?> nodes.</p>

<p> Also available for download in <a href="/sites/sites.kml">KML format for Google Earth.</a> </p> 
</div>

<?php
require_once 'plc_functions.php';
require_once 'plc_peers.php';
require_once 'table.php';

$headers = array();
$headers['Peer']='string';
$headers['Name']='string';
$headers['Lon']='float';
$headers['Lat']='float';

$table=new PlekitTable ("sites",$headers,1,array('pagesize'=>10000,'pagesize_area'=>false));
$table->start();

$peers=new Peers($adm);

// All defined sites
$sites = $adm->GetSites(array('is_public' => TRUE), 
			array('peer_id','name', 'url','latitude','longitude'));


foreach ($sites as $site) {
  $name = htmlspecialchars($site['name']);
  $url = $site['url'];

  $table->row_start();
  $peers->cell($table,$site['peer_id']);
  if ($url) 
    $table->cell(href($url,$name));
  else
    $table->cell($name);
  $table->cell($site['longitude']);
  $table->cell($site['latitude']);
  $table->row_end();
}
$table->end();

include 'plc_footer.php';

?>
