<?php

function authorizeSlice($sn) {

	global $api;

  $slice_list= array();
  $result= $api->GetSlices( Null, array( "name", "slice_id" ) );

  foreach ( $result AS $slice )
  {
  	if ( $slice["name"] == $sn )
  		return 1;

  }

  return 0;

}

//can a request be satisfied?  Currently, answer is yes unless
//either this time is taken or you asked for more than one unit
//probably will need to change this.
function validateRequest ($units, $timesOccupied, $requestedTime, $currentTime) {
  if ($units != 1)
    return TOO_MANY_UNITS;

  // buffer so we aren't too close to deadline, if your request is late
  // OR if it's within 1 minute of deadline, it's too late
  if ($requestedTime - 60 <= $currentTime)
    return TIME_ALREADY_OCCURRED;

  if (array_key_exists($requestedTime, $timesOccupied)) {
    if ($timesOccupied[$requestedTime] == MAX_JOBS)
      return NO_ROOM;
  }
  return SUCCESS;
}

//can a request be satisfied?  Currently, answer is yes unless
//either this time is taken or you asked for more than one unit
//probably will need to change this.
function validateAndMarkRequest ($units, &$timesOccupied, $requestedTime, $currentTime, $sn, $jobArray) {
  // buffer so we aren't too close to deadline, if your request is late
  // OR if it's within 1 minute of deadline, it's too late
  if ($requestedTime - 60 <= $currentTime)
    return TIME_ALREADY_OCCURRED;

  if (array_key_exists($requestedTime, $timesOccupied)) {
    if ($timesOccupied[$requestedTime] == MAX_JOBS)
      return NO_ROOM;
    else
      $timesOccupied[$requestedTime]++;
  }
  else {
    $timesOccupied[$requestedTime] = 1;
    if (array_key_exists($sn, $jobArray)) {
      $ts = $jobArray[$sn]["timestamp"];
      $timesOccupied[$ts]--;
    }
  }

  if ($units != 1)
    return TOO_MANY_UNITS;

  return SUCCESS;
}

function findNextFreeSlot($units, $timesOccupied) {
  $currYear = gmdate("y");
  $currMonth = gmdate("m");
  $currDate = gmdate("d");
  $currHour = gmdate("H") + 1;
  $currentTime = time();
  $reqTime = gmmktime($currHour, 0, 0, $currMonth, $currDate, $currYear);
  $retVal = 1;
  while ($retVal != SUCCESS) {
    $retVal = validateRequest($units, $timesOccupied, $reqTime, $currentTime);
    if ($retVal == NO_ROOM || $retVal == TIME_ALREADY_OCCURRED) { // advance timestamp one hour (3600 seconds)
      $reqTime = $reqTime + 3600;
    }
  }
  return $reqTime;
}

function dumpToFile($fileName, $buffer, $which, $timesOccupied) {
  //open file, and dump newly list into it (buffer is the list)
  //we're just currently overwriting (fopen with "w" truncates)

  $fileHandle = fopen($fileName, "w");
  //periodically, updateSliceUnits program will update the slices file
  //this function is general, works for schedule or slices file

  //lock in case of concurrent accesses
  flock($fileHandle, LOCK_EX);

  if ($which == "schedule") {  // need to write timestamp in this case
    $s = gettimeofday();
    fwrite($fileHandle, $s['sec']);
    fwrite($fileHandle, "\n");
  }

  //do the dump here
  foreach ($buffer as $value) {
    $t = "";
    if ($which == "schedule") {
      if (strcmp($value["timestamp"], time()) > 0) {
	$numReps = $value["reps"];
	$ts = $value["timestamp"];
	$t = $value["sliceName"]." ".$value["id"]." ".$value["timestamp"]." ".$value["units"]." ".$value["reps"]." \n";
      }
      else {  // job expired, does it need be run again?
	if ($value["reps"] > 0) {
	  $ts = findNextFreeSlot($value["units"], $timesOccupied);
	}
	$numReps = $value["reps"] - 1;
      }
      if ($numReps >= 0)
	$t = $value["sliceName"]." ".$value["id"]." ".$ts." ".$value["units"]." ".$numReps." \n";
    }
    else if ($which == "slices") {
      $t = $value["sliceName"]." ".$value["units"]." \n";
    }

    if ($t != "")
      fwrite($fileHandle, $t);

  }

  flock($fileHandle, LOCK_UN);

  fclose($fileHandle);
}

//update the slice file, takes a slice name (name) and number of units
function updateSliceFile($name, $units) {
  $dummyArray = array();

  $sliceFile = fopen("slices.txt", "rw");
  if (!$sliceFile) {
    echo "<p>Unable to open remote file.</p>";

  }

  flock($sliceFile, LOCK_EX);

  //we'll construct a new list here, will be current slice file except
  //the slice in question will have it's units decreased, if there are any...
  while (!feof($sliceFile)) {
    $num = fscanf($sliceFile, "%s %d\n", $sliceName, $unitsAvailable);
    //for some reason feof seems to not quite work
    //precisely, the last entry in the file is read twice (!?!), so hack here
    if ($num == 0)
      break;

    $newArray["sliceName"] = $sliceName;
    if ($name == $sliceName) {
      $newUnits = $unitsAvailable - $units;
      if ($newUnits < 0)  // error, slice has no more units
	return -1;
      else
	$newArray["units"] = $newUnits;
    }
    else
      $newArray["units"] = $unitsAvailable;
    //append this tuple to the entire array
    $sliceArray[] = $newArray;
  }
  flock($sliceFile, LOCK_UN);
  fclose($sliceFile);
  //do the dump to new file
  dumpToFile("slices.txt", $sliceArray, "slices", dummyArray);
  return 0;
}


//pretty obvious what this does; basically, does the slice exist in
//the slice file yet?  (New user of calendar service user may not have
//an entry)
function isFirstSliceRequest($name) {
  $sliceFile = fopen("slices.txt", "r");
  if (!$sliceFile) {
    echo "<p>Unable to open remote file.</p>";

  }

  flock($sliceFile, LOCK_EX);

  while (!feof($sliceFile)) {
    $num = fscanf($sliceFile, "%s %d\n", $sliceName, $unitsAvailable);
    //for some reason feof seems to not quite work
    //precisely, the last entry in the file is read twice (!?!), so hack here
    if ($num == 0)
      break;

    if ($name == $sliceName) {
      flock($sliceFile, LOCK_UN);
      fclose($sliceFile);
      return 0;
    }
  }

  flock($sliceFile, LOCK_UN);
  fclose($sliceFile);
  return 1;
}


function cmp ($a, $b) {
  if ($a["timestamp"] == $b["timestamp"])
    return 0;
  else
    return ($a["timestamp"] < $b["timestamp"]) ? -1 : 1;
}

function checkForErrors($requestStatus) {
  if ($requestStatus == NO_ROOM) {
    printf("<b> Error: Cannot add your request; that time slot is currently full. </b> <p>");
  }
  else if ($requestStatus == TOO_MANY_UNITS) {
    printf("<b> Error: Cannot add your request; only 1 extra unit is allowed.</b> <p>");
  }
  else if ($requestStatus == NO_UNITS_LEFT) {
    printf("<b> Error: Cannot add your request; no more units remaining.</b> <p>");
  }
  else if ($requestStatus == TIME_ALREADY_OCCURRED) {
    printf("<b> Error: Cannot add your request; that time has already occurred, or is too close to the current time.</b> <p>");
  }
  else if ($requestStatus == NO_SUCH_SLICE) {
    printf("<b> Error: Cannot delete nonexistent slice.</b> <p>");
  }
  else if ($requestStatus == NOT_SLICE_OWNER) {
    printf("<b> Error: Only authorized user can manipulate slice.</b> <p>");
  }
  else if ($requestStatus == TOO_CLOSE_TO_DEADLINE) {
    printf("<b> Error: Cannot delete your request; it is too close to the time that your slice will receive its priority increase.</b> <p>");
  }
}

function getCurrentSchedule (&$jobArray, &$timesOccupied, &$maxId) {

  $schedFile = fopen("schedule.txt", "r");
  if (!$schedFile) {
    echo "<p>Unable to open remote file.</p>";

  }

  flock($schedFile, LOCK_EX);

  //first line is timestamp, throw it away.
  fscanf($schedFile, "%s\n", $str);

  //read in current file into array
  $jobArray = array();
  $newArray = array();
  $timesOccupied = array();
  $maxId = 0;
  while (!feof($schedFile)) {
    $num = fscanf($schedFile, "%s %d %s %d %d\n", $sliceName, $id, $timestamp, $units, $reps);

    if ($id > $maxId)
      $maxId = $id;

    //for some reason feof seems to not quite work
    //precisely, the last entry in the file is read twice (!?!), so hack here
    if ($num == 0)
      break;
    $newArray["sliceName"] = $sliceName;
    $newArray["id"] = $id;
    $newArray["units"] = $units;
    $newArray["timestamp"] = $timestamp;
    $newArray["reps"] = $reps;
    $jobArray[$sliceName] = $newArray;

    if (array_key_exists($timestamp, $timesOccupied)) {
      $timesOccupied[$timestamp]++;
    }
    else {
      $timesOccupied[$timestamp] = 1;
    }

  }

  flock($schedFile, LOCK_UN);
  fclose($schedFile);

}


function findNextQueue($units, $timesOccupied) {
  global $arr;
  $currYear = gmdate("y");
  $currMonth = gmdate("m");
  $currDate = gmdate("d");
  $currHour = gmdate("H") + 1;
  $currentTime = time();
  $reqTime = gmmktime($currHour, 0, 0, $currMonth, $currDate, $currYear);
  $retVal = 1;
  $i = 0;

	// DAVE
	// outputting table to display the queue
	// green background will mean slot is open, and red will mean the slot is used
	//
  echo "<table cellspacing=\"2\" cellpadding=\"1\" border=\"0\" width=550>\n";
  echo "<tr><td colspan=\"3\"><span class='bold'>24 hour Queue:</span> Choose the GMT time slot you desire (<font color=\"#339933\">green</font> slots are open, <font color=\"#CC3333\">red</font> are taken) <p></td></tr>\n";
  echo "<tr><td width=\"47%\" align=\"right\"><table cellspacing=1 cellpadding=1 border=0 width=130>\n";

  // here's what this does below: it goes through each hour, and sees if the slot is occupied
  // if so, it outputs in red, w/ slice name ($arr[$x], where $x is the number request, i.e.
  // earlier when we dump out the list of slices on the schedule, we do $arr[$x++] = $slicename
  $x= 0;
  //  while ($reqTime < ( $reqTime + ( 24 * 3600 ) ) ) {
  while ($i < 12) {
    $retVal = validateRequest($units, $timesOccupied, $reqTime, $currentTime);
    if ($retVal == SUCCESS) { // advance timestamp one hour (3600 seconds)

	echo "<tr bgcolor=\"#339933\"><td><input type=\"radio\" name=\"queue_time\" value=\"" . gmdate("H:i:s", $reqTime) . "\"> " . gmdate("H:i:s", $reqTime) . " &nbsp;  </td></tr>\n";
    }
    else {
	echo"<tr bgcolor=\"#CC3333\"><td align=center> " . $arr[$x] . " </td></tr>\n";
	$x++;
    }

    $reqTime = $reqTime + 3600;
    $i++;
  }
  echo "</table></td><td width=\"6%\"> &nbsp; </td><td><table cellspacing=1 cellpadding=1 border=0 width=130>\n";

  while ($i < 24 && $i > 11) {
    $retVal = validateRequest($units, $timesOccupied, $reqTime, $currentTime);
    if ($retVal == SUCCESS) { // advance timestamp one hour (3600 seconds)

        echo "<tr bgcolor=\"#339933\"><td><input type=\"radio\" name=\"queue_time\" value=\"" . gmdate("H:i:s", $reqTime) . "\"> " . gmdate("H:i:s", $reqTime) . " &nbsp;  </td></tr>\n";     }
    else {
        echo"<tr bgcolor=\"#CC3333\"><td align=center> " . $arr[$x] . " </td></tr>\n";
	$x++;
    }

    $reqTime = $reqTime + 3600;
    $i++;
  }
  echo "</table></td></tr>\n";

  echo "</table>\n";

}


function sliceDropDown() {

	global $api;

  $slice_list= array();
  $result= $api->GetSlices( Null, array( "name", "slice_id" ) );
  // sort_slices( $result ); --> slice sort on name
  function __cmp_slices($a, $b) {
    return strcasecmp($a['name'], $b['name']);
  }
  usort($result, '__cmp_slices');

  foreach ( $result AS $slice )
  {
  	echo "<option value='" . $slice["name"] . "'>" . $slice["name"] . "\n";

  }

}





?>
