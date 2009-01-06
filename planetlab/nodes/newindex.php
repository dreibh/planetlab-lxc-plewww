<?php

// $Id$

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('New Nodes');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

$header_js='
<script type="text/javascript" src="/planetlab/tablesort/tablesort.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/customsort.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/paginate.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/more.js"></script>
<body OnLoad="init();">
';
$header_css='
<link href="/planetlab/css/demo.css" rel="stylesheet" type="text/css" />
<link href="/planetlab/css/more.css" rel="stylesheet" type="text/css" />
<!--[if IE]>
<style type="text/css">
ul.fdtablePaginater {display:inline-block;}
mul.fdtablePaginater {display:inline;}
ul.fdtablePaginater li {float:left;}
ul.fdtablePaginater {text-align:center;}
table { border-bottom:1px solid #C1DAD7; }
</style>
<![endif]-->
';

$header_unused='
';

drupal_set_html_head($header_js);
drupal_set_html_head($header_css);

$site_columns=array("site_id","login_base");
$site_filter=array("login_base"=>"*");
$sites=$api->GetSites($site_filter,$site_columns);

$site_hash=array();
foreach ($sites as $site) {
    $site_hash[$site["site_id"]]=$site;
}

$node_columns=array("hostname","site_id","node_id","boot_state");
$node_filter=array("hostname"=>"*");
$nodes=$api->GetNodes($node_filter,$node_columns);

$interface_columns=array("ip","node_id");
$interface_filter=array("is_primary"=>TRUE);
$interfaces=$api->GetInterfaces($interface_filter,$interface_columns);

$interface_hash=array();
foreach ($interfaces as $interface) {
    $interface_hash[$interface['node_id']]=$interface;
}

?>

<table id="theTable" cellpadding="0" cellspacing="0" border="0" 
class="sortable-onload-2 rowstyle-alt colstyle-alt no-arrow paginate-50 max-pages-10">
<thead>
<tr>
<th align=top>Select </th>
<th class="sortable">State</th>
<th class="sortable">Hostname</th>
<th class="sortable">Site</th>
<th class="sortable">Region</th>
<th class="sortable-sortIPAddress">IP</th>
<th class="sortable">Load</th>
<th class="sortable">Avg Load</th>
</tr>
</thead>
<tbody>

<?php

foreach ($nodes as $node) {
    $hostname=$node['hostname'];
    $site=$site_hash[$node['site_id']];
    $login_base = $site['login_base'];
    $node_id=$node['node_id'];
    $ip=$interface_hash[$node['node_id']]['ip'];
    printf ('<tr id="%s">',$hostname);
    printf ('<td> <input type="checkbox" id="%s"></td>',$hostname);
    printf ('<td> %s </td>',$node['boot_state']);
    printf ('<td> %s </td>',$hostname);
    printf ('<td> %s </td>',$login_base);
    printf ('<td> %s </td>',topdomain($hostname));
    printf ('<td> %s </td>',$ip);
    printf ('<td> 1.0 </td>');
    printf ('<td> 10.0 </td>');
    printf ( '</tr>');
}

?>
</tbody>
<tfoot>
</tfoot>
</table>

