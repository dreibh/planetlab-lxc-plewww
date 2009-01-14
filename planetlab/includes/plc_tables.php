<?php
  /* table_id */
function plc_table_header ($table_id,$size_init,$size_def) {
  $tablesize_text_id = $table_id . "_tablesize";
  $search_text_id = $table_id . "_search";
  $search_reset_id = $table_id . "_search_reset";
  $search_and_id = $table_id . "_search_and";
  print <<< EOF
<table class='table_dialogs'> <tr>
<td class='table_flushleft'>
<form class='table_size'>
   <input class='table_size_input' type='text' id="$tablesize_text_id" value=$size_init 
      onkeyup='plc_table_setsize("$table_id","$tablesize_text_id", $size_init);' 
      size=3 maxlength=3 /> 
  <label class='table_size_label'> items/page </label>   
  <img class='table_reset' src="/planetlab/icons/clear.png" 
      onmousedown='plc_table_size_reset("$table_id","$tablesize_text_id",$size_def);'>
</form>
</td>

<td class='table_flushright'> 
<form class='table_search'>
   <label class='table_search_label'> Search </label> 
   <input class='table_search_input' type='text' id='$search_text_id'
      onkeyup='plc_table_filter("$table_id","$search_text_id","$search_and_id");'
      size=40 maxlength=256 />
   <label>and</label>
   <input id='$search_and_id' class='table_search_and' 
      type='checkbox' checked='checked' 
      onchange='plc_table_filter("$table_id","$search_text_id","$search_and_id");' />
   <img class='table_reset' src="/planetlab/icons/clear.png" 
      onmousedown='plc_table_filter_reset("$table_id","$search_text_id","$search_and_id");'>
</form>
</td>
</tr></table>
EOF;
}
?>

