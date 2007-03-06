<?php

/**
 * page_header.php
 *
 * Prints the page header (duh)
 *
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** Include required files from SM */
include_once(SM_PATH . 'functions/imap_mailbox.php');

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
    global $squirrelmail_language, $sTemplateID, $oErrorHandler, $oTemplate;

    if ( !sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION) ) {
        global $base_uri;
    }
    global $custom_css, $pageheader_sent, $theme, $theme_default, $text_direction,
        $default_fontset, $chosen_fontset, $default_fontsize, $chosen_fontsize, 
        $chosen_theme, $chosen_theme_path, $user_themes, $user_theme_default;

    /* add no cache headers here */
//FIXME: should change all header() calls in SM core to use $oTemplate->header()!!
    $oTemplate->header('Pragma: no-cache'); // http 1.0 (rfc1945)
    $oTemplate->header('Cache-Control: private, no-cache, no-store'); // http 1.1 (rfc2616)

    $oTemplate->assign('frames', $frames);
    $oTemplate->assign('lang', $squirrelmail_language);

    $header_tags = '';

    $header_tags .= "<meta name=\"robots\" content=\"noindex,nofollow\">\n";

    $used_fontset = (!empty($chosen_fontset) ? $chosen_fontset : $default_fontset);
    $used_fontsize = (!empty($chosen_fontsize) ? $chosen_fontsize : $default_fontsize);
    $used_theme = !isset($chosen_theme) && $user_theme_default != 'none' && is_dir($chosen_theme) && is_readable($chosen_theme)?  $user_themes[$user_theme_default]['PATH'].'/default.css' : $chosen_theme_path;
    
    /**
     * Stylesheets are loaded in the following order:
     *    1) All stylesheets provided by the template.  Normally, these are
     *       stylsheets in templates/<template>/css/.  This is accomplished by calling
     *       $oTemplate->fetch_standard_stylesheet_links().
     *    2) An optional user-defined stylesheet.  This is set in the Display
     *       Preferences.
     *    3) src/style.php which sets some basic font prefs.
     *    4) If we are dealing with an RTL language, we load rtl.css from the
     *       template set.
     */

    // 1. Stylesheets from the template.
    $header_tags .= $oTemplate->fetch_standard_stylesheet_links();

    $aUserStyles = array();

    // 2. Option user-defined stylesheet from preferences.
    if (!empty($used_theme)) {
        /**
         * All styles just point to a directory, so we need to include all .css
         * files in that directory. 
         */
        $styles = list_files($used_theme, '.css');
        foreach ($styles as $sheet) { 
            $aUserStyles[] = $used_theme .'/'.$sheet;
        }
    }

    // 3. src/style.php
    $aUserStyles[] = $base_uri .'src/style.php?'
                   . (!empty($used_fontset) ? '&amp;fontset='.$used_fontset : '')
                   . (!empty($used_fontsize) ? '&amp;fontsize='.$used_fontsize : '');

    // 3.1.  Load the stylesheets we have already  
    $header_tags .= $oTemplate->fetch_external_stylesheet_links($aUserStyles);

    // 4. Optional rtl.css stylesheet
    if ($text_direction == 'rtl') {
        $header_tags .= $oTemplate->fetch_right_to_left_stylesheet_link();
    }

    if ($squirrelmail_language == 'ja_JP') {
        /*
         * force correct detection of charset, when browser does not follow
         * http content-type and tries to detect charset from page content.
         * Shooting of browser's creator can't be implemented in php.
         * We might get rid of it, if we follow http://www.w3.org/TR/japanese-xml/
         * recommendations and switch to unicode.
         */
        $header_tags .= "<!-- \xfd\xfe -->\n";
        $header_tags .= '<meta http-equiv="Content-type" content="text/html; charset=euc-jp">' . "\n";
    }
    if ($do_hook) {
        // NOTE! plugins here MUST assign output to template 
        //       and NOT echo anything directly!!  A common
        //       approach is if a plugin decides it needs to
        //       put something at page-top after the standard
        //       SM page header, to dynamically add itself to
        //       the page_header_bottom and/or compose_header_bottom
        //       hooks for the current page request.  See 
        //       the Sent Confirmation v1.7 or Restrict Senders v1.2
        //       plugins for examples of this approach.
        global $null;
        ob_start();
        do_hook('generic_header', $null);
        $output = ob_get_contents();
        ob_end_clean();
        // plugin authors can debug their errors with one of the following:
        //sm_print_r($output);
        //echo $output;
        if (!empty($output)) trigger_error('A plugin on the "generic_header" hook has attempted to output directly to the browser', E_USER_ERROR);
    }

    $header_tags .= $xtra;
    $oTemplate->assign('page_title', $title);

    /* work around IE6's scrollbar bug */
    $header_tags .= <<<EOS
<!--[if IE 6]>
<style type="text/css">
/* avoid stupid IE6 bug with frames and scrollbars */
body {
    width: expression(document.documentElement.clientWidth - 30);
}
</style>
<![endif]-->

EOS;

    $oTemplate->assign('header_tags', $header_tags);
    $oTemplate->display('protocol_header.tpl');

    /* this is used to check elsewhere whether we should call this function */
    $pageheader_sent = TRUE;
    if (isset($oErrorHandler)) {
        $oErrorHandler->HeaderSent();
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
    global $base_uri, $oTemplate;
//    sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION);

    // This is an inefficient hook and is only used by
    // one plugin that still needs to patch this code,
    // plus if we are templat-izing SM, visual hooks
    // are not needed.  However, I am leaving the code
    // here just in case we find a good (non-visual?)
    // use for the internal_link hook.
    //
    //do_hook('internal_link', $text);

    return create_hyperlink($base_uri . $path, $text, $target);
}

/**
 * Same as makeInternalLink, but echoes it too
 */
function displayInternalLink($path, $text, $target='') {
// FIXME: should let the template echo all these kinds of things
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

function displayPageHeader($color, $mailbox='', $sHeaderJs='', $sBodyTagJs = '') {

    global $reply_focus, $hide_sm_attributions, $frame_top,
        $provider_name, $provider_uri, $startMessage,
        $action, $oTemplate, $org_title, $base_uri;

//FIXME: $sBodyTag should be turned into $sOnload and should only contain the contents of the onload attribute (not the attribute name nor any quotes).... only question is if anyone was using $sBodyTag for anything but onload event handlers? (see function compose_Header() below for how to fix it once we confirm it can be changed)
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

    sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION );

    if (!isset($frame_top)) {
        $frame_top = '_top';
    }

//FIXME: does checkForJavascript() make the 2nd part of the if() below unneccessary??
    if( checkForJavascript() || strpos($sHeaderJs, 'new_js_autodetect_results.value') ) {
        $js_includes = $oTemplate->get_javascript_includes(TRUE);
        $sJsBlock = '';
        foreach ($js_includes as $js_file) {
            $sJsBlock .= '<script src="'.$js_file.'" type="text/javascript"></script>' ."\n";
        }
        if ($sHeaderJs) {
            $sJsBlock .= "\n<script type=\"text/javascript\">" .
                        "\n<!--\n" .
                        $sHeaderJs . "\n\n// -->\n</script>\n";
        }
        displayHtmlHeader ($org_title, $sJsBlock);
    } else {
        /* do not use JavaScript */
        displayHtmlHeader ($org_title);
        $sBodyTagJs = '';
    }
    /*
     * this explains the imap_mailbox.php dependency. We should instead store
     * the selected mailbox in the session and fallback to the session var.
     */
    $shortBoxName = htmlspecialchars(imap_utf7_decode_local(
                readShortMailboxName($mailbox, $delimiter)));
    if ( $shortBoxName == 'INBOX' ) {
        $shortBoxName = _("INBOX");
    }

    $provider_link = '';
    if (!empty($provider_uri) && !empty($provider_name) && $provider_name != 'SquirrelMail') {
        $provider_link = create_hyperlink($provider_uri, $provider_name, '_blank');
    }

    $oTemplate->assign('body_tag_js', $sBodyTagJs);
    $oTemplate->assign('shortBoxName', $shortBoxName);
    $oTemplate->assign('provider_link', $provider_link);
    $oTemplate->assign('frame_top', $frame_top);
    $oTemplate->assign('urlMailbox', $urlMailbox);
    $oTemplate->assign('startMessage', $startMessage);
    $oTemplate->assign('hide_sm_attributions', $hide_sm_attributions);
    $oTemplate->display('page_header.tpl');

    global $null;
    do_hook('page_header_bottom', $null);
}

/**
 * Blatantly copied/truncated/modified from displayPageHeader.
 * Outputs a page header specifically for the compose_in_new popup window
 *
 * @param array color the array of theme colors
 * @param string mailbox the current mailbox name to display
 * @param string sHeaderJs javascipt code to be inserted in a script block in the header
 * @param string sOnload JavaScript code to be added inside the body's onload handler
 *                       as of 1.5.2, this replaces $sBodyTagJs argument
 * @return void
 */
function compose_Header($color, $mailbox, $sHeaderJs='', $sOnload = '') {

    global $reply_focus, $action, $oTemplate;

    if (empty($sOnload)) {
        if (strpos($action, 'reply') !== FALSE && $reply_focus) {
            if ($reply_focus == 'select')
                $sOnload = 'checkForm(\'select\');';
            else if ($reply_focus == 'focus')
                $sOnload = 'checkForm(\'focus\');';
            else if ($reply_focus != 'none')
                $sOnload = 'checkForm();';
        }
        else
        $sOnload = 'checkForm();';
    }


    /*
     * Locate the first displayable form element (only when JavaScript on)
     */
    if(checkForJavascript()) {
        if ($sHeaderJs) {
            $sJsBlock = "\n<script type=\"text/javascript\">" .
                        "\n<!--\n" .
                        $sHeaderJs . "\n\n// -->\n</script>\n";
        } else {
        $sJsBlock = '';
        }
        $sJsBlock .= "\n";

        $js_includes = $oTemplate->get_javascript_includes(TRUE);
        foreach ($js_includes as $js_file) {
            $sJsBlock .= '<script src="'.$js_file.'" type="text/javascript"></script>' ."\n";
        }

        displayHtmlHeader (_("Compose"), $sJsBlock);
    } else {
        /* javascript off */
        displayHtmlHeader(_("Compose"));
        $onload = '';
    }

// FIXME: change the colorization attributes below to a CSS class!
    $class = '';
    $aAttribs = array('text' => $color[8], 'bgcolor' => $color[4],
                      'link' => $color[7], 'vlink' => $color[7],
                      'alink' => $color[7]);

    // this is template-safe (see create_body() function)
    echo create_body($sOnload, $class, $aAttribs);

    global $null;
    do_hook('compose_header_bottom', $null);
}
