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

    if ($theme_css != '') {
        echo "<LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"$theme_css\">\n";
    }

    /* Output the javascript onload function. */
    echo "<SCRIPT LANGUAGE=\"JavaScript\">\n" .
         "<!--\n".
         "  function squirrelmail_loginpage_onload() {\n".
         "    document.forms[0].js_autodetect_results.value = '" . SMPREF_JS_ON . "';\n".
         "    document.forms[0].elements[0].focus();\n".
         "  }\n".
         "// -->\n".
         "</script>\n";

    /* Set the title of this page. */
    echo "<TITLE>$org_name - " . _("Login") . "</TITLE></HEAD>\n".
         "<BODY TEXT=#000000 BGCOLOR=#FFFFFF LINK=#0000CC VLINK=#0000CC ALINK=#0000CC onLoad='squirrelmail_loginpage_onload();'>\n".
         "<FORM ACTION=\"redirect.php\" METHOD=\"POST\" NAME=f>\n";

    $username_form_name = 'login_username';
    $password_form_name = 'secretkey';
    do_hook('login_top');

    $loginname_value = (isset($loginname) ? htmlspecialchars($loginname) : '');

    echo "<CENTER>".
         "  <IMG SRC=\"$org_logo\"><BR>\n".
         ( $hide_sm_attributions ? '' :
           '<SMALL>' . sprintf (_("SquirrelMail version %s"), $version) . "<BR>\n".
           '  ' . _("By the SquirrelMail Development Team") . "<BR></SMALL>\n" ) .
         "</CENTER>\n".

         "<CENTER>\n".
         "<TABLE COLS=1 WIDTH=350>\n".
         "   <TR><TD BGCOLOR=#DCDCDC>\n".
         '      <B><CENTER>' . sprintf (_("%s Login"), $org_name) . "</CENTER></B>\n".
         "   </TD></TR>".
         "   <TR><TD BGCOLOR=\"#FFFFFF\"><TABLE COLS=2 WIDTH=\"100%\">\n".
         "      <TR>\n".
         '         <TD WIDTH=30% ALIGN=right>' . _("Name:") . "</TD>\n".
         "         <TD WIDTH=* ALIGN=left>\n".
         "            <INPUT TYPE=TEXT NAME=\"$username_form_name\" VALUE=\"$loginname_value\">\n".
         "         </TD>\n".
         "      </TR>\n".
         "      <TR>\n".
         '         <TD WIDTH="30%" ALIGN=right>' . _("Password:") . "</TD>\n".
         "         <TD WIDTH=* ALIGN=left>\n".
         "            <INPUT TYPE=PASSWORD NAME=\"$password_form_name\">\n".
         "            <INPUT TYPE=HIDDEN NAME=\"js_autodetect_results\" VALUE=\"" . SMPREF_JS_OFF . "\">\n".
         "            <INPUT TYPE=HIDDEN NAME=\"just_logged_in\" value=1>\n";
    if ($rcptaddress != '') {
        echo "         <INPUT TYPE=HIDDEN NAME=\"rcptemail\" VALUE=\"".htmlspecialchars($rcptaddress)."\">\n";
    }
    echo "         </TD>\n".
         "      </TR>\n".
         "   </TABLE></TD></TR>\n".
         "   <TR><TD>\n".
         '      <CENTER><INPUT TYPE=SUBMIT VALUE="' . _("Login") . "\"></CENTER>\n".
         "   </TD></TR>\n".
         "</TABLE>\n".
         "</CENTER>\n";

    do_hook('login_form');
    echo "</FORM>\n";

    do_hook('login_bottom');
    echo "</BODY>\n".
         "</HTML>\n";
?>
