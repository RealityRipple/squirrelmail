<?php

/**
 * page_header.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Prints the page header (duh)
 *
 * @version $Id$
 * @package squirrelmail
 */

/** Include required files from SM */
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/imap_mailbox.php');
require_once(SM_PATH . 'functions/global.php');

/**
 * Output a SquirrelMail page header, from <!doctype> to </head>
 * Always set up the language before calling these functions.
 *
 * @param string title the page title, default SquirrelMail.
 * @param string xtra extra HTML to insert into the header
 * @param bool do_hook whether to execute hooks, default true
 * @param bool frames generate html frameset doctype (since 1.5.1)
 * @return void
 */
function displayHtmlHeader( $title = 'SquirrelMail', $xtra = '', $do_hook = true, $frames = false ) {
    global $squirrelmail_language;

    if ( !sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION) ) {
        global $base_uri;
    }
    global $theme_css, $custom_css, $pageheader_sent;

    if ($frames) {
        echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">';
    } else {
        echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">';
    }
    echo "\n\n" . html_tag( 'html' ,'' , '', '', 'lang="'.$squirrelmail_language.'"' ) . "\n<head>\n";

    /*
     * Add closing / to link and meta elements only after switching to xhtml 1.0 Transitional.
     * It is not compatible with html 4.01 Transitional
     */
    if ( !isset( $custom_css ) || $custom_css == 'none' ) {
        if ($theme_css != '') {
            echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$theme_css\">";
        }
    } else {
        echo '<link rel="stylesheet" type="text/css" href="' .
             $base_uri . 'themes/css/'.$custom_css.'">';
    }

    if ($squirrelmail_language == 'ja_JP') {
        /*
         * force correct detection of charset, when browser does not follow
         * http content-type and tries to detect charset from page content.
         * Shooting of browser's creator can't be implemented in php.
         * We might get rid of it, if we follow http://www.w3.org/TR/japanese-xml/
         * recommendations and switch to unicode.
         */
        echo "<!-- \xfd\xfe -->\n";
        echo '<meta http-equiv="Content-type" content="text/html; charset=euc-jp">' . "\n";
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

/**
 * Given a path to a SquirrelMail file, return a HTML link to it
 *
 * @param string path the SquirrelMail file to link to
 * @param string text the link text
 * @param string target the target frame for this link
 */
function makeInternalLink($path, $text, $target='') {
    sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION);
    if ($target != '') {
        $target = " target=\"$target\"";
    }

    // This is an inefficient hook and is only used by
    // one plugin that still needs to patch this code,
    // plus if we are templat-izing SM, visual hooks
    // are not needed.  However, I am leaving the code
    // here just in case we find a good (non-visual?)
    // use for the internal_link hook.
    //
    //$hooktext = do_hook_function('internal_link',$text);
    //if ($hooktext != '')
    //    $text = $hooktext;

    return '<a href="'.$base_uri.$path.'"'.$target.'>'.$text.'</a>';
}

/**
 * Same as makeInternalLink, but echoes it too
 */
function displayInternalLink($path, $text, $target='') {
    echo makeInternalLink($path, $text, $target);
}

/**
 * Outputs a complete SquirrelMail page header, starting with <!doctype> and
 * including the default menu bar. Uses displayHtmlHeader and takes
 * JavaScript and locale settings into account.
 *
 * @param array color the array of theme colors
 * @param string mailbox the current mailbox name to display
 * @param string xtra extra html code to add
 * @param bool session
 * @return void
 */
function displayPageHeader($color, $mailbox, $xtra='', $session=false) {

    global $hide_sm_attributions, $PHP_SELF, $frame_top,
           $compose_new_win, $compose_width, $compose_height,
           $provider_name, $provider_uri, $startMessage,
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

    if( $javascript_on || strpos($xtra, 'new_js_autodetect_results.value') ) {

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
                    $js .= "function comp_in_new_form(comp_uri, button, myform) {\n".
                           '   if (!comp_uri) {'."\n".
                           '       comp_uri = "'.$compose_uri."\";\n".
                           '   }'. "\n".
                           '   comp_uri += "&" + button.name + "=1";'."\n".
                           '   for ( var i=0; i < myform.elements.length; i++ ) {'."\n".
                           '      if ( myform.elements[i].type == "checkbox"  && myform.elements[i].checked )'."\n".
                           '         comp_uri += "&" + myform.elements[i].name + "=1";'."\n".
                           '   }'."\n".
                           '   var newwin = window.open(comp_uri' .
                           ', "_blank",'.
                           '"width='.$compose_width. ',height='.$compose_height.
                           ',scrollbars=yes,resizable=yes,status=yes");'."\n".
                           "}\n\n";
                    $js .= "function comp_in_new(comp_uri) {\n".
                           "       if (!comp_uri) {\n".
                           '           comp_uri = "'.$compose_uri."\";\n".
                           '       }'. "\n".
                           '    var newwin = window.open(comp_uri' .
                           ', "_blank",'.
                           '"width='.$compose_width. ',height='.$compose_height.
                           ',scrollbars=yes,resizable=yes,status=yes");'."\n".
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
             "function checkForm() {\n";

            global $action, $reply_focus;
            if (strpos($action, 'reply') !== FALSE && $reply_focus)
            {
                if ($reply_focus == 'select') $js .= "document.forms['compose'].body.select();}\n";
                else if ($reply_focus == 'focus') $js .= "document.forms['compose'].body.focus();}\n";
                else if ($reply_focus == 'none') $js .= "}\n";
            }
            // no reply focus also applies to composing new messages
            else if ($reply_focus == 'none')
            {
                $js .= "}\n";
            }
            else
                $js .= "var f = document.forms.length;\n".
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

        case 'src/right_main.php':
// following code graciously stolen from phpMyAdmin project at:
// http://www.phpmyadmin.net
            $js = <<<EOS
/**
 * This array is used to remember mark status of rows in browse mode
 */
var marked_row = new Array;


/**
 * Sets/unsets the pointer and marker in browse mode
 *
 * @param   object    the table row
 * @param   integer  the row number
 * @param   string    the action calling this script (over, out or click)
 * @param   string    the default background color
 * @param   string    the color to use for mouseover
 * @param   string    the color to use for marking a row
 *
 * @return  boolean  whether pointer is set or not
 */
function setPointer(theRow, theRowNum, theAction, theDefaultColor, thePointerColor, theMarkColor)
{
    var theCells = null;

    // 1. Pointer and mark feature are disabled or the browser can't get the
    //    row -> exits
    if ((thePointerColor == '' && theMarkColor == '')
        || typeof(theRow.style) == 'undefined') {
        return false;
    }

    // 2. Gets the current row and exits if the browser can't get it
    if (typeof(document.getElementsByTagName) != 'undefined') {
        theCells = theRow.getElementsByTagName('td');
    }
    else if (typeof(theRow.cells) != 'undefined') {
        theCells = theRow.cells;
    }
    else {
        return false;
    }

    // 3. Gets the current color...
    var rowCellsCnt  = theCells.length;
    var domDetect    = null;
    var currentColor = null;
    var newColor     = null;
    // 3.1 ... with DOM compatible browsers except Opera that does not return
    //         valid values with "getAttribute"
    if (typeof(window.opera) == 'undefined'
        && typeof(theCells[0].getAttribute) != 'undefined') {
        currentColor = theCells[0].getAttribute('bgcolor');
        domDetect    = true;
    }
    // 3.2 ... with other browsers
    else {
        currentColor = theCells[0].style.backgroundColor;
        domDetect    = false;
    } // end 3

    // 3.3 ... Opera changes colors set via HTML to rgb(r,g,b) format so fix it
    if (currentColor.indexOf("rgb") >= 0)
    {
        var rgbStr = currentColor.slice(currentColor.indexOf('(') + 1,
                                     currentColor.indexOf(')'));
        var rgbValues = rgbStr.split(",");
        currentColor = "#";
        var hexChars = "0123456789ABCDEF";
        for (var i = 0; i < 3; i++)
        {
            var v = rgbValues[i].valueOf();
            currentColor += hexChars.charAt(v/16) + hexChars.charAt(v%16);
        }
    }

    // 4. Defines the new color
    // 4.1 Current color is the default one
    if (currentColor == ''
        || currentColor.toLowerCase() == theDefaultColor.toLowerCase()) {
        if (theAction == 'over' && thePointerColor != '') {
            newColor              = thePointerColor;
        }
        else if (theAction == 'click' && theMarkColor != '') {
            newColor              = theMarkColor;
            marked_row[theRowNum] = true;
            // deactivated onclick marking of the checkbox because it's also executed
            // when an action (clicking on the checkbox itself) on a single item is 
            // performed. Then the checkbox would get deactived, even though we need 
            // it activated. Maybe there is a way to detect if the row was clicked, 
            // and not an item therein...
            //document.getElementById('msg[' + theRowNum + ']').checked = true;
        }
    }
    // 4.1.2 Current color is the pointer one
    else if (currentColor.toLowerCase() == thePointerColor.toLowerCase()
             && (typeof(marked_row[theRowNum]) == 'undefined' || !marked_row[theRowNum])) {
        if (theAction == 'out') {
            newColor              = theDefaultColor;
        }
        else if (theAction == 'click' && theMarkColor != '') {
            newColor              = theMarkColor;
            marked_row[theRowNum] = true;
            //document.getElementById('msg[' + theRowNum + ']').checked = true;
        }
    }
    // 4.1.3 Current color is the marker one
    else if (currentColor.toLowerCase() == theMarkColor.toLowerCase()) {
        if (theAction == 'click') {
            newColor              = (thePointerColor != '')
                                  ? thePointerColor
                                  : theDefaultColor;
            marked_row[theRowNum] = (typeof(marked_row[theRowNum]) == 'undefined' || !marked_row[theRowNum])
                                  ? true
                                  : null;
            //document.getElementById('msg[' + theRowNum + ']').checked = false;
        }
    } // end 4

    // 5. Sets the new color...
    if (newColor) {
        var c = null;
        // 5.1 ... with DOM compatible browsers except Opera
        if (domDetect) {
            for (c = 0; c < rowCellsCnt; c++) {
                theCells[c].setAttribute('bgcolor', newColor, 0);
            } // end for
        }
        // 5.2 ... with other browsers
        else {
            for (c = 0; c < rowCellsCnt; c++) {
                theCells[c].style.backgroundColor = newColor;
            }
        }
    } // end 5

    return true;
} // end of the 'setPointer()' function
EOS;
            $js = "\n".'<script language="JavaScript" type="text/javascript">' .
                        "\n<!--\n" . $js;
            if ($compose_new_win == '1') {
                if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
                    $compose_width = '640';
                }
                if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
                    $compose_height = '550';
                }
                $js .= "\nfunction comp_in_new(comp_uri) {\n".
                     "       if (!comp_uri) {\n".
                     '           comp_uri = "'.$compose_uri."\";\n".
                     '       }'. "\n".
                     '    var newwin = window.open(comp_uri' .
                     ', "_blank",'.
                     '"width='.$compose_width. ',height='.$compose_height.
                     ',scrollbars=yes,resizable=yes,status=yes");'."\n".
                     "}\n\n";
            }
            $js .= "// -->\n</script>\n";
            $onload = '';
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
                         ',scrollbars=yes,resizable=yes,status=yes");'."\n".
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
    $shortBoxName = htmlspecialchars(imap_utf7_decode_local(
                readShortMailboxName($mailbox, $delimiter)));
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
        . ($hide_sm_attributions ? html_tag( 'td', '', 'left', '', 'colspan="2"' )
                                 : html_tag( 'td', '', 'left' ) )
        . "\n";
    $urlMailbox = urlencode($mailbox);
    echo makeComposeLink('src/compose.php?mailbox='.$urlMailbox.'&amp;startMessage='.$startMessage);
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ('src/addressbook.php', _("Addresses"));
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ('src/folders.php', _("Folders"));
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ('src/options.php', _("Options"));
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ("src/search.php?mailbox=$urlMailbox", _("Search"));
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ('src/help.php', _("Help"));
    echo "&nbsp;&nbsp;\n";

    do_hook('menuline');

    echo "      </td>\n";

    if (!$hide_sm_attributions)
    {
        echo html_tag( 'td', '', 'right' ) ."\n";
        if (empty($provider_uri)) {
            echo '<a href="about.php">SquirrelMail</a>';
        } else {
            if (empty($provider_name)) $provider_name= 'SquirrelMail';
            echo '<a href="'.$provider_uri.'" target="_blank">'.$provider_name.'</a>';
        }
        echo "</td>\n";
    }
    echo "   </tr>\n".
        "</table><br />\n\n";
}

/**
 * Blatantly copied/truncated/modified from displayPageHeader.
 * Outputs a page header specifically for the compose_in_new popup window
 *
 * @param array color the array of theme colors
 * @param string mailbox the current mailbox name to display
 * @return void
 */
function compose_Header($color, $mailbox) {

    global $javascript_on;

    /*
     * Locate the first displayable form element (only when JavaScript on)
     */
    if($javascript_on) {
        global $base_uri, $PHP_SELF, $data_dir, $username;

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
             "function checkForm() {\n";

            global $action, $reply_focus;
            if (strpos($action, 'reply') !== FALSE && $reply_focus)
            {
                if ($reply_focus == 'select') $js .= "document.forms['compose'].body.select();}\n";
                else if ($reply_focus == 'focus') $js .= "document.forms['compose'].body.focus();}\n";
                else if ($reply_focus == 'none') $js .= "}\n";
            }
            // no reply focus also applies to composing new messages
            else if ($reply_focus == 'none')
            {
                $js .= "}\n";
            }
            else
                $js .= "var f = document.forms.length;\n".
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
