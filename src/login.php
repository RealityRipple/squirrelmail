<?php
    /**
     * login.php -- simple login screen
     * 
     * Copyright (c) 1999-2001 The Squirrelmail Development Team
     * Licensed under the GNU GPL. For full terms see the file COPYING.
     *
     * This a simple login screen. Some housekeeping is done to clean
     * cookies and find language.
     *
     * $Id$
     */

    $rcptaddress = '';
    if (isset($emailaddress)) {
        if (stristr($emailaddress, 'mailto:')) {
            $rcptaddress = substr($emailaddress, 7);
        } else {
            $rcptaddress = $emailaddress;
        }
	 
        if (($pos = strpos($rcptaddress, '?')) !== false) {
            $a = substr($rcptaddress, $pos + 1);
	    $rcptaddress = substr($rcptaddress, 0, $pos);
            $a = explode('=', $a, 2);
            if (isset($a[1])) {
                $name = urldecode($a[0]);
                $val = urldecode($a[1]);
                global $$name;
                $$naame = $val;
            }
        }
      
        /* At this point, we have parsed a lot of the mailto stuff. */
        /*   Let's do the rest -- CC, BCC, Subject, Body            */
        /*   Note:  They can all be case insensitive                */
        foreach ($GLOBALS as $k => $v) {
            $key = strtolower($k);
            $value = urlencode($v);
            if ($key == 'cc') {
                $rcptaddress .= '&send_to_cc=' . $value;
            } else if ($key == 'bcc') {
                $rcptaddress .= '&send_to_bcc=' . $value;
            } else if ($key == 'subject') {
                $rcptaddress .= '&subject=' . $value;
            } else if ($key == 'body') {
                $rcptaddress .= '&body=' . $value;
            }
        }
      
        /* Double-encode in this fashion to get past redirect.php properly. */
        $rcptaddress = urlencode($rcptaddress);
    }

    require_once('../functions/strings.php');
    require_once('../config/config.php');
    require_once('../functions/i18n.php');
    require_once('../functions/plugin.php');
    require_once('../functions/constants.php');

    /*
     * $squirrelmail_language is set by a cookie when the user selects
     * language and logs out
     */
    set_up_language($squirrelmail_language, true);

    /* Need the base URI to set the cookies. (Same code as in webmail.php). */
    ereg ("(^.*/)[^/]+/[^/]+$", $PHP_SELF, $regs);
    $base_uri = $regs[1];
    @session_destroy();

    /*
     * In case the last session was not terminated properly, make sure
     * we get a new one.
     */
    $cookie_params = session_get_cookie_params(); 
    setcookie(session_name(),'',0,$cookie_params['path'].$cookie_params['domain']); 
    setcookie('username', '', 0, $base_uri);
    setcookie('key', '', 0, $base_uri);
    header ('Pragma: no-cache');

    do_hook('login_cookie');

    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">' .
         "\n\n" .
 	 "<HTML>\n" .
	 "<HEAD>\n";
			   
    if ($theme_css != "") {
        echo "<LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"$theme_css\">\n";
    }

    /* Output the javascript onload function. */
    echo "<SCRIPT LANGUAGE=\"JavaScript\">\n";
    echo "<!--\n";
    echo "  function squirrelmail_loginpage_onload() {\n";
    echo "    document.forms[0].js_autodetect_results.value = '" . SMPREF_JS_ON . "';\n";
    echo "    document.forms[0].elements[0].focus();\n";
    echo "  }\n";
    echo "// -->\n";
    echo "</script>\n";

    /* Set the title of this page. */
    echo "<TITLE>$org_name - " . _("Login") . "</TITLE></HEAD>\n";
    echo "<BODY TEXT=#000000 BGCOLOR=#FFFFFF LINK=#0000CC VLINK=#0000CC ALINK=#0000CC onLoad='squirrelmail_loginpage_onload();'>\n";
    echo "<FORM ACTION=\"redirect.php\" METHOD=\"POST\" NAME=f>\n";
   
    $username_form_name = 'login_username';
    $password_form_name = 'secretkey';
    do_hook('login_top');

    $loginname_value = (isset($loginname) ? htmlspecialchars($loginname) : '');
   
    echo "<CENTER><SMALL>";
    echo "  <IMG SRC=\"$org_logo\"><BR>\n";
    echo '  ' . sprintf (_("SquirrelMail version %s"), $version) . "<BR>\n";
    echo '  ' . _("By the SquirrelMail Development Team") . "<BR>\n";
    echo "</SMALL><CENTER>\n";

    echo "<TABLE COLS=1 WIDTH=350>\n";
    echo "   <TR><TD BGCOLOR=#DCDCDC>\n";
    echo '      <B><CENTER>' . sprintf (_("%s Login"), $org_name) . "</CENTER></B>\n";
    echo "   </TD></TR>";
    echo "   <TR><TD BGCOLOR=\"#FFFFFF\"><TABLE COLS=2 WIDTH=\"100%\">\n";
    echo "      <TR>\n";
    echo '         <TD WIDTH=30% ALIGN=right>' . _("Name:") . "</TD>\n";
    echo "         <TD WIDTH=* ALIGN=left>\n";
    echo "            <INPUT TYPE=TEXT NAME=\"$username_form_name\" VALUE=\"$loginname_value\">\n";
    echo "         </TD>\n";
    echo "      </TR>\n";
    echo "      <TR>\n";
    echo '         <TD WIDTH="30%" ALIGN=right>' . _("Password:") . "</TD>\n";
    echo "         <TD WIDTH=* ALIGN=left>\n";
    echo "            <INPUT TYPE=PASSWORD NAME=\"$password_form_name\">\n";
    echo "            <INPUT TYPE=HIDDEN NAME=\"js_autodetect_results\" VALUE=\"" . SMPREF_JS_OFF . "\">\n";
    echo "            <INPUT TYPE=HIDDEN NAME=\"just_logged_in\" value=1>\n";
    if ($rcptaddress != '') {
        echo "         <INPUT TYPE=HIDDEN NAME=\"rcptemail\" VALUE=\"".htmlspecialchars($rcptaddress)."\">\n";
    }
    echo "         </TD>\n";
    echo "      </TR>\n";
    echo "   </TABLE></TD></TR>\n";
    echo "   <TR><TD>\n";
    echo '      <CENTER><INPUT TYPE=SUBMIT VALUE="' . _("Login") . "\"></CENTER>\n";
    echo "   </TD></TR>\n";
    echo "</TABLE>\n";

    do_hook('login_form');
    echo "</FORM>\n";

    do_hook('login_bottom');
    echo "</BODY>\n";
    echo "</HTML>\n";
?>
