<?php
  // $Id$

drupal_set_html_head('
<script type="text/javascript" src="/planetlab/minitabs/minitabs.js"></script>
<link href="/planetlab/minitabs/minitabs.css" rel="stylesheet" type="text/css" />
');


function plc_tabs($array) {
  print '<div id="minitabs_container">';
  print '<ul id="miniflex">';
  print "\n";
  foreach ($array as $name=>$url) {
    printf ('<li class="minitabs"><a href="%s" title="">%s</a></li>',$url,$name);
    print "\n";
  }
  print '</ul>';
  print '</div>';
  print "\n";
  print "<br/>\n";
}

?>