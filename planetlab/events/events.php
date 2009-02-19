<?php
// $Id: index.php 11949 2009-02-09 17:57:16Z thierry $

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

//// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'table.php';
require_once 'linetabs.php';
require_once 'datepicker.php';
  
// needs much memory
ini_set("memory_limit","256M");

//set default title
drupal_set_title('Events');

// as per index.php, we get here if _GET['type'] is set
$type=$_GET['type'];
$from_date=$_GET['from_date'];
$until_date=$_GET['until_date'];

$messages=array();

//////////////////////////////////////////////////////////// dates
// our format is yyyy/MMM/dd

// given the user-typed string, return (*) a visible string, (*) an int time
// the flag should be false for the 'from' field, and true for the until field

$EPOCH="1 January 2008";
$DAY=24*60*60-1;

function parse_date ($user_date,$until_if_true=false,$DAY,$EPOCH) {

  // empty string
  if (empty ($user_date)) {
    if ( ! $until_if_true) {
    // an empty string means the epoch in from-mode
      $date=$EPOCH; $time=strtotime($date);
    } else {
      $date="now"; $time=strtotime($date);
    }
  
  } else {
    // user-provided string
    list ($year,$month,$day) = split ('[/.-]',$user_date);
    $date=sprintf("%s %s %s",$day,$month,$year);
    $time=strtotime($date);
    //if the flag is set, we add 23h59'59'', so the 'until' date is inclusive
    if ($until_if_true) {
      $time += $DAY;
    }
    $date=strftime("%Y/%b/%d @ %H:%M",$time);
  }
  return array($date,$time);
}

//////////////////////////////////////////////////////////// layout
// outline node ids and person ids with a link
function e_node ($node_id) {
  if (! $node_id) return "";
  return l_node_t($node_id,$node_id);
}
function e_person ($person_id) {
  if (! $person_id) return "";
  return l_person_t($person_id,$person_id);
}
// xxx broken
function e_event ($event_id) {
  if (! $event_id) return "";
  return href(l_event("Event","event",$event_id),$event_id);
}

function e_subject ($type,$id) {
  $mess=$type . " " . $id;
  switch ($type) {
  case 'Node': return l_node_t ($id,$mess);
  case 'Site': return l_site_t ($id,$mess);
  case 'Person': return l_person_t ($id,$mess);
  case 'Slice': return l_slice_t ($id,$mess);
  case 'Role': case 'Key': case 'PCU': case 'Interface': case 'NodeGroup': case "Address":
    return "$mess";
  default: return "Unknown $type" . "-" . $id;
  }
}

// synthesize links to the subject objects from types and ids
function e_subjects ($param) {
  $types=$param['object_types'];
  $ids=$param['object_ids'];
  if ( ! $types) return "";
  return plc_vertical_table(array_map ("e_subject",$types,$ids));
}

function e_issuer ($param) {
  if ($param['node_id'])	return e_subject('Node',$param['node_id']);
  if ($param['person_id'])	return e_subject('Person',$param['person_id']);
  return '???';
}

function e_auth ($event) {
  if (array_key_exists('auth_type',$event)) 
    return $event['auth_type'];
    else
      return "";
}

function e_fault ($event) {
  $f=$event['fault_code'];
  if ($f==0) return "OK";
  else return $f;
}

////////////////////////////////////////////////////////////
// for convenience, add 1 day to the 'until' date as otherwise this corresponds to 0:00

$tabs=array();
$tabs['Clear events']=l_events();
plc_tabs($tabs);

list($from_string,$from_time) = parse_date ($from_date,false,$DAY,$EPOCH);
list($until_string,$until_time) = parse_date ($until_date,true,$DAY,$EPOCH);

if ($from_time > $until_time) {
  drupal_set_error("Warning - <from> is after <until>");
  return;
 }

$filter=array();
// sort events by time is not good enough, let's use event_id
$filter['-SORT']='-event_id';
$filter[']time']=$from_time;
$filter['[time']=$until_time;

//////////////////////////////////////// Events
if ($type == 'Event') {
  
  // and the filter applied for fetching events using GetEvent
  $user_desc=$_GET['event'];
  if ( ! empty($user_desc)) {
    // should parse stuff like 45-90,230-3000 - some other day
    $filter['event_id']=intval($user_desc);
  }
  
  $events = $api->GetEvents($filter); 
  $title="Events [ $from_string - $until_string] matching " . ($user_desc ? $user_desc : "everything");
  
  // see actual display of $title and $events below
  
 } else {
  
  switch ($type) {
  case 'Person': 
    $primary_key='person_id';
    $string_key='email';
    $user_input=$_GET['person'];
    $method="GetPersons";
    $object_type='Person';
    break;

  case 'Node': 
    $primary_key='node_id';
    $string_key='hostname';
    $user_input=$_GET['node'];
    $method="GetNodes";
    $object_type='Node';
    break;
      
  case 'Site': 
    $primary_key='site_id';
    $string_key='login_base';
    $user_input=$_GET['site'];
    $method="GetSites";
    $object_type='Site';
    break;

  case 'Slice': 
    $primary_key='slice_id';
    $string_key='name';
    $user_input=$_GET['slice'];
    $method="GetSlices";
    $object_type='Slice';
    break;
  }

  $object_ids=array();
  $title="Events [ $from_string - $until_string]";
  $title .= " type=$object_type";
  $title .= " id(s)=";
  foreach ( split(",",$user_input) as $user_desc) {
# numeric 
    if (my_is_int($user_desc)) {
      $obj_check = call_user_func(array($api,$method),array(intval($user_desc)),array($primary_key));
      if (empty ($obj_check)) {
	$messages[] = "No such " . $primary_key . ": " . $user_desc;
      } else {
	$object_ids[] = $obj_check[0][$primary_key];
	$title .= " $user_desc, " ;
      }
    } else {
# string
      $new_object_ids=call_user_func (array($api,$method), array($string_key=>$user_desc),array($primary_key,$string_key));
      if (empty($new_object_ids)) {
	$messages[] = "No " . $string_key . " matching " . $user_desc;
      } else {
	foreach ($new_object_ids as $new_obj_id) {
	  $object_ids[] = $new_obj_id[$primary_key];
	  $title .= $new_obj_id[$primary_key] . ", ";
	}
      }
    }
  }

  $event_objs = $api->GetEventObjects(array('object_id'=>$object_ids,'object_type'=>$object_type),array('event_id'));
  // get set of event_ids
  $event_ids = array_map ( create_function ('$eo','return $eo["event_id"];') , $event_objs);
    
  $events = $api->GetEvents (array('event_id'=>$event_ids));

  // see actual display of $title and $events below

 }

  drupal_set_title ($title);
// Show messages
if (!empty($messages)) 
  foreach ($messages as $line) 
    drupal_set_message($line);

$headers=array("Id"=>"int",
	       "Time"=>"EnglishDateTime",
	       "Method"=>"string",
	       "Message"=>"string",
	       "Subjects"=>"string",
	       "Issuer"=>"string",
	       "Auth"=>"string",
	       "R"=>"string",
	       "D"=>"none",
	       );

$table = new PlcTable ("events",$headers,"0r");
$table->set_options (array ('max_pages'=>20));
$table->start ();
foreach ($events as $event) {
  
  // the call button
  $message = htmlentities($event['message'], ENT_QUOTES);
  $call = htmlentities($event['call'], ENT_QUOTES);
  $text = sprintf("message=<<%s>>\\n\\ncall=<<%s>>\\n\\nruntime=<<%f>>\\n",$message,$call,$event['runtime']);
  $method = "<input type=button name='call' value='" . $event['call_name'] ."' onclick='alert(\"" . $text . "\")'";
  //    $method = sprintf('<span title="%s">%s</span>',$call,$method);
  
  // the message button
  $trunc_mess=htmlentities(truncate($event['message'],40),ENT_QUOTES);
  $message="<input type=button name='message' value='" . $trunc_mess ."' onclick='alert(\"" . $text . "\")'";
  $details="<input type=button name='message' value='X' onclick='alert(\"" . $text . "\")'";
  //    $message=sprintf('<span title="%s">%s</span>',$message,$message);
  
  $message=truncate($event['message'],40);
  $table->row_start();
  $table->cell(e_event($event['event_id']));
  $table->cell(date('M/d/Y H:i', $event['time']));
  $table->cell($event['call_name']);
  $table->cell($message);
  $table->cell(e_subjects($event));
  $table->cell(e_issuer($event));
  $table->cell(e_auth($event));
  $table->cell(e_fault($event));
  $table->cell($details);
  $table->row_end();
}
$table->set_options(array('notes'=>array("The R column shows the call result value, a.k.a. fault_code",
					 "Click the button in the D(etails) column to get more details")));
$table->end();
  
//plc_tabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>

