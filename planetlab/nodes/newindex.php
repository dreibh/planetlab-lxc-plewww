<?php

// $Id$

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Nodes');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

$header_autocomplete_js='
<script type="text/javascript" src="/planetlab/bsn/bsn.Ajax.js"></script>
<script type="text/javascript" src="/planetlab/bsn/bsn.DOM.js"></script>
<script type="text/javascript" src="/planetlab/bsn/bsn.AutoSuggest.js"></script>
';

$header_tablesort_js='
<script type="text/javascript" src="/planetlab/tablesort/tablesort.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/customsort.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/paginate.js"></script>
<script type="text/javascript" src="/planetlab/js/plc_paginate.js"></script>
';

$header_tablesort_css='
<link href="/planetlab/css/plc_style.css" rel="stylesheet" type="text/css" />
<link href="/planetlab/css/plc_table.css" rel="stylesheet" type="text/css" />
<link href="/planetlab/css/plc_paginate.css" rel="stylesheet" type="text/css" />
';

$unused='
<script type="text/javascript" src="/planetlab/tablesort/more.js"></script>
<link href="/planetlab/css/more.css" rel="stylesheet" type="text/css" />
<body OnLoad="init();">
';

drupal_set_html_head($header_autocomplete_js);
drupal_set_html_head($header_tablesort_js);
drupal_set_html_head($header_tablesort_css);

$nodepattern=$_GET['nodepattern'];
$peerscope=$_GET['peerscope'];
$tablesize=$_GET['tablesize'];
if (empty($tablesize)) $tablesize=25;

?>

<div class="plc_filter">
<form method=get action='newindex.php'>
<table>

<tr>
<th><label for='peerscope'>Federation scope </label></th>
<td colspan=2><select id='peerscope' name='peerscope' onChange='submit()'>
<?php echo plc_peers_option_list($api); ?>
</select></td>
</tr>

<tr>
<th><label for='nodepattern'>Hostname </label></th>
<td><input type='text' id='nodepattern' name='nodepattern' 
     size=40 value='<?php print $nodepattern; ?>'/></td>
<td><input type=submit value='Go' /></td>
</tr> 

<tr> 
<th><label for='tablesize'>Table size</label></th>
<td> <input type='text' id='tablesize' name='tablesize' 
      size=3 value='<?php print $tablesize; ?>'/></td>
<td><input type=submit value='Go' /> </td>
</tr>
</table>
</form>
</div>

<script type="text/javascript">
var options = {
	script:"/planetlab/nodes/test.php?",
	varname:"input",
	minchars:1
};
var as = new AutoSuggest('nodepattern', options);
</script>


<?php

$peer_filter=array();

// fetch nodes
$node_columns=array('hostname','site_id','node_id','boot_state','interface_ids');
if ($nodepattern) {
  $node_filter['hostname']=$nodepattern;
 } else {
  $node_filter=array('hostname'=>"*");
 }

// peerscope
list ( $peer_filter, $peer_label) = plc_peer_info($api,$_GET['peerscope']);
$node_filter=array_merge($node_filter,$peer_filter);

$nodes=$api->GetNodes($node_filter,$node_columns);

// build site_ids and interface_ids 
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

?>

<div class="fdtablePaginaterWrap" id="nodes-fdtablePaginaterWrapTop"><p></p></div>

<table id="nodes" cellpadding="0" cellspacing="0" border="0" 
class="plc_table sortable-onload-3r rowstyle-alt colstyle-alt no-arrow paginationcallback-nodesTextInfo max-pages-15 paginate-<?php print $tablesize; ?>">
<thead>
<tr>
<th class="sortable plc_table">State</th>
<th class="sortable plc_table">Hostname</th>
<th class="sortable plc_table">Site</th>
<th class="sortable plc_table">Region</th>
<th class="sortable-sortIPAddress plc_table">IP</th>
<th class="sortable plc_table">Load</th>
<th class="sortable plc_table">Avg Load</th>
</tr>
</thead>
<tbody>

<script type"text/javascript">
function nodesTextInfo (opts) {
  displayTextInfo (opts,"nodes");
}
</script>

<?php

  $fake1=1; $fake2=3.14;
foreach ($nodes as $node) {
    $hostname=$node['hostname'];
    $node_id=$node['node_id'];
    $site_id=$node['site_id'];
    $site=$site_hash[$site_id];
    $login_base = $site['login_base'];
    $node_id=$node['node_id'];
    $ip=$interface_hash[$node['node_id']]['ip'];
    $interface_id=$interface_hash[$node['node_id']]['interface_id'];
    printf ('<tr id="%s">',$hostname);
    printf ('<td class="plc_table"> %s </td>',$node['boot_state']);
    printf ('<td class="plc_table"> <a href="/db/nodes/index.php?id=%s">%s</a></td>',$node_id,$hostname);
    printf ('<td class="plc_table"> <a href="/db/sites/index.php?id=%s">%s</a></td>',$site_id,$login_base);
    printf ('<td class="plc_table"> %s </td>',topdomain($hostname));
    printf ('<td class="plc_table"> <a href="/db/nodes/interfaces.php?id=%s">%s</a></td>', $interface_id,$ip);
    printf ('<td class="plc_table"> %s </td>', $fake1);
    printf ('<td class="plc_table"> %s </td>', $fake2);
    printf ( '</tr>');
    $fake1 += 3;
    $fake2 += 2;
}

?>
</tbody>
<tfoot>
</tfoot>
</table>

<div class="fdtablePaginaterWrap" id="nodes-fdtablePaginaterWrapBottom"><p></p></div>

