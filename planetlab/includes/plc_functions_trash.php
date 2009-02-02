<?php

// this will be trashed eventually

// pagination function
function paginate_trash ( $fn_array, $table_id, $caption, $limit, $main_field, $other_func= NULL, $fid= NULL ) {
  // get vars from call adjust them
  $dir= strtolower( $caption );
  $echo= "";

  $link_page= 'index.php';

  // check for page number
  if( empty( $_GET['page'] ) )
    $page= 1;
  else
    $page= $_GET['page'];

  // reorder array_chunk
  foreach( $fn_array as $arr1 ) {
    unset( $arr2 );
    
    foreach( $arr1 as $key => $val ) {
//      if( substr( $key, -3 ) == "_id" )
      if ( $key == $table_id  ) {
        $id[$key]= $val;
      } else {
        $data[$key]= $val;
      }
    }
    
    foreach( $id as $key => $val )
      $arr2[$key]= $val;

    foreach( $data as $key => $val )
      $arr2[$key]= $val;

    $as_array[]= $arr2;
  }

  $totalrows= count( $as_array );

  // if array has no rows display msg
  if( $totalrows == 0 )
    return "Nothing to display";

  // set key and break up data struct
  $newkey= $page - 1;  
  $newarray= array_chunk( $as_array, $limit );

  // start table output
  $echo.= "<table class='list_set' border=0 cellpadding=2>\n";

  // if there is a caption add it to table
  if( $caption )
    $echo.= "<caption class='list_set'>$caption</caption>\n";

  $echo.= "<thead><tr class='list_set'>";

  // go through keys of one array row for table headers
  foreach( $newarray[$newkey][0] as $key => $val ) {
//    if( substr( $key, -3 ) != "_id" )
    if ( $key != $table_id && $key != 'peer_id' )
      $echo.= "<th class='list_set'>". ucfirst( $key ) ."</th>";
  }

  if( $other_func == 'slivers' )
    $echo.= "<th>Slivers</th>";

  $echo.= "</tr></thead><tbody>\n";

  // go through array row by row to output table rows
  foreach( $newarray[$newkey] as $assoc ) {

    $extraclass="";
    if ($assoc['peer_id']) {
      $extraclass="plc-foreign";
    }
    

    $echo.= "<tr class='list_set'>";

    foreach( $assoc as $key => $val ) {
      // do not rely on keys order
      $id = $assoc[$table_id];
//      if( substr( $key, -3 ) == "_id" )
      if ($key == $table_id) {
//	$id= $val;
	continue;
      } elseif( $key == $main_field ) {
        $echo.= "<td class='list_set $extraclass'><a href='/db/$dir/$link_page?id=$id'>$val</a></td>";
      } elseif ($key != 'peer_id') {
        $echo.= "<td class='list_set $extraclass'>";
        if( is_array( $val ) ) {
          $count= 1;
          $tot= count( $val );
          foreach( $val as $k => $v ) {
            $echo.= $v;
            if( $count != $tot )
              $echo.= ", ";
            $count++;
          }
        }
        else
          $echo.= $val;
        $echo.= "</td>";
      }

    }

    if( $other_func == 'slivers' )
      $echo.= "<td><a href='slivers.php?node=$fid&slice=$id'>view</a></td>";

    $echo.= "</tr>\n";
  }

  // close table
  $echo.= "</tbody></table>\n";
  $echo.= "<hr />\n";

  // find total number of pages
  $numofpages = $totalrows / $limit;

  // start navigation links
  if( $numofpages > 1 ) {
    // if page is not 1 display first and prev links
    if( $page != 1 && $page ) {
        $pageprev= $page - 1;
        $echo.= "<a href=\"". $_SERVER['REQUEST_URI'] ."&page=1\">FIRST</a> &nbsp; ";
        $echo.= " <a href=\"". $_SERVER['REQUEST_URI'] ."&page=$pageprev\">PREV ".$limit."</a> &nbsp; ";
      }
      else
        $echo.= "PREV ". $limit ." ";

    // if less than 30 pages display all
    // otherwise show 30 pages but put ... inbetween
    if( $numofpages < 30 ) {
      $npages= $numofpages;
      $start= 1;
    }
    else {
      $npages= $page + 9;
      if( $npages > $numofpages )
        $npages= $numofpages;
      $start= $page - 10;
      if( $start < 1 )
        $start= 1;
      if( $page != 1 )
        $echo.= " ... ";
    }

    // display pages, no link if current page
    for( $i= $start; $i <= $npages; $i++ ) {
      if( $i == $page )
        $echo.= $i ." ";
      else
        $echo.= "<a href=\"". $_SERVER['REQUEST_URI'] ."&page=$i\">$i</a> ";

    }

    if( ( $totalrows % $limit ) != 0 ) {
      $last= $numofpages + 1;
      if( $i == $page )
        $echo.= $i ." ";
      else
        $echo.= "<a href=\"". $_SERVER['REQUEST_URI'] ."&page=$i\">$i</a> ";
    }
    else
      $last= $numofpages;

    if( $numofpages >= 30 ) {
      if( $page != $numofpages )
        $echo.= " ... ";
    }

    if( ( $totalrows - ($limit * $page) ) > 0 ) {
      $pagenext= $page + 1;
      $echo.= " &nbsp; <a href=\"". $_SERVER['REQUEST_URI'] ."&page=$pagenext\">NEXT ".$limit."</a> &nbsp; ";
    }
    else
      $echo.= "NEXT ". $limit;

    $echo.= " <a href=\"". $_SERVER['REQUEST_URI'] ."&page=". intval( $last ) ."\">LAST</a>\n";

  }

  return $echo;
  
}

// function for getting the diff of multi dimention array
function arr_diff( $a1, $a2 ) {
  $diff= array();
  foreach( $a1 as $k=>$v ) {
    unset( $dv );
    for( $x= 0; $x < count( $a2 ); $x++ ) {
      if( is_int( $k ) ) {
        if( array_search( $v, $a2 ) === false )
          $dv=$v;
        else if( is_array( $v ) )
          $dv= arr_diff( $v, $a2[$x] );
        if( $dv && !in_array( $dv, $diff ) )
          $diff[]=$dv;
      }
      else {

        if( !$a2[$k] )
          $dv=$v;
        else if(is_array($v))
          $dv=arr_diff($v,$a2[$x]);
        if($dv)
          $diff[$x]=$dv;
      }
    }
  }
  return $diff;
}

?>
