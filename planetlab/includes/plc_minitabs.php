<?php
  // $Id$

drupal_set_html_head('
<script type="text/javascript" src="/planetlab/minitabs/minitabs.js"></script>
<link href="/planetlab/minitabs/minitabs.css" rel="stylesheet" type="text/css" />
');


// the expected argument is an (ordered) associative array
// ( label => todo , ...)
// label is expected to be the string to display in the menu
// todo can be either
// (*) a string : it is then taken to be a URL to move to
// (*) or an associative array with the following keys
//     (*) 'method': 'POST' or 'GET' -- default is 'GET'
//     (*) 'url': where to go
//     (*) 'values': an associative array of (key,value) pairs to send to the URL; values are strings
//     (*) 'active': if set, this entry is the default one on startup
//		     (not supported yet)


function plc_tabs($array) {
  print '<div id="minitabs_container">';
  print '<ul id="miniflex">';
  print "\n";
  foreach ($array as $label=>$todo) {
    print '<li class="minitabs">';
    // in case we have a string, rewrite it as an array
    if (is_string ($todo)) $todo=array('method'=>'GET','url'=>$todo);
    // set default method
    if ( ! $todo['method'] ) $todo['method']='GET';
    // create form
    printf ('<form name="%s" action="%s" method="%s">',$label,$todo['url'],$todo['method']);
    // set values
    if ( $todo['values'] ) {
      foreach ($todo['values'] as $key=>$value) {
	printf('<input class="minitabs" type=hidden name="%s" value="%s" />',$key,$value);
      }
    }
    $class_value="minitabs";
    if ($todo['active']) $class_value += ' active';
    // onmouseover="over()" onmouseout="out()"
    printf('<input class="%s" value="%s" type=submit />',$class_value,$label);
    printf("</form></li>\n");
  }
  print '</ul>';
  print '</div>';
  print "\n";
  print "<br/>\n";
}

?>
