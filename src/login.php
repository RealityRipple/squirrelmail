<?
   /**
    **  login.php
    **
    **  Very simple login screen that clears the cookie every time it's loaded
    **
    **/

   setcookie("username", "", time(), "/");
   setcookie("key", "", time(), "/");
   setcookie("logged_in", 0, time(), "/");

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");

   // let's check to see if they compiled with gettext support
   if (!function_exists("_")) {
      function _($string) {
         return $string;
      }
   } else {
      // $squirrelmail_language is set by a cookie when the user selects
      // language
      if (isset($squirrelmail_language)) {
         if ($squirrelmail_language != "en") {
            putenv("LANG=".$squirrelmail_language);
            bindtextdomain("squirrelmail", "../locale/");
            textdomain("squirrelmail");
         }
      }
   }

   echo "<HTML>";
   echo "<HEAD><TITLE>";
   echo _("SquirrelMail Login");
   echo "</TITLE></HEAD>\n";
   echo "<BODY TEXT=000000 BGCOLOR=FFFFFF LINK=0000CC VLINK=0000CC ALINK=0000CC>\n";
 
   echo "<FORM ACTION=webmail.php METHOD=\"POST\" NAME=f>\n";
   echo "<CENTER><IMG SRC=\"$org_logo\"</CENTER>\n";
   echo "<CENTER><SMALL>";
   echo _("SquirrelMail version $version<BR>By the SquirrelMail Development Team");
   echo "<BR></SMALL><CENTER>\n";
   echo "<TABLE COLS=1 WIDTH=350>\n";
   echo "   <TR>\n";
   echo "      <TD BGCOLOR=DCDCDC>\n";
   echo "         <B><CENTER>$org_name Login</CENTER></B>\n";
   echo "      </TD>\n";
   echo "   </TR><TR>\n";
   echo "      <TD BGCOLOR=FFFFFF>\n";
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
   echo "</FORM>\n";
?>
</BODY>
</HTML>

