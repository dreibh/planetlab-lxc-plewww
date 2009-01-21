<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Sites Status');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];
 


$count1=0;
$count2=0;
$count3=0;
$j=0;
$site_nt_enabled = array();
$site_wno_nodes = array();
$sitemembers_nodes= array();
$nodes_ids=array();
$site_up=array();
function layout(&$param) {

  $class='plc-foreign';
  $messages=array();
  $new_site=($param[$j]['abbreviated_name']);
  $temp= $new_site;
  $messages[]=$temp;
  echo $new_site;
  //unset ($param['time']);
  $param[$j]['abbreviated_name']=plc_vertical_table($messages,$class);
  $j++;

}


/////////Quantavis and Alcatel case
echo "<table ><tr><td><h2<span class='plc-foreign'> Quantavis   <span></td><td><span class='plc-warning'>Member to join:no membership requested</span></h2</td></tr></table>" ;  
echo "<table ><tr><td><h2><span class='plc-foreign'> Alcatel   <span></td><td><span class='plc-warning'>Member to join:no membership requested</span></h2></td></tr></table>" ;  

//get all local site with the filter peer_id=None
$filter=array("peer_id"=>NULL);
$columns=array("abbreviated_name","name");
$sites=$api->GetSites($filter,NULL);
//layout($sites);

if (empty($sites)){
  echo "No PLE Nodes";
  echo "<br /><p><a href='/db/nodes/index.php'>Back to Nodes List</a>";
  //  return;
 }



for($i=0; $i <= count($sites) ; $i++)
  {
    $temp= $sites[$i]["site_id"];
    $filter=array("site_id"=>$temp);
    $columns=array("boot_state","hostname","node_id");
    $Nodes=$api->GetNodes($filter,$columns);

    //array_push($sitemembers_nodes,$Nodes[$i]);
    //for($i=0; $i < count($Nodes) ; $i++)      
    //  {
    //	echo $i;
    //    array_push($sitemembers_nodes,$Nodes[$i]);
    //    echo $Nodes[$i]['hostname'];
    //  }
    //
    
    if (empty($Nodes)){
      if ($site_st= $sites[$i]["enabled"]==0){
	$site_name= $sites[$i]["name"];
	array_push($site_nt_enabled,$site_name);
	$count1++;
      }
      
      elseif ($sites[$i]["name"]=="PlanetLabEurope Central"){}///planetlab Central case
      
      else{
	$site_name= $sites[$i]["name"];
	array_push($site_wno_nodes,$site_name);
	$count2++;
      }
    }
  
 else{
   
   
   $site_name= $sites[$i]["name"];
   array_push($sitemembers_nodes,$Nodes);
   array_push($nodes_ids,$Nodes[$j]["node_id"]);
   array_push($site_up,$site_name);
   $count3++;
   
   //echo paginate($Nodes,"node_id", "----$site_name----", 5,"hostname","$site_name");
 }
}

///site not already enabled
for($i=0; $i != (($count1)-1); $i++)
  {  $site_name=$site_nt_enabled[$i];
    echo "<table ><tr><td><h2><span class='plc-foreign'> $site_name <span></td><td><span class='plc-warning'>Site with a join request pending</span></h2></td></tr></table>" ;  
  }

//site with no nodes
for($i=0; $i < $count2; $i++)
  {  $site_name=$site_wno_nodes[$i];
    echo "<table ><tr><td><h2><span class='plc-foreign'> $site_name <span></td><td><span class='plc-warning'>Site has no nodes </span></h2></td></tr></table>" ;  
    }

//////Site runing up
for($i=0; $i < $count3; $i++)
  { $nodes=$sitemembers_nodes[$i];
    $site_name=$site_up[$i];
    echo " <table ><tr><td><h4><span class='plc-foreign'> $site_name <span></td></tr></h4></table>";
    array_map(layout($site_up),$nodes);
    echo paginate($nodes,"node_id", "Nodes", 5,"hostname","nodes","$nodes_ids[$i]");
  }

    
echo "<br /><p><a href='index.php'>Back to node list</a>";
//// Print footer
include 'plc_footer.php';
?>
