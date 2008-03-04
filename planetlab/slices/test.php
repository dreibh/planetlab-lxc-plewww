<?php

// $Id: test.php 152 2007-03-28 13:26:00Z thierry $

  // *NOTE*
  // the optimization done for nodes was *not* merged here, because of the following bug

  //root@onelab-private /tmp # api
  //PlanetLab Central Direct API Access
  //Type "system.listMethods()" or "help(method)" for more information.
  //>>> GetSlices({'name':'o*','~expires':0})
  //Database error 52cfdd40-b57e-43cb-ace0-406d49408527:
  //unindexable object
  //Query:
  //SELECT creator_person_id, instantiation, slice_attribute_ids, name, slice_id, created, url, max_nodes, person_ids, expires, site_id, peer_slice_id, node_ids, peer_id, description FROM view_slices WHERE is_deleted IS False AND expires > 1175085968 AND (True AND name LIKE 'o%' AND  ( NOT expires = 0 ) )
  //Params:
  //{'api': <PLC.API.PLCAPI instance at 0xb7b466ac>,
  // 'columns': None,
  // 'expires': 1175085968,
  // 'self': [],
  // 'slice_filter': {'creator_person_id': (<type 'int'>, [<type 'int'>]), 'instantiation': (<type 'str'>, [<type 'str'>]), 'name': (<type 'str'>, [<type 'str'>]), 'slice_id': (<type 'int'>, [<type 'int'>]), 'created': (<type 'int'>, [<type 'int'>]), 'url': (<type 'str'>, [<type 'str'>]), 'max_nodes': (<type 'int'>, [<type 'int'>]), 'expires': (<type 'int'>, [<type 'int'>]), 'site_id': (<type 'int'>, [<type 'int'>]), 'peer_slice_id': (<type 'int'>, [<type 'int'>]), 'peer_id': (<type 'int'>, [<type 'int'>]), 'description': (<type 'str'>, [<type 'str'>])},
  // 'sql': "SELECT creator_person_id, instantiation, slice_attribute_ids, name, slice_id, created, url, max_nodes, person_ids, expires, site_id, peer_slice_id, node_ids, peer_id, description FROM view_slices WHERE is_deleted IS False AND expires > 1175085968 AND (True AND name LIKE 'o%' AND  ( NOT expires = 0 ) )"}
  //Traceback (most recent call last):
  //  File "/usr/bin/plcsh", line 139, in ?
  //    result = eval(command)
  //  File "<string>", line 0, in ?
  //  File "/usr/share/plc_api/PLC/Shell.py", line 51, in __call__
  //    return self.func(*args, **kwds)
  //  File "/usr/share/plc_api/PLC/Method.py", line 105, in __call__
  //    raise fault
  //PLCDBError: <Fault 106: 'GetSlices: Database error: Please contact OneLabPrivate Support <support@one-lab.org> and reference 52cfdd40-b57e-43cb-ace0-406d49408527'>

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;


$arr= $adm->GetSlices( NULL, array( "name" ) );

foreach( $arr as $slices ) {
  $useArr[]= $slices['name'];
}


$input = strtolower( $_GET['input'] );
$len = strlen($input);

$aResults = array();

if ($len) {
  for ( $i=0; $i<count($useArr); $i++ ) {
    if ( strtolower( substr( $useArr[$i], 0, $len ) ) == $input )
      $aResults[] = $useArr[$i];
  }
}


header("Content-Type: text/xml");

echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>
<results>";
for ( $i=0; $i<count($aResults); $i++ )
  echo"	<rs>". $aResults[$i] ."</rs>";

echo "
</results>
";

?>
