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
   include ("../config/config.php");
   include("../src/load_prefs.php");

   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   echo "<BR><BR><TABLE BGCOLOR=\"$color[4]\" BORDER=0 COLS=1 WIDTH=50% CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER>";
   echo "   <TR BGCOLOR=\"$color[3]\" WIDTH=100%>";
   echo "      <TD ALIGN=CENTER>";
   echo "         <FONT FACE=\"Arial,Helvetica\"><B>Sign Out</B></FONT>";
   echo "      </TD>";
   echo "   </TR>";
   echo "   <TR BGCOLOR=\"$color[4]\" WIDTH=100%>";
   echo "      <TD ALIGN=CENTER>";
   echo "         <FONT FACE=\"Arial,Helvetica\"><BR>You have been successfully signed out.<BR></FONT>";
   echo "         <FONT FACE=\"Arial,Helvetica\">Click here to <A HREF=\"login.php\" TARGET=_top>log back in.</A></FONT><BR><BR>";
   echo "      </TD>";
   echo "   </TR>";
   echo "   <TR BGCOLOR=\"$color[0]\" WIDTH=100%>";
   echo "      <TD ALIGN=CENTER>";
   echo "         <FONT FACE=\"Arial,Helvetica\"><BR></FONT>";
   echo "      </TD>";
   echo "   </TR>";
   echo "</TABLE>";
?>
</BODY>
</HTML>

