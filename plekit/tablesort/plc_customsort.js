/* $Id$ */
/*
    sortAlphaNumeric{Top,Bottom}
    -----------------------

    These functions are derived from sortAlphaNumeric
    inputs are expected to be alphaNumeric values e.g. 1, e, 1a, -23c, 54z

    when comparing two values, the following happens
    (*) if both have a numeric part, then they are compared; if equal the non-numeric part is used
    (*) if only one has a numeric part, then 
	. with sortAlphaNumericTop, the non-numeric value (typically n/a) happens first
	. with sortAlphaNumericBottom, the non-numeric value (typically n/a) happens second
*/
function _sortAlphaNumericPrepareData(tdNode, innerText){
        var aa = innerText.toLowerCase().replace(" ", "");
        var reg = /((\-|\+)?(\s+)?[0-9]+\.([0-9]+)?|(\-|\+)?(\s+)?(\.)?[0-9]+)([a-z]+)/;

        if(reg.test(aa)) {
                var aaP = aa.match(reg);
                return [aaP[1], aaP[8]];
        };

        // Return an array
        return isNaN(aa) ? ["",aa] : [aa,""];
}

/* non_numeric_first : 
   when comparing, say, '12' with 'n/a':
   if non_numeric_first is true, we return 1 (n/a greater than 12)
   otherwise we return -1 
*/
function _sortAlphaNumeric(a, b, non_numeric_first) {
        // Get the previously prepared array
        var aa = a[fdTableSort.pos];
        var bb = b[fdTableSort.pos];

        // Check numeric parts if not equal
        if(aa[0] != bb[0]) {
		// both are numeric and have different numeric parts : usual comparison
                if(aa[0] != "" && bb[0] != "") { return aa[0] - bb[0]; };
		// one numeric value is missing
                if(aa[0] == "" && bb[0] != "") 
			return non_numeric_first ? -1 : 1 ;
		else 
			return non_numeric_first ? 1 : -1 ;
	// from here and on, aa[0] == bb[0]
        } else if (aa[1] == bb[1]) {
	  return 0;
	// the alpha part differ
	} else {
	  return (aa[1]<bb[1] ? -1 : 1);
	}
}

function sortAlphaNumericBottomPrepareData(tdNode, innerText) {
	return _sortAlphaNumericPrepareData(tdNode, innerText);
}
function sortAlphaNumericBottom(a,b,) {
	return _sortAlphaNumeric (a,b,false);
}
function sortAlphaNumericTopPrepareData(tdNode, innerText) {
	return _sortAlphaNumericPrepareData(tdNode, innerText);
}
function sortAlphaNumericTop(a,b,) {
	return _sortAlphaNumeric (a,b,true);
}

