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
    **  $Id$
    **/

   include("../functions/strings.php");
   include("../config/config.php");
   include('../functions/i18n.php');
   include("../functions/plugin.php");

   // $squirrelmail_language is set by a cookie when the user selects
   // language and logs out
   set_up_language($squirrelmail_language, true);

   // Need the base URI to set the cookies. (Same code as in webmail.php)
   ereg ("(^.*/)[^/]+/[^/]+$", $PHP_SELF, $regs);
   $base_uri = $regs[1];

   setcookie("username", '', 0, $base_uri);
   setcookie("key", '', 0, $base_uri);
   setcookie("logged_in", 0, 0, $base_uri);
   header ("Pragma: no-cache");

   // In case the last session was not terminated properly, make sure
   // we get a new one.
	$cookie_params = session_get_cookie_params(); 
	setcookie(session_name(),"",0,$cookie_params["domain"].$cookie_params["path"]); 

   do_hook('login_cookie');
   echo "<HTML>";
   echo "<HEAD><TITLE>";
   echo $org_name . " - " . _("Login");
   echo "</TITLE></HEAD>\n";
   echo "<BODY TEXT=000000 BGCOLOR=#FFFFFF LINK=0000CC VLINK=0000CC ALINK=0000CC>\n";
   echo "<FORM ACTION=\"redirect.php\" METHOD=\"POST\" NAME=f>\n";
   
   $username_form_name = 'login_username';
   $password_form_name = 'secretkey';
   do_hook('login_top');
   
   echo "<CENTER><IMG SRC=\"$org_logo\"></CENTER>\n";
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
   echo "                  <INPUT TYPE=TEXT NAME=\"$username_form_name\">\n";
   echo "               </TD>\n";
   echo "            </TR><TR>\n";
   echo "               <TD WIDTH=30% ALIGN=right>\n";
   echo _("Password:");
   echo "               </TD><TD WIDTH=* ALIGN=left>\n";
   echo "                  <INPUT TYPE=PASSWORD NAME=\"$password_form_name\">\n";
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
   do_hook('login_form');
   echo "</FORM>\n";
   do_hook("login_bottom");
?>
</BODY>
</HTML>

