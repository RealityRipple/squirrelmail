<?php
   /**
    **  signout.php -- cleans up session and logs the user out
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Cleans up after the user. Resets cookies and terminates
    **  session.
    **
    **  $Id$
    **/

   session_start();

   if (!isset($strings_php))
      include("../functions/strings.php");

   include ("../src/load_prefs.php");

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($i18n_php))
      include("../functions/i18n.php");
   if (!isset($prefs_php))
      include ("../functions/prefs.php");
   if (!isset($plugin_php))
      include ("../functions/plugin.php");

   set_up_language(getPref($data_dir, $username, "language"));

   // If a user hits reload on the last page, $base_uri isn't set
   // because it was deleted with the session.
   if (! isset($base_uri))
   {
       ereg ("(^.*/)[^/]+/[^/]+$", $PHP_SELF, $regs);
       $base_uri = $regs[1];
   }

   do_hook("logout");
   setcookie("username", "", 0, $base_uri);
   setcookie("key", "", 0, $base_uri);
   setcookie("logged_in", "", 0, $base_uri);
   session_destroy();
?>
<HTML>
   <HEAD>
<?php
   if ($theme_css != "") {
      printf ('<LINK REL="stylesheet" TYPE="text/css" HREF="%s">', 
               $theme_css);
      echo "\n";
   }
   
   echo "<TITLE>$org_title - Signout</TITLE>\n";
   echo "</HEAD><BODY TEXT=$color[8] BGCOLOR=$color[4] LINK=$color[7] VLINK=$color[7] ALINK=$color[7]>\n";
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
   echo "<A HREF=\"login.php\" TARGET=_top>";
   echo _("Click here to log back in.");
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
