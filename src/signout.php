<?
	/**
	 **  signout.php
	 **
	 **  Clears the cookie, and logs them out.
	 **
	 **/
	
	$username = "";
	$key = "";
	$logged_in = 0;
	
	setcookie("username", "", time(), "/");
	setcookie("key", "", time(), "/");
	setcookie("logged_in", 0, time(), "/");
?>
<HTML>
<?
   echo "<BODY TEXT=000000 BGCOLOR=FFFFFF LINK=0000CC VLINK=0000CC ALINK=0000CC>\n";
   echo "<BR><BR><TABLE BGCOLOR=FFFFFF BORDER=0 COLS=1 WIDTH=50% CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER>";
   echo "   <TR BGCOLOR=DCDCDC WIDTH=100%>";
   echo "      <TD ALIGN=CENTER>";
   echo "         <FONT FACE=\"Arial,Helvetica\"><B>Sign Out</B></FONT>";
   echo "      </TD>";
   echo "   </TR>";
   echo "   <TR BGCOLOR=FFFFFF WIDTH=100%>";
   echo "      <TD ALIGN=CENTER>";
   echo "         <FONT FACE=\"Arial,Helvetica\"><BR>You have been successfully signed out.<BR></FONT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">Click here to <A HREF=\"login.php\" TARGET=_top>log back in.</A></FONT><BR><BR>";
   echo "      </TD>";
   echo "   </TR>";
   echo "   <TR BGCOLOR=DCDCDC WIDTH=100%>";
   echo "      <TD ALIGN=CENTER>";
   echo "         <FONT FACE=\"Arial,Helvetica\"><BR></FONT>";
   echo "      </TD>";
   echo "   </TR>";
   echo "</TABLE>";
?>
</BODY>
</HTML>

