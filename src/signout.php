<?
   session_start();

	/**
	 **  signout.php
	 **
	 **  Clears the cookie, and logs them out.
	 **
	 **/
	
	$username = "";
	$key = "";
	$logged_in = 0;

        // $squirrelmail_language is set by a cookie when the user
        // selects language
        if (isset($squirrelmail_language)) {
           if ($squirrelmail_language != "en") {
              putenv("LANG=".$squirrelmail_language);
              bindtextdomain("squirrelmail", "../locale/");
              textdomain("squirrelmail");
           }
        }
	
#	setcookie("username", "", time(), "/");
#	setcookie("key", "", time(), "/");
#	setcookie("logged_in", 0, time(), "/");
?>
<HTML>
<?
   echo "<BODY TEXT=000000 BGCOLOR=#FFFFFF LINK=0000CC VLINK=0000CC ALINK=0000CC>\n";
   echo "<BR><BR><TABLE BGCOLOR=#FFFFFF BORDER=0 COLS=1 WIDTH=50% CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER>";
   echo "   <TR BGCOLOR=#DCDCDC WIDTH=100%>";
   echo "      <TD ALIGN=CENTER>";
   echo "         <B>";
   echo _("Sign Out");
   echo "</B>";
   echo "      </TD>";
   echo "   </TR>";
   echo "   <TR BGCOLOR=#FFFFFF WIDTH=100%>";
   echo "      <TD ALIGN=CENTER>";
   echo "         <BR>";
   echo _("You have been successfully signed out.");
   echo "<BR>";
   echo _("Click here to ");
   echo "<A HREF=\"login.php\" TARGET=_top>";
   echo _("log back in.");
   echo "</A><BR><BR>";
   echo "      </TD>";
   echo "   </TR>";
   echo "   <TR BGCOLOR=#DCDCDC WIDTH=100%>";
   echo "      <TD ALIGN=CENTER>";
   echo "         <BR>";
   echo "      </TD>";
   echo "   </TR>";
   echo "</TABLE>";
?>
</BODY>
</HTML>
<?
   session_destroy();
?>
