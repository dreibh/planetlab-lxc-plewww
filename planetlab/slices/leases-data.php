<?php

// this ajax hook returns the 'leases_data' html <table>
// that holds the data about leases

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

$slice_id=$_POST['slice_id'];
$slicename=$_POST['slicename'];
$leases_granularity=$_POST['leases_granularity'];
$leases_offset=$_POST['leases_offset'];
$leases_slots=$_POST['leases_slots'];
$leases_w=$_POST['leases_w'];

// need to recompute reservable nodes for this slice
$node_columns=array('hostname','node_id');
$reservable_nodes=$api->GetNodes(array('|slice_ids'=>intval($slice_id), 'node_type'=>'reservable'),$node_columns);

// where to start from, expressed as an offset in hours from now
$rough_start=time()+$leases_offset*3600;
// show the next 36 grains 
$duration=$leases_slots*$leases_granularity;
$steps=$duration/$leases_granularity;
$start=intval($rough_start/$leases_granularity)*$leases_granularity;
$end=$rough_start+$duration;
$lease_columns=array('lease_id','name','t_from','t_until','hostname','name');
$leases=$api->GetLeases(array(']t_until'=>$rough_start,'[t_from'=>$end,'-SORT'=>'t_from'),$lease_columns);
// hash nodes -> leases
$host_hash=array();
foreach ($leases as $lease) {
  $hostname=$lease['hostname'];
  if ( ! isset($host_hash[$hostname] )) {
    $host_hash[$hostname]=array();
  }
  // resync within the table
  $lease['nfrom']=($lease['t_from']-$start)/$leases_granularity;
  $lease['nuntil']=($lease['t_until']-$start)/$leases_granularity;
  $host_hash[$hostname] []= $lease;
}
// leases_data is the name used by leases.js to locate this table
//echo "<table id='leases_data' class='hidden'>";
// empty upper-left cell
echo "<thead><tr><td></td>";
// the timeslot headers read (timestamp,label)
$day_names=array('Su','M','Tu','W','Th','F','Sa');
for ($i=0; $i<$steps; $i++) {
  $timestamp=($start+$i*$leases_granularity);
  $day=$day_names[intval(strftime("%w",$timestamp))];
  $label=$day . strftime(" %H:%M",$timestamp);
  // expose in each header cell the full timestamp, and how to display it - use & as a separator*/
  echo "<th>" . implode("&",array($timestamp,$label)) . "</th>";
}
echo "</tr></thead><tbody>";
// todo - sort on hostnames
function sort_hostname ($a,$b) { return ($a['hostname']<$b['hostname'])?-1:1;}
usort($reservable_nodes,"sort_hostname");
foreach ($reservable_nodes as $node) {
  echo "<tr><th scope='row'>". $node['hostname'] . "</th>";
  $hostname=$node['hostname'];
  $leases=NULL;
  if (array_key_exists($hostname,$host_hash) ) $leases=$host_hash[$hostname];
  $counter=0;
  while ($counter<$steps) {
    if ($leases && ($leases[0]['nfrom']<=$counter)) {
      $lease=array_shift($leases);
      /* nicer display, merge two consecutive leases for the same slice 
	 avoid doing that for now, as it might make things confusing */
      /* while ($leases && ($leases[0]['name']==$lease['name']) && ($leases[0]['nfrom']==$lease['nuntil'])) {
	 $lease['nuntil']=$leases[0]['nuntil'];
	 array_shift($leases);
	 }*/
      $duration=$lease['nuntil']-$counter;
      echo "<td colspan='$duration'>" . $lease['lease_id'] . '&' . $lease['name'] . "</td>";
      $counter=$lease['nuntil']; 
    } else {
      echo "<td></td>";
      $counter+=1;
    }
  }
  echo "</tr>";
}
echo "</tbody>";
//echo "</table>\n";

?>
