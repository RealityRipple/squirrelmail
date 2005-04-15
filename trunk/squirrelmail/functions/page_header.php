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
    echo "\n" . html_tag( 'html' ,'' , '', '', 'lang="'.$squirrelmail_language.'"' ) .
        "<head>\n<meta name=\"robots\" content=\"noindex,nofollow\">\n";

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

    echo "<title>$title</title>\n$xtra\n";

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
 * @param string sHeaderJs javascipt code to be inserted in a script block in the header
 * @param string sBodyTagJs js events to be inserted in the body tag
 * @return void
 */

function displayPageHeader($color, $mailbox, $sHeaderJs='', $sBodyTagJs = 'onload="checkForm();"') {
    global $hide_sm_attributions, $frame_top,
           $provider_name, $provider_uri, $startMessage,
           $javascript_on;

    sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION );

    if (!isset($frame_top)) {
        $frame_top = '_top';
    }

    if( $javascript_on || strpos($sHeaderJs, 'new_js_autodetect_results.value') ) {
        if ($sHeaderJs) {
            $sJsBlock = "\n<script language=\"JavaScript\" type=\"text/javascript\">" .
                        "\n<!--\n" .
                        $sJsHeader . "\n\n// -->\n</script>\n";
        } else {
           $sJsBlock = '';
        }
        $sJsBlock .= "\n" . '<script src="'. SM_PATH .'templates/default/js/default.js" type="text/javascript" language="JavaScript"></script>' ."\n";
        displayHtmlHeader ('SquirrelMail', $sJsBlock);
   } else {
        /* do not use JavaScript */
        displayHtmlHeader ('SquirrelMail');
        $sBodyTagJs = '';
    }

    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" $sBodyTagJs>\n\n";

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
 * @param string sHeaderJs javascipt code to be inserted in a script block in the header
 * @param string sBodyTagJs js events to be inserted in the body tag
 * @return void
 */
function compose_Header($color, $mailbox, $sHeaderJs='', $sBodyTagJs = 'onload="checkForm();"') {
    global $javascript_on;
    /*
     * Locate the first displayable form element (only when JavaScript on)
     */
    if($javascript_on) {
        if ($sHeaderJs) {
            $sJsBlock = "\n<script language=\"JavaScript\" type=\"text/javascript\">" .
                        "\n<!--\n" .
                        $sJsHeader . "\n\n// -->\n</script>\n";
        } else {
           $sJsBlock = '';
        }
        $sJsBlock .= "\n" . '<script src="'. SM_PATH .'templates/default/js/default.js" type="text/javascript" language="JavaScript"></script>' ."\n";
        displayHtmlHeader (_("Compose"), $sJsBlock);
    } else {
        /* javascript off */
        displayHtmlHeader(_("Compose"));
        $onload = '';
    }
    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" $sBodyTagJs>\n\n";
}
?>
