<?php
   /**
    **  login.php -- simple login screen
    ** 
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This a simple login screen. Some housekeeping is done to clean
    **  cookies and find language.
    **
    **/

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($i18n_php))
      include("../functions/i18n.php");
   if (!isset($plugin_php))
      include("../functions/plugin.php");

   // let's check to see if they compiled with gettext support
   if (!function_exists("_")) {
      function _($string) {
         return $string;
      }
   } else {
      // $squirrelmail_language is set by a cookie when the user selects
      // language and logs out

      // Use HTTP content language negotiation if cookie not set
      if (!isset($squirrelmail_language) && isset($HTTP_ACCEPT_LANGUAGE)) {
         $squirrelmail_language = substr($HTTP_ACCEPT_LANGUAGE, 0, 2);
      }

      if (isset($squirrelmail_language)) {
         if ($squirrelmail_language != "en" && $squirrelmail_language != "") {
            putenv("LC_ALL=".$squirrelmail_language);
            bindtextdomain("squirrelmail", "../locale/");
            textdomain("squirrelmail");
            header ("Content-Type: text/html; charset=".$languages[$squirrelmail_language]["CHARSET"]);
         }
      }
   }

   // Need the base URI to set the cookies. (Same code as in webmail.php)
   ereg ("(^.*/)[^/]+/[^/]+$", $PHP_SELF, $regs);
   $base_uri = $regs[1];

   setcookie("username", "", 0, $base_uri);
   setcookie("key", "", 0, $base_uri);
   setcookie("logged_in", 0, 0, $base_uri);

   // In case the last session was not terminated properly, make sure
   // we get a new one.
   setcookie("PHPSESSID", "", 0, $base_uri);

   echo "<HTML>";
   echo "<HEAD><TITLE>";
   echo _("SquirrelMail Login");
   echo "</TITLE></HEAD>\n";
   echo "<BODY TEXT=000000 BGCOLOR=#FFFFFF LINK=0000CC VLINK=0000CC ALINK=0000CC>\n";
 
   echo "<FORM ACTION=\"webmail.php\" METHOD=\"POST\" NAME=f>\n";
   echo "<CENTER><IMG SRC=\"$org_logo\"</CENTER>\n";
   echo "<CENTER><SMALL>";
   printf (_("SquirrelMail version %s"), $version);
   echo "<BR>\n";
   echo _("By the SquirrelMail Development Team");
   echo "<BR></SMALL><CENTER>\n";
   echo "<TABLE COLS=1 WIDTH=350>\n";
   echo "   <TR>\n";
   echo "      <TD BGCOLOR=#DCDCDC>\n";
   echo "         <B><CENTER>";
   printf (_("%s Login"), $org_name);
   echo "</CENTER></B>\n";
   echo "      </TD>\n";
   echo "   </TR><TR>\n";
   echo "      <TD BGCOLOR=#FFFFFF>\n";
   echo "         <TABLE COLS=2 WIDTH=100%>\n";
   echo "            <TR>\n";
   echo "               <TD WIDTH=30% ALIGN=right>\n";
   echo _("Name:");
   echo "               </TD><TD WIDTH=* ALIGN=left>\n";
   echo "                  <INPUT TYPE=TEXT NAME=username>\n";
   echo "               </TD>\n";
   echo "            </TR><TR>\n";
   echo "               <TD WIDTH=30% ALIGN=right>\n";
   echo _("Password:");
   echo "               </TD><TD WIDTH=* ALIGN=left>\n";
   echo "                  <INPUT TYPE=PASSWORD NAME=key>\n";
   echo "               </TD>\n"; 
   echo "         </TABLE>\n";
   echo "      </TD>\n";
   echo "   </TR><TR>\n";
   echo "      <TD>\n";
   echo "         <CENTER><INPUT TYPE=SUBMIT VALUE=\"";
   echo _("Login");
   echo "\"></CENTER>\n";
   echo "      </TD>\n";
   echo "   </TR>\n";
   echo "</TABLE>\n";
   echo "<input type=hidden name=just_logged_in value=1>\n";
   echo "</FORM>\n";
   do_hook("login_bottom");
?>
</BODY>
</HTML>

