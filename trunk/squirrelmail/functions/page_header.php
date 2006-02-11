<?php

/**
 * page_header.php
 *
 * Prints the page header (duh)
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** @ignore */
if (! defined('SM_PATH')) define('SM_PATH','../');

/** Include required files from SM */
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/imap_mailbox.php');
require_once(SM_PATH . 'functions/global.php');
include_once(SM_PATH . 'class/template/template.class.php');

/**
 * Output a SquirrelMail page header, from <!doctype> to </head>
 * Always set up the language before calling these functions.
 *
 * Since 1.5.1 function sends http headers. Function should be called
 * before any output is started.
 * @param string title the page title, default SquirrelMail.
 * @param string xtra extra HTML to insert into the header
 * @param bool do_hook whether to execute hooks, default true
 * @param bool frames generate html frameset doctype (since 1.5.1)
 * @return void
 */
function displayHtmlHeader( $title = 'SquirrelMail', $xtra = '', $do_hook = TRUE, $frames = FALSE ) {
    global $squirrelmail_language, $sTplDir, $oErroHandler;

    if ( !sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION) ) {
        global $base_uri;
    }
    global $theme_css, $custom_css, $pageheader_sent, $theme, $theme_default,
        $default_fontset, $chosen_fontset, $default_fontsize, $chosen_fontsize, $chosen_theme;

    /* add no cache headers here */
    header('Pragma: no-cache'); // http 1.0 (rfc1945)
    header('Cache-Control: private, no-cache, no-store'); // http 1.1 (rfc2616)

    if ($frames) {
        echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"'."\n"
            .' "http://www.w3.org/TR/1999/REC-html401-19991224/frameset.dtd">';
    } else {
        echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"'."\n"
            .' "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">';
    }
    echo "\n" . html_tag( 'html' ,'' , '', '', 'lang="'.$squirrelmail_language.'"' ) .
        "<head>\n<meta name=\"robots\" content=\"noindex,nofollow\">\n";

    $used_fontset = (!empty($chosen_fontset) ? $chosen_fontset : $default_fontset);
    $used_fontsize = (!empty($chosen_fontsize) ? $chosen_fontsize : $default_fontsize);
    $used_theme = basename((!empty($chosen_theme) ? $chosen_theme : $theme[$theme_default]['PATH']),'.php');

    /*
     * Add closing / to link and meta elements only after switching to xhtml 1.0 Transitional.
     * It is not compatible with html 4.01 Transitional
     */
    echo '<link rel="stylesheet" type="text/css" href="'. $base_uri .'src/style.php'
        .'?themeid='.$used_theme
        .'&amp;templateid='.basename($sTplDir)
        .(!empty($used_fontset) ? '&amp;fontset='.$used_fontset : '')
        .(!empty($used_fontsize) ? '&amp;fontsize='.$used_fontsize : '')."\">\n";


    // load custom style sheet (deprecated)
    if ( ! empty($theme_css) ) {
        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$theme_css\">\n";
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
    if (isset($oErrorHandler)) {
        $oErrorHander->HeaderSent();
    }
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

function displayPageHeader($color, $mailbox, $sHeaderJs='', $sBodyTagJs = '') {

    global $reply_focus, $hide_sm_attributions, $frame_top,
        $provider_name, $provider_uri, $startMessage,
        $javascript_on, $action, $oTemplate;

    if (empty($sBodyTagJs)) {
        if (strpos($action, 'reply') !== FALSE && $reply_focus) {
        if ($reply_focus == 'select')
            $sBodyTagJs = 'onload="checkForm(\'select\');"';
        else if ($reply_focus == 'focus')
            $sBodyTagJs = 'onload="checkForm(\'focus\');"';
        else if ($reply_focus != 'none')
            $sBodyTagJs = 'onload="checkForm();"';
        }
        else
        $sBodyTagJs = 'onload="checkForm();"';
    }

    $urlMailbox = urlencode($mailbox);
    $startMessage = (int)$startMessage;

    $sTplDir = $oTemplate->template_dir;

    sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION );

    if (!isset($frame_top)) {
        $frame_top = '_top';
    }

    if( $javascript_on || strpos($sHeaderJs, 'new_js_autodetect_results.value') ) {
        $sJsBlock = '<script src="'. $sTplDir. 'js/default.js" type="text/javascript"></script>' ."\n";
        if ($sHeaderJs) {
            $sJsBlock .= "\n<script type=\"text/javascript\">" .
                        "\n<!--\n" .
                        $sHeaderJs . "\n\n// -->\n</script>\n";
        }
        displayHtmlHeader ('SquirrelMail', $sJsBlock);
    } else {
        /* do not use JavaScript */
        displayHtmlHeader ('SquirrelMail');
        $sBodyTagJs = '';
    }

    $shortBoxName = htmlspecialchars(imap_utf7_decode_local(
                readShortMailboxName($mailbox, $delimiter)));
    if ( $shortBoxName == 'INBOX' ) {
        $shortBoxName = _("INBOX");
    }

    $sm_attributes = '';
    if (!$hide_sm_attributions) {
        $sm_attributes .= '<td class="sqm_providerInfo">' ."\n";
        if (empty($provider_uri)) {
            $sm_attributes .= '   <a href="about.php">SquirrelMail</a>';
        } else {
            if (empty($provider_name)) $provider_name= 'SquirrelMail';
            $sm_attributes .= '   <a href="'.$provider_uri.'" target="_blank">'.$provider_name.'</a>'."\n";
        }
        $sm_attributes .= "  </td>\n";
    }

    $oTemplate->assign('body_tag_js', $sBodyTagJs);
    $oTemplate->assign('shortBoxName', $shortBoxName);
    $oTemplate->assign('sm_attribute_str', $sm_attributes);
    $oTemplate->assign('frame_top', $frame_top);
    $oTemplate->assign('urlMailbox', $urlMailbox);
    $oTemplate->assign('startMessage', $startMessage);
    $oTemplate->assign('hide_sm_attributions', $hide_sm_attributions);
    $oTemplate->display('page_header.tpl');
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
function compose_Header($color, $mailbox, $sHeaderJs='', $sBodyTagJs = '') {

    global $reply_focus, $javascript_on, $action;

    if (empty($sBodyTagJs)) {
        if (strpos($action, 'reply') !== FALSE && $reply_focus) {
        if ($reply_focus == 'select')
            $sBodyTagJs = 'onload="checkForm(\'select\');"';
        else if ($reply_focus == 'focus')
            $sBodyTagJs = 'onload="checkForm(\'focus\');"';
        else if ($reply_focus != 'none')
            $sBodyTagJs = 'onload="checkForm();"';
        }
        else
        $sBodyTagJs = 'onload="checkForm();"';
    }


    /*
     * Locate the first displayable form element (only when JavaScript on)
     */
    if($javascript_on) {
        if ($sHeaderJs) {
            $sJsBlock = "\n<script type=\"text/javascript\">" .
                        "\n<!--\n" .
                        $sHeaderJs . "\n\n// -->\n</script>\n";
        } else {
        $sJsBlock = '';
        }
        $sJsBlock .= "\n" . '<script src="'. SM_PATH .'templates/default/js/default.js" type="text/javascript"></script>' ."\n";
        displayHtmlHeader (_("Compose"), $sJsBlock);
    } else {
        /* javascript off */
        displayHtmlHeader(_("Compose"));
        $onload = '';
    }
    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" $sBodyTagJs>\n\n";
}
?>