<?php

// $Id$

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

$header_tablesort_js='
<script type="text/javascript" src="/planetlab/tablesort/tablesort.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/customsort.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/paginate.js"></script>
<script type="text/javascript" src="/planetlab/minitab/minitab.js"></script>
<script type="text/javascript" src="/planetlab/js/plc_paginate.js"></script>
<script type="text/javascript" src="/planetlab/js/plc_filter.js"></script>
';

$header_tablesort_css='
<link href="/planetlab/minitab/minitab.css" rel="stylesheet" type="text/css" />
<link href="/planetlab/css/plc_style.css" rel="stylesheet" type="text/css" />
<link href="/planetlab/css/plc_table.css" rel="stylesheet" type="text/css" />
<link href="/planetlab/css/plc_paginate.css" rel="stylesheet" type="text/css" />
';

drupal_set_html_head($header_tablesort_js);
drupal_set_html_head($header_tablesort_css);

// -------------------- 
$nodepattern=$_GET['nodepattern'];
$peerscope=$_GET['peerscope'];
$tablesize=25;

drupal_set_title('Nodes');

require_once 'plc_minitab.php';
$minitab=array("Old page"=>"/db/nodes/index.php",
	       "About"=>"/db/about.php",
	       "Logout"=>"/planetlab/logout.php",
	       "And other buttons"=>"http://www.google.com",
	       "For demo purposes"=>"/undefined");
plc_show_options($minitab);

// -------------------- 
$peer_filter=array();

// fetch nodes - use nodepattern for server-side filtering
$node_columns=array('hostname','site_id','node_id','boot_state','interface_ids','peer_id');
if ($nodepattern) {
  $node_filter['hostname']=$nodepattern;
 } else {
  $node_filter=array('hostname'=>"*");
 }

// server-side selection on peerscope
list ( $peer_filter, $peer_label) = plc_peer_info($api,$_GET['peerscope']);
$node_filter=array_merge($node_filter,$peer_filter);

// go
$nodes=$api->GetNodes($node_filter,$node_columns);

// build site_ids - interface_ids
$site_ids=array();
$interface_ids=array();
foreach ($nodes as $node) {
  $site_ids []= $node['site_id'];
  $interface_ids = array_merge ($interface_ids,$node['interface_ids']);
}

// fetch related interfaces
$interface_columns=array('ip','node_id','interface_id');
$interface_filter=array('is_primary'=>TRUE,'interface_id'=>$interface_ids);
$interfaces=$api->GetInterfaces($interface_filter,$interface_columns);

$interface_hash=array();
foreach ($interfaces as $interface) {
    $interface_hash[$interface['node_id']]=$interface;
}

// fetch related sites
$site_columns=array('site_id','login_base');
$site_filter=array('site_id'=>$site_ids);
$sites=$api->GetSites($site_filter,$site_columns);

$site_hash=array();
foreach ($sites as $site) {
    $site_hash[$site['site_id']]=$site;
}

// fetch peers
$peer_columns=array('peer_id','shortname');
$peer_filter=array();
$peers = $api->GetPeers($peer_filter,$peer_columns);

$peer_hash=array();
foreach ($peers as $peer) {
    $peer_hash[$peer['peer_id']]=$peer;
}

?>

<!------------------------------------------------------------>
<!-- instantiate generic mechanisms for nodes -->
<script type"text/javascript">
function nodes_paginator (opts) {
  plc_table_paginator (opts,"nodes");
}
function nodes_filter () {
  plc_table_filter("nodes","search_text","nodes_and");
}
</script>

<br/>
<!------------------------------------------------------------>
<table class='table_dialogs'> <tr>
<td class='table_flushleft'>
<form class='table_size'>
  <input class='table_size_input' type='text' id='tablesize_text' value="<?php echo $tablesize; ?>" 
  onkeyup='plc_table_setsize("nodes","tablesize_text", "<?php echo $tablesize; ?>" );' 
  size=3 maxlength=3 /> 
  <label class='table_size_label'> Items per page </label>   
  <img class='table_reset' src="/planetlab/icons/clear.png" 
    onmousedown='plc_table_size_reset("nodes","tablesize_text","999");'>
</form>
</td>

<td class='table_flushright'> 
<form class='table_search'>
  <label class='table_search_label'> Search </label> 
  <input class='table_search_input' type='text' id='search_text'
     onkeyup='nodes_filter();'
  size=40 maxlength=256 />
  <label>and</label>
  <input id='nodes_and' class='table_search_and' 
    type='checkbox' checked='checked' onchange='nodes_filter();' />
  <img class='table_reset' src="/planetlab/icons/clear.png" 
  onmousedown='plc_table_filter_reset("nodes","search_text");'>
</form>
</td>
</tr></table>

<!------------------------------------------------------------>
<!-- <div class="fdtablePaginaterWrap" id="nodes-fdtablePaginaterWrapTop"><p></p></div> -->

<!------------------------------------------------------------>
<table id="nodes" cellpadding="0" cellspacing="0" border="0" 
class="plc_table sortable-onload-4 rowstyle-alt colstyle-alt no-arrow paginationcallback-nodes_paginator max-pages-15 paginate-<?php print $tablesize; ?>">
<thead>
<tr>
<th class="sortable plc_table">Peer</th>
<th class="sortable plc_table">Region</th>
<th class="sortable plc_table">Site</th>
<th class="sortable plc_table">State</th>
<th class="sortable plc_table">Hostname</th>
<th class="sortable-sortIPAddress plc_table">IP</th>
<th class="sortable plc_table">Load</th>
<th class="sortable plc_table">Avg Load</th>
</tr>
</thead>
<tbody>

<?php

  $fake1=1; $fake2=3.14; $fake_i=0;
foreach ($nodes as $node) {
    $hostname=$node['hostname'];
    $node_id=$node['node_id'];
    $site_id=$node['site_id'];
    $site=$site_hash[$site_id];
    $login_base = $site['login_base'];
    $node_id=$node['node_id'];
    $ip=$interface_hash[$node['node_id']]['ip'];
    $interface_id=$interface_hash[$node['node_id']]['interface_id'];
    if ( ! $node['peer_id'] ) {
      $shortname="local";
    } else {
      $shortname=$peer_hash[$node['peer_id']]['shortname'];
    }
    printf ('<tr id="%s">',$hostname);
    printf ('<td class="plc_table"> %s </td>',$shortname);
    printf ('<td class="plc_table"> %s </td>',topdomain($hostname));
    printf ('<td class="plc_table"> <a href="/db/sites/index.php?id=%s">%s</a></td>',$site_id,$login_base);
    printf ('<td class="plc_table"> %s </td>',$node['boot_state']);
    printf ('<td class="plc_table"> <a href="/db/nodes/index.php?id=%s">%s</a></td>',$node_id,$hostname);
    printf ('<td class="plc_table"> <a href="/db/nodes/interfaces.php?id=%s">%s</a></td>', $interface_id,$ip);
    printf ('<td class="plc_table"> %s </td>', $fake1);
    printf ('<td class="plc_table"> %s </td>', $fake2);
    printf ( '</tr>');
				 
    if ($fake_i % 5 == 0) $fake1 += 3; 
    if ($fake_i % 3 == 0) $fake2 +=5; else $fake2 -= $fake_i;
    $fake_i += 1;
}

?>
</tbody>
<tfoot>
</tfoot>
</table>

<!-- <div class="fdtablePaginaterWrap" id="nodes-fdtablePaginaterWrapBottom"><p></p></div> -->

<p class='plc_filter_note'> 
Notes: Enter & or | in the search area to alternate between <bold>AND</bold> and <bold>OR</bold> search modes
<br/> 
Hold down the shift key to select multiple columns to sort 
</p>
