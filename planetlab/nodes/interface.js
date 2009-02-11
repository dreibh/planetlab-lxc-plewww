/* $Id$ */

/* using prototype.js */

/* disable/enable input fields according to the selected method */
function updateMethodFields() {
  var method=$('method');
  var index = method.selectedIndex;
  var selectedText = method[index].text;

  var is_static = selectedText == 'Static';
  var is_tap = selectedText == 'TUN/TAP';

  $('netmask').disabled= !is_static;
  $('network').disabled= !is_static;
  $('gateway').disabled= !is_static && !is_tap;
  $('broadcast').disabled= !is_static;
  $('dns1').disabled= !is_static;
  $('dns2').disabled= !is_static;
}

/* updates broadcast & network from IP and netmask, as long as they are reasonably set */

/* a is assumed to be a 4-items array text.split('.') */
function arr_to_int (as) {
  /*a=as.map(parseInt);*/
  a=as.map( function (x) { return parseInt(x) & 0xff;} );
  return (a[0]<<24) | (a[1]<<16) | (a[2]<<8) | a[3];
}
function int_to_arr (n) {
  var a=[];
  a[0]=(n&0xff000000)>>>24;
  a[1]=(n&0x00ff0000)>>>16;
  a[2]=(n&0x0000ff00)>>>8;
  a[3]=(n&0x000000ff)>>>0;
  return a;
}

function int_to_bits (n) {
  var a=[];
  for (var i=0; i<32; i++) {
    a[31-i]=n&1;
    n=n>>>1;
  }
  return a;
}

function mask (masklen) {
  return ( 0xffffffff<<(32-masklen));
}

// input is the user-typed text
// return the number of bits in the mask (like 24 for a /24) or -1 if the mask is wrong
function get_masklen (nm) {
  var a = nm.split('.');
  if ( 4 != a.length ) return -1;
  var n = arr_to_int (a);
  var bits = int_to_bits (n);
  var masklen=0;
  while (bits[masklen] && masklen<32 ) masklen++;
  // masklen holds the number of consecutive bits; just need to check
  var n_mask = mask(masklen);
  return (n == n_mask) ? masklen : -1;
}


// returns network and broadcast from ip and masklen
function get_derived (n,masklen) {
  var n_mask = mask(masklen);
  var r=[];
  r[0]=int_to_arr(n&n_mask).join(".");
  r[1]=int_to_arr(n|~n_mask).join(".");
  return r;
}

function same_subnet (ip1,ip2,masklen) {
  var n1=arr_to_int(ip1.split("."));
  var n2=arr_to_int(ip2.split("."));
  return (n1&mask(masklen)) == (n2 & mask(masklen));
}

function networkHelper () {
  var ip=$('ip').value;
  var nm=$('netmask').value;

  var ip_a = ip.split('.');
  var nm_a = ip.split('.');
  
  /* don't trigger if the input does not make sense */
  if (ip_a.length != 4) return; 
  if (ip_a[3] == "") return;
  if (nm_a.length != 4) return; 
  if (nm_a[3] == "") return;
  
  /*check netmask*/
  var masklen=get_masklen (nm);
  if (masklen < 0) return;

  var ip_n=arr_to_int(ip_a);
  var derived = get_derived(ip_n,masklen);

  $('network').value=derived[0];
  $('broadcast').value=derived[1];
}

/* check one */
function subnetChecker (args) {
  id=args[0];
  optional=args[1];

  var ip2=$(id).value;
  if (optional && (ip2=="")) return "";
  if ( ip2.split(".").length != 4) return "Inconsistent value for " + id;

  var masklen = get_masklen ($('netmask').value);
  if (masklen < 0) return "Inconsistent netmask";

  var ip=$('ip').value;
  if ( ip.split(".").length != 4) return "Inconsistent IP";

  if ( ! same_subnet (ip,ip2,masklen) ) 
    return id + ' ' + ip2 + ' is not in the /' + masklen + ' subnet range';
  
  return "";
}

function formSubmit () {
  // get error strings, and remove the empty ones
  // dns2 is optional
  var errors=['gateway','dns1'].zip ([true,true,false],subnetChecker).reject( function (s) {return s.length==0;} );
  if ( ! errors.length)
    $('ip').up('form').submit();
  else
    alert(errors.join("\n"));
}
  
