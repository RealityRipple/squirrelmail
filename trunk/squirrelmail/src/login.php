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
?>
<HTML>
<?
   include("../config/config.php");
   include("../functions/strings.php");

   echo "<BODY TEXT=000000 BGCOLOR=FFFFFF LINK=0000CC VLINK=0000CC ALINK=0000CC>\n";
   echo "<FORM ACTION=webmail.php METHOD=\"POST\" NAME=f>\n";
   echo "<CENTER><IMG SRC=\"$org_logo\"</CENTER>\n";
   echo "<CENTER><FONT FACE=\"Arial,Helvetica\" SIZE=-2>";
   echo _("SquirrelMail version $version<BR>By the SquirrelMail Development Team");
   echo "<BR></FONT><CENTER>\n";
   echo "<TABLE COLS=1 WIDTH=350>\n";
   echo "   <TR>\n";
   echo "      <TD BGCOLOR=DCDCDC>\n";
   echo "         <B><CENTER><FONT FACE=\"Arial,Helvetica\">$org_name Login</FONT></CENTER></B>\n";
   echo "      </TD>\n";
   echo "   </TR><TR>\n";
   echo "      <TD BGCOLOR=FFFFFF>\n";
   echo "         <TABLE COLS=2 WIDTH=100%>\n";
   echo "            <TR>\n";
   echo "               <TD WIDTH=30% ALIGN=right>\n";
   echo "                  <FONT FACE=\"Arial,Helvetica\">";
   echo _("Name:");
   echo "		   </FONT>\n";
   echo "               </TD><TD WIDTH=* ALIGN=left>\n";
   echo "                  <CENTER><INPUT TYPE=TEXT NAME=username></CENTER>\n";
   echo "               </TD>\n";
   echo "            </TR><TR>\n";
   echo "               <TD WIDTH=30% ALIGN=right>\n";
   echo "                  <FONT FACE=\"Arial,Helvetica\">";
   echo _("Password:");
   echo "		   </FONT>\n";
   echo "               </TD><TD WIDTH=* ALIGN=left>\n";
   echo "                  <CENTER><INPUT TYPE=PASSWORD NAME=key></CENTER>\n";
   echo "               </TD>\n"; 
   echo "         </TABLE>\n";
   echo "      </TD>\n";
   echo "   </TR><TR>\n";
   echo "      <TD>\n";
   echo "         <CENTER><INPUT TYPE=SUBMIT VALUE=\"";
   echo _("Login");
   echo "></CENTER>\n";
   echo "      </TD>\n";
   echo "   </TR>\n";
   echo "</TABLE>\n";
   echo "</FORM>\n";
?>
</BODY>
</HTML>

