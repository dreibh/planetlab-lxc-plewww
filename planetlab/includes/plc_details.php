<?php

// $Id$

require_once 'plc_functions.php';

// rough implem, no class for now

// start the details area, with an optional caption
function plc_details_start ($title="") {
  print "<table><thead>";
  if ($caption) {
    printf ("<caption>%s</caption>\n",$caption);
  }
  echo "</thead><tbody>";
}

// end the area
function plc_details_end() {
  print "</tbody></table>\n";
}

// display a line with caption and value
function plc_details_line ($title,$value) {
  printf("<tr><th>%s</th><td>%s</td></tr>\n",$title,$value);
}

// same but the values are multiple and displayed in an embedded vertical table
function plc_details_line_list($title,$list) {
  plc_details_line($title,plc_vertical_table($list,"foo"));
}

// a dummy line for getting some air
function plc_details_space_line () {
  echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
}

?>
