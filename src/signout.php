<?php
   session_start();

	/**
	 **  signout.php
	 **
	 **  Clears the cookie, and logs them out.
	 **
	 **/

	// Quick Fix for Gettext in LogOut Screen
if (!function_exists("_")) {
      function _($string) {
         return $string;
      }
   }

   // $squirrelmail_language is set by a cookie when the user
   // selects language
   if (isset($squirrelmail_language)) {
      if ($squirrelmail_language != "en") {
         putenv("LANG=".$squirrelmail_language);
         bindtextdomain("squirrelmail", "../locale/");
         textdomain("squirrelmail");
      }
   }

   include ("../src/load_prefs.php");
	
	setcookie("username", "", time(), "/");
	setcookie("key", "", time(), "/");
	setcookie("logged_in", 0, time(), "/");
   session_destroy();
?>
<HTML>
<?php
   echo "<BODY TEXT=000000 BGCOLOR=$color[4] LINK=$color[7] VLINK=$color[7] ALINK=$color[7]>\n";
   echo "<BR><BR><TABLE BGCOLOR=FFFFFF BORDER=0 COLS=1 WIDTH=50% CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER>";
   echo "   <TR BGCOLOR=$color[0] WIDTH=100%>";
   echo "      <TD ALIGN=CENTER>";
   echo "         <B>";
   echo _("Sign Out");
   echo "</B>";
   echo "      </TD>";
   echo "   </TR>";
   echo "   <TR BGCOLOR=$color[4] WIDTH=100%>";
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
   echo "   <TR BGCOLOR=$color[0] WIDTH=100%>";
   echo "      <TD ALIGN=CENTER>";
	echo "			<br>";
   echo "      </TD>";
   echo "   </TR>";
   echo "</TABLE>";
	echo "<br><br>";
?>
</BODY>
</HTML>
