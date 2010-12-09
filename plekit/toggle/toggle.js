
function plc_toggle(id){

	var area=$('toggle-area-'+id);
	area.toggle();
	var visible=$('toggle-image-visible-'+id);
	var hidden=$('toggle-image-hidden-'+id);
	if(area.visible()){
		visible.show();
		hidden.hide();
	}
	else{
		visible.hide();
		hidden.show();
	}
}

function plc_show_toggle_info(div, id) {
//debugfilter("showing "+div);

	var area=$('toggle-area-'+id);
	var visible=$('toggle-image-visible-'+id);
	var hidden=$('toggle-image-hidden-'+id);

	if (document.getElementById(div).style.display == "none") 
		document.getElementById(div).style.display = "";

	plc_toggle(id);
}
