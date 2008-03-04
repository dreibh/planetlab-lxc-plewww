<?php
// echo "<SCRIPT LANGUAGE='javascript' TYPE='text/javascript' SRC='/calendar/CalendarPopup.js'>
//</script>"
//
echo "<SCRIPT LANGUAGE='JavaScript' ID='js15' SRC='calendar/CalendarPopup.js'>
";

echo "var cal15 = new CalendarPopup();
cal15.setReturnFunction('setMultipleValues4');
function setMultipleValues4(y,m,d) {
     document.forms[0].date15_year.value=y;
     document.forms[0].date15_month.selectedIndex=m;
     for (var i=0; i<document.forms[0].date15_date.options.length; i++) {
          if (document.forms[0].date15_date.options[i].value==d) {
               document.forms[0].date15_date.selectedIndex=i;
               }
          }
     }";

//echo "function getDateString(y_obj,m_obj,d_obj) {
    // var y = y_obj.options[y_obj.selectedIndex].value;
    // var m = m_obj.options[m_obj.selectedIndex].value;
    // var d = d_obj.options[d_obj.selectedIndex].value;
    // if (y=="" || m=="")  return null; 
    // if (d=="") d=1; 
    // return str= y+'-'+m+'-'+d;
//   }";

echo "var cal16 = new CalendarPopup();
cal16.setReturnFunction('setMultipleValues5');
function setMultipleValues5(y,m,d) {
     document.forms[0].date16_year.value=y;
     document.forms[0].date16_month.selectedIndex=m;
     for (var i=0; i<document.forms[0].date16_date.options.length; i++) {
          if (document.forms[0].date16_date.options[i].value==d) {
               document.forms[0].date16_date.selectedIndex=i;
               }
          }
     }


</script>";

echo "
<SCRIPT LANGUAGE=' JavaScript' >writeSource(' js15' );</SCRIPT>
<table align='bottom'>
<tr>
       <th>START</th>
       <th>END</th>
</tr>

<tr>
      <td>    
        <SELECT NAME=' date15_month' >
	<OPTION>
	<OPTION VALUE=' Jan' >January
	<OPTION VALUE=' Feb' >February
	<OPTION VALUE=' Mar' >March
	<OPTION VALUE=' Apr' >April
	<OPTION VALUE=' May' >May
	<OPTION VALUE=' Jun' >June
	<OPTION VALUE=' Jul' >July
	<OPTION VALUE=' Aug' >August
	<OPTION VALUE=' Sep' >September
	<OPTION VALUE=' Oct' >October
	<OPTION VALUE=' Nov' >November
	<OPTION VALUE=' Dec' >December

</SELECT>

<SELECT NAME=' date15_date' >
	<OPTION>
	<OPTION VALUE=' 1' >1
	<OPTION VALUE=' 2' >2
	<OPTION VALUE=' 3' >3
	<OPTION VALUE=' 4' >4
	<OPTION VALUE=' 5' >5
	<OPTION VALUE=' 6' >6
	<OPTION VALUE=' 7' >7
	<OPTION VALUE=' 8' >8
	<OPTION VALUE=' 9' >9
	<OPTION VALUE=' 10' >10
	<OPTION VALUE=' 11' >11
	<OPTION VALUE=' 12' >12
	<OPTION VALUE=' 13' >13
	<OPTION VALUE=' 14' >14
	<OPTION VALUE=' 15' >15
	<OPTION VALUE=' 16' >16
	<OPTION VALUE=' 17' >17
	<OPTION VALUE=' 18' >18
	<OPTION VALUE=' 19' >19
	<OPTION VALUE=' 20' >20
	<OPTION VALUE=' 21' >21
	<OPTION VALUE=' 22' >22
	<OPTION VALUE=' 23' >23
	<OPTION VALUE=' 24' >24
	<OPTION VALUE=' 25' >25
	<OPTION VALUE=' 26' >26
	<OPTION VALUE=' 27' >27
	<OPTION VALUE=' 28' >28
	<OPTION VALUE=' 29' >29
	<OPTION VALUE=' 30' >30
	<OPTION VALUE=' 31' >31

</SELECT>
<SELECT NAME=' date15_year' >
	<OPTION>
	<OPTION VALUE=' 2000' >2000
	<OPTION VALUE=' 2001' >2001
	<OPTION VALUE=' 2002' >2002
	<OPTION VALUE=' 2003' >2003
	<OPTION VALUE=' 2004' >2004
	<OPTION VALUE=' 2005' >2005
	<OPTION VALUE=' 2006' >2006
	<OPTION VALUE=' 2007' >2007
</SELECT>


<A HREF=' #'  onClick=' cal15.showCalendar('anchor15',getDateString(document.forms[0].date15_year,document.forms[0].date15_month,document.forms[0].date15_date)); return false;'  TITLE=' cal15.showCalendar('anchor15',getDateString(document.forms[0].date15_year,document.forms[0].date15_month,document.forms[0].date15_date)); return false;'  NAME=' anchor15'  ID=' anchor15' >Select Date</A>
&nbsp;&nbsp;&nbsp;
</td>

<TD>
 <SELECT NAME=' date16_month' >

	<OPTION>
	<OPTION VALUE=' Jan' >January
	<OPTION VALUE=' Feb' >February
	<OPTION VALUE=' Mar' >March
	<OPTION VALUE=' Apr' >April
	<OPTION VALUE=' May' >May
	<OPTION VALUE=' Jun' >June
	<OPTION VALUE=' Jul' >July
	<OPTION VALUE=' Aug' >August
	<OPTION VALUE=' Sep' >September
	<OPTION VALUE=' Oct' >October
	<OPTION VALUE=' Nov' >November
	<OPTION VALUE=' Dec' >December
</SELECT>
 
<SELECT NAME=' date16_date' >
	<OPTION>
	<OPTION VALUE=' 1' >1
	<OPTION VALUE=' 2' >2
	<OPTION VALUE=' 3' >3
	<OPTION VALUE=' 4' >4
	<OPTION VALUE=' 5' >5
	<OPTION VALUE=' 6' >6
	<OPTION VALUE=' 7' >7
	<OPTION VALUE=' 8' >8
	<OPTION VALUE=' 9' >9
	<OPTION VALUE=' 10' >10
	<OPTION VALUE=' 11' >11
	<OPTION VALUE=' 12' >12
	<OPTION VALUE=' 13' >13
	<OPTION VALUE=' 14' >14
	<OPTION VALUE=' 15' >15
	<OPTION VALUE=' 16' >16
	<OPTION VALUE=' 17' >17
	<OPTION VALUE=' 18' >18
	<OPTION VALUE=' 19' >19
	<OPTION VALUE=' 20' >20
	<OPTION VALUE=' 21' >21
	<OPTION VALUE=' 22' >22
	<OPTION VALUE=' 23' >23
	<OPTION VALUE=' 24' >24
	<OPTION VALUE=' 25' >25
	<OPTION VALUE=' 26' >26
	<OPTION VALUE=' 27' >27
	<OPTION VALUE=' 28' >28
	<OPTION VALUE=' 29' >29
	<OPTION VALUE=' 30' >30
	<OPTION VALUE=' 31' >31

</SELECT>
<SELECT NAME=' date16_year' >
	<OPTION>
	<OPTION VALUE=' 2000' >2000
	<OPTION VALUE=' 2001' >2001
	<OPTION VALUE=' 2002' >2002
	<OPTION VALUE=' 2003' >2003
	<OPTION VALUE=' 2004' >2004
	<OPTION VALUE=' 2005' >2005
	<OPTION VALUE=' 2006' >2006
	<OPTION VALUE=' 2007' >2007
</SELECT>

<A HREF=' #'  onClick=' var d=getDateString(document.forms[0].date16_year,document.forms[0].date16_month,document.forms[0].date16_date); cal16.showCalendar('anchor16',(d==null)?getDateString(document.forms[0].date15_year,document.forms[0].date15_month,document.forms[0].date15_date):d); return false;'  TITLE=' var d=getDateString(document.forms[0].date16_year,document.forms[0].date16_month,document.forms[0].date16_date); cal16.showCalendar('anchor16',(d==null)?getDateString(document.forms[0].date15_year,document.forms[0].date15_month,document.forms[0].date15_date):d); return false;'  NAME=' anchor16'  ID=' anchor16' >Select Date</A>
</TD></TR>
";
?>