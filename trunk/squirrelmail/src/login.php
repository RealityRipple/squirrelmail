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

   echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   echo "<FORM ACTION=webmail.php METHOD=\"POST\" NAME=f>\n";
   echo "<CENTER><IMG SRC=\"$org_logo\"</CENTER>\n";
   echo "<CENTER><FONT FACE=\"Arial,Helvetica\" SIZE=-2>SquirrelMail version $version<BR>By Nathan and Luke Ehresman<BR></FONT><CENTER>\n";
   echo "<TABLE COLS=1 WIDTH=350>\n";
   echo "   <TR>\n";
   echo "      <TD BGCOLOR=\"$color[0]\">\n";
   echo "         <B><CENTER><FONT FACE=\"Arial,Helvetica\">$org_name Login</FONT></CENTER></B>\n";
   echo "      </TD>\n";
   echo "   </TR><TR>\n";
   echo "      <TD BGCOLOR=\"$color[4]\">\n";
   echo "         <TABLE COLS=2 WIDTH=100%>\n";
   echo "            <TR>\n";
   echo "               <TD WIDTH=30% ALIGN=right>\n";
   echo "                  <FONT FACE=\"Arial,Helvetica\">Name:</FONT>\n";
   echo "               </TD><TD WIDTH=* ALIGN=left>\n";
   echo "                  <CENTER><INPUT TYPE=TEXT NAME=username></CENTER>\n";
   echo "               </TD>\n";
   echo "            </TR><TR>\n";
   echo "               <TD WIDTH=30% ALIGN=right>\n";
   echo "                  <FONT FACE=\"Arial,Helvetica\">Password:</FONT>\n";
   echo "               </TD><TD WIDTH=* ALIGN=left>\n";
   echo "                  <CENTER><INPUT TYPE=PASSWORD NAME=key></CENTER>\n";
   echo "               </TD>\n"; 
   echo "         </TABLE>\n";
   echo "      </TD>\n";
   echo "   </TR><TR>\n";
   echo "      <TD>\n";
   echo "         <CENTER><INPUT TYPE=SUBMIT VALUE=Login></CENTER>\n";
   echo "      </TD>\n";
   echo "   </TR>\n";
   echo "</TABLE>\n";
   echo "</FORM>\n";
?>

<SCRIPT LANGUAGE="JavaScript">
   document.f.username.focus(); 
</SCRIPT>

</BODY>
</HTML>

