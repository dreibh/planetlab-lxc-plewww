/*
  $Id$

 instead of explicitly calling
 Nifty ('div#$id','medium');
 on every single element as the original niftycube.js recommends,
 we just do that on every element that has one of the
 the nifty-{small,medium,big} class set
*/

var nifty_inited = false;

function nifty_init () {
  
  if ( nifty_inited ) return;
  const sizes = { 0: 'small', 1: 'medium', 2: 'big', length: 3}
  for (const [int, size] of Object.entries(sizes)) {

    let elements = document.getElementsByClassName('nifty-'+size)
    for (const element of elements) {
      // use Rounded rather than Nifty
      // the latter needs an id that some elements don't have
      // plus, it's more efficient anyway
      pleRounded(element, size)
    }
  }
  nifty_inited = true
}
