<?php

/**
 * page_header.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Prints the page header (duh)
 *
 * $Id$
 */

require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/imap_mailbox.php');
require_once(SM_PATH . 'functions/global.php');

/* Always set up the language before calling these functions */
function displayHtmlHeader( $title = 'SquirrelMail', $xtra = '', $do_hook = TRUE ) {
    global $squirrelmail_language;

    if ( !sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION) ) {
        global $base_uri;
    }
    global $theme_css, $custom_css, $pageheader_sent;

    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">' .
         "\n\n" . html_tag( 'html' ,'' , '', '', '' ) . "\n<head>\n";

    if ( !isset( $custom_css ) || $custom_css == 'none' ) {
        if ($theme_css != '') {
            echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$theme_css\" />";
        }
    } else {
        echo '<link rel="stylesheet" type="text/css" href="' .
             $base_uri . 'themes/css/'.$custom_css.'" />';
    }
    
    if ($squirrelmail_language == 'ja_JP') {
        echo "<!-- \xfd\xfe -->\n";
        echo '<meta http-equuiv="Content-type" content="text/html; charset=euc-jp">' . "\n";
    }
    
    if ($do_hook) {
        do_hook('generic_header');
    }
    
    echo "\n<title>$title</title>$xtra\n";

    /* work around IE6's scrollbar bug */
    echo <<<ECHO
<style type="text/css">
<!--
  /* avoid stupid IE6 bug with frames and scrollbars */
  body { 
      voice-family: "\"}\""; 
      voice-family: inherit; 
      width: expression(document.documentElement.clientWidth - 30);
  }
-->
</style>

ECHO;

    echo "\n</head>\n\n";

    /* this is used to check elsewhere whether we should call this function */
    $pageheader_sent = TRUE;
}

function makeInternalLink($path, $text, $target='') {
    sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION);
    if ($target != '') {
        $target = " target=\"$target\"";
    }
    return '<a href="'.$base_uri.$path.'"'.$target.'>'.$text.'</a>';
}

function displayInternalLink($path, $text, $target='') {
    echo makeInternalLink($path, $text, $target);
}

function displayPageHeader($color, $mailbox, $xtra='', $session=false) {

    global $hide_sm_attributions, $PHP_SELF, $frame_top,
           $compose_new_win, $compose_width, $compose_height,
           $attachemessages, $provider_name, $provider_uri,
           $javascript_on, $default_use_mdn, $mdn_user_support;

    sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION );
    sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION );
    $module = substr( $PHP_SELF, ( strlen( $PHP_SELF ) - strlen( $base_uri ) ) * -1 );
    if ($qmark = strpos($module, '?')) {
        $module = substr($module, 0, $qmark);
    }
    if (!isset($frame_top)) {
        $frame_top = '_top';
    }

    if ($session) {
	$compose_uri = $base_uri.'src/compose.php?mailbox='.urlencode($mailbox).'&amp;attachedmessages=true&amp;session='."$session";
    } else {
        $compose_uri = $base_uri.'src/compose.php?newmessage=1';
	$session = 0;
    }
  
    if($javascript_on) { 

      switch ( $module ) {
        case 'src/read_body.php':
                $js ='';

                // compose in new window code
                if ($compose_new_win == '1') {
                    if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
                        $compose_width = '640';
                    }
                    if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
                        $compose_height = '550';
                    }
                    $js .= "function comp_in_new(comp_uri) {\n".
    		     "       if (!comp_uri) {\n".
    		     '           comp_uri = "'.$compose_uri."\";\n".
    		     '       }'. "\n".
                         '    var newwin = window.open(comp_uri' .
                         ', "_blank",'.
                         '"width='.$compose_width. ',height='.$compose_height.
                         ',scrollbars=yes,resizable=yes");'."\n".
                         "}\n\n";
                }

                // javascript for sending read receipts
                if($default_use_mdn && $mdn_user_support) {
                    $js .= 'function sendMDN() {'."\n".
                           "    mdnuri=window.location+'&sendreceipt=1'; ".
                           "var newwin = window.open(mdnuri,'right');".
    	               "\n}\n\n";
                }

                // if any of the above passes, add the JS tags too.
                if($js) {
                    $js = "\n".'<script language="JavaScript" type="text/javascript">' .
                        "\n<!--\n" . $js . "// -->\n</script>\n";
                }

                displayHtmlHeader ('SquirrelMail', $js);
                $onload = $xtra;
            break;
        case 'src/compose.php':
            $js = '<script language="JavaScript" type="text/javascript">' .
                 "\n<!--\n" .
                 "function checkForm() {\n".
                    "var f = document.forms.length;\n".
                    "var i = 0;\n".
                    "var pos = -1;\n".
                    "while( pos == -1 && i < f ) {\n".
                        "var e = document.forms[i].elements.length;\n".
                        "var j = 0;\n".
                        "while( pos == -1 && j < e ) {\n".
                            "if ( document.forms[i].elements[j].type == 'text' ) {\n".
                                "pos = j;\n".
                            "}\n".
                            "j++;\n".
                        "}\n".
                    "i++;\n".
                    "}\n".
                    "if( pos >= 0 ) {\n".
                        "document.forms[i-1].elements[pos].focus();\n".
                    "}\n".
                "}\n";
	    
            $js .= "// -->\n".
            	 "</script>\n";
            $onload = 'onload="checkForm();"';
            displayHtmlHeader ('SquirrelMail', $js);
            break;   

        default:
            $js = '<script language="JavaScript" type="text/javascript">' .
                 "\n<!--\n" .
                 "function checkForm() {\n".
                    "var f = document.forms.length;\n".
                    "var i = 0;\n".
                    "var pos = -1;\n".
                    "while( pos == -1 && i < f ) {\n".
                        "var e = document.forms[i].elements.length;\n".
                        "var j = 0;\n".
                        "while( pos == -1 && j < e ) {\n".
                            "if ( document.forms[i].elements[j].type == 'text' " .
                            "|| document.forms[i].elements[j].type == 'password' ) {\n".
                                "pos = j;\n".
                            "}\n".
                            "j++;\n".
                        "}\n".
                    "i++;\n".
                    "}\n".
                    "if( pos >= 0 ) {\n".
                        "document.forms[i-1].elements[pos].focus();\n".
                    "}\n".
	    	"$xtra\n".
                "}\n";
	    
                if ($compose_new_win == '1') {
                    if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
                        $compose_width = '640';
                    }
                    if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
                        $compose_height = '550';
                    }
                    $js .= "function comp_in_new(comp_uri) {\n".
    		     "       if (!comp_uri) {\n".
		         '           comp_uri = "'.$compose_uri."\";\n".
	    	     '       }'. "\n".
                         '    var newwin = window.open(comp_uri' .
                         ', "_blank",'.
                         '"width='.$compose_width. ',height='.$compose_height.
                         ',scrollbars=yes,resizable=yes");'."\n".
                         "}\n\n";

                }
            $js .= "// -->\n". "</script>\n";
	
            $onload = 'onload="checkForm();"';
            displayHtmlHeader ('SquirrelMail', $js);
            break;   

        }
    } else {
        /* do not use JavaScript */
        displayHtmlHeader ('SquirrelMail');
        $onload = '';
    }

    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" $onload>\n\n";
    /** Here is the header and wrapping table **/
    $shortBoxName = imap_utf7_decode_local(
		      readShortMailboxName($mailbox, $delimiter));
    if ( $shortBoxName == 'INBOX' ) {
        $shortBoxName = _("INBOX");
    }
    echo "<a name=\"pagetop\"></a>\n"
        . html_tag( 'table', '', '', $color[4], 'border="0" width="100%" cellspacing="0" cellpadding="2"' ) ."\n"
        . html_tag( 'tr', '', '', $color[9] ) ."\n"
        . html_tag( 'td', '', 'left' ) ."\n";
    if ( $shortBoxName <> '' && strtolower( $shortBoxName ) <> 'none' ) {
        echo '         ' . _("Current Folder") . ": <b>$shortBoxName&nbsp;</b>\n";
    } else {
        echo '&nbsp;';
    }
    echo  "      </td>\n"
        . html_tag( 'td', '', 'right' ) ."<b>\n";
    displayInternalLink ('src/signout.php', _("Sign Out"), $frame_top);
    echo "</b></td>\n"
        . "   </tr>\n"
        . html_tag( 'tr', '', '', $color[4] ) ."\n"
        . html_tag( 'td', '', 'left' ) ."\n";
    $urlMailbox = urlencode($mailbox);
    echo makeComposeLink('src/compose.php?mailbox='.$urlMailbox);
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ('src/addressbook.php', _("Addresses"), 'right');
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ('src/folders.php', _("Folders"), 'right');
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ('src/options.php', _("Options"), 'right');
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ("src/search.php?mailbox=$urlMailbox", _("Search"), 'right');
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ('src/help.php', _("Help"), 'right');
    echo "&nbsp;&nbsp;\n";

    do_hook('menuline');

    echo "      </td>\n"
        . html_tag( 'td', '', 'right' ) ."\n";
    if (!isset($provider_uri)) $provider_uri= 'http://www.squirrelmail.org/';
    if (!isset($provider_name)) $provider_name= 'SquirrelMail';
    echo ($hide_sm_attributions ? '&nbsp;' :
            '<a href="'.$provider_uri.'" target="_blank">'.$provider_name.'</a>');
    echo "</td>\n".
        "   </tr>\n".
        "</table><br>\n\n";
}

/* blatently copied/truncated/modified from the above function */
function compose_Header($color, $mailbox) {

    global $javascript_on;

    /*
     * Locate the first displayable form element (only when JavaScript on)
     */
    if($javascript_on) {
        global $delimiter, $base_uri, $PHP_SELF, $data_dir, $username;

        $module = substr( $PHP_SELF, ( strlen( $PHP_SELF ) - strlen( $base_uri ) ) * -1 );

        switch ( $module ) {
        case 'src/search.php':
            $pos = getPref($data_dir, $username, 'search_pos', 0 ) - 1;
            $onload = "onload=\"document.forms[$pos].elements[2].focus();\"";
            displayHtmlHeader (_("Compose"));
            break;
        default:
            $js = '<script language="JavaScript" type="text/javascript">' .
                 "\n<!--\n" .
                 "function checkForm() {\n".
                    "var f = document.forms.length;\n".
                    "var i = 0;\n".
                    "var pos = -1;\n".
                    "while( pos == -1 && i < f ) {\n".
                        "var e = document.forms[i].elements.length;\n".
                        "var j = 0;\n".
                        "while( pos == -1 && j < e ) {\n".
                            "if ( document.forms[i].elements[j].type == 'text' ) {\n".
                                "pos = j;\n".
                            "}\n".
                            "j++;\n".
                        "}\n".
                    "i++;\n".
                    "}\n".
                    "if( pos >= 0 ) {\n".
                        "document.forms[i-1].elements[pos].focus();\n".
                    "}\n".
                "}\n";
            $js .= "// -->\n".
            	 "</script>\n";
            $onload = 'onload="checkForm();"';
            displayHtmlHeader (_("Compose"), $js);
            break;   
        }
    } else {
        /* javascript off */
        displayHtmlHeader(_("Compose"));
        $onload = '';
    }

    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" $onload>\n\n";
}

?>
