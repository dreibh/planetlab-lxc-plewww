
function addLoadEvent(func) {
  if (!document.getElementById | !document.getElementsByTagName) return
	var oldonload=window.onload
	if (typeof window.onload != 'function') { window.onload=func }
	else {
		window.onload=function() { oldonload(); func() }
	}
}

// ----------------------------------------------------------------------------- //

function show(id){
	if (!document.getElementsByTagName) return
	if (document.getElementById(id).style.display=='block'){
		document.getElementById(id).style.display='none'
	}
	else{
		document.getElementById(id).style.display='block'
	}
	//  focus is moved by the href of the link to the start of the help
}


function hide(id){
	if (document.getElementById) {document.getElementById(id).style.display='none'}
}

function copyValue (id1,id2) {
  if (document.getElementById) {document.getElementById(id2).value=document.getElementById(id1).value}
}


// ----------------------------------------------------------------------------- //

