<?
   setcookie("username", "", time(), "/");
   setcookie("key", "", time(), "/");
   setcookie("logged_in", 0, time(), "/");
?>
<HTML>
<BODY BGCOLOR=FFFFFF>
<?
   include("config/config.php3");

   echo "<FORM ACTION=webmail.php3 METHOD=POST NAME=f>\n";
   echo "<CENTER><IMG SRC=\"$org_logo\"</CENTER>\n";
   echo "<CENTER><FONT FACE=\"Arial,Helvetica\" SIZE=-2>SquirrelMail version $version<BR>By Nathan and Luke Ehresman<BR></FONT><CENTER>\n";
   echo "<TABLE COLS=1 WIDTH=350>\n";
   echo "   <TR>\n";
   echo "      <TD BGCOLOR=CCCCCC>\n";
   echo "         <B><CENTER><FONT FACE=\"Arial,Helvetica\">$org_name Login</FONT></CENTER></B>\n";
   echo "      </TD>\n";
   echo "   </TR><TR>\n";
   echo "      <TD BGCOLOR=FFFFFF>\n";
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

