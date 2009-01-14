<?php
  // $Id$

function plc_show_options($array) {
  print '<div id="minitabs_container">';
  print '<ul id="miniflex">';
  foreach ($array as $name=>$url) {
    print "<li class='minitabs'><a href=\"" . $url . "\"title=\"\">" . $name . "</a></li>\n";
  }
  print '</ul>';
  print '</div>';
  print "\n";
  print "<br/>\n";
}

?>
