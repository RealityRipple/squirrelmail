<?php
   chdir ('../');
   require_once('../src/validate.php');
   require_once('../src/load_prefs.php');
?>
<HTML>
<TITLE>New Mail</TITLE>
<BODY bgcolor=<?php echo $color[4] ?> topmargin=0 leftmargin=0
rightmargin=0 marginwidth=0 marginheight=0>
<CENTER>
<table width=100% cellpadding=2 cellspacing=2 border=0>
<tr>
   <td bgcolor=<?php echo $color[0] ?>>
      <b><center>SquirrelMail Notice:</center></b>
   </td>
</tr><tr>   
   <td>
      <center>
      <br>
      <big><font color=<?php echo $color[2] ?>>You have new
mail!</font></big><br>
      <form name=nm>
         <input type=button name=bt value="Close Window"
onClick="javascript:window.close();">
      </form>
      </center>
   </td>
</tr>
</table>
</CENTER>
<script language=javascript>
<!--
   document.nm.bt.focus();
-->
</script>
</BODY></HTML>
