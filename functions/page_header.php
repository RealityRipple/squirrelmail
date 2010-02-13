<?php

/**
 * page_header.php
 *
 * Prints the page header (duh)
 *
 * @copyright 1999-2010 The SquirrelMail Project Team
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
    /* prevent information leakage about read emails by forbidding Firefox
     * to do preemptive DNS requests for any links in the message body. */
    $oTemplate->header('X-DNS-Prefetch-Control: off');

    // don't show version as a security measure
    //$oTemplate->header('X-Powered-By: SquirrelMail/' . SM_VERSION, FALSE);
    $oTemplate->header('X-Powered-By: SquirrelMail', FALSE);

    $oTemplate->assign('frames', $frames);
    $oTemplate->assign('lang', $squirrelmail_language);

    $header_tags = '';

    $header_tags .= "<meta name=\"robots\" content=\"noindex,nofollow\" />\n";

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
    if (!empty($used_theme) && $used_theme != 'none') {
        /**
         * All styles (except "none" - ugh) just point to a directory,
         * so we need to include all .css files in that directory.
         */
//FIXME: rid ourselves of "none" strings!  I didn't do it here because I think the problem is that the theme itself should never be "none" (? well, what else would it be?  if "none" theme is actually OK, then is there a constant to use below in stead of a hard-coded string?)
        $styles = $used_theme == 'none' ? array()
                : list_files($used_theme, '.css');
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

    // 5. Printer friendly stylesheet
    $header_tags .= create_css_link($base_uri . 'css/print.css', 'printerfriendly', false, 'print');

    if ($squirrelmail_language == 'ja_JP') {
        /*
         * force correct detection of charset, when browser does not follow
         * http content-type and tries to detect charset from page content.
         * Shooting of browser's creator can't be implemented in php.
         * We might get rid of it, if we follow http://www.w3.org/TR/japanese-xml/
         * recommendations and switch to unicode.
         */
        $header_tags .= "<!-- \xfd\xfe -->\n";
        $header_tags .= '<meta http-equiv="Content-type" content="' . $oTemplate->get_content_type() . '; charset=euc-jp" />' . "\n";
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
        ob_start();
        $temp = array(&$header_tags);
        do_hook('generic_header', $temp);
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
 * @param string $path      The SquirrelMail file to link to
 *                          (should start with something like "src/..." or
 *                          "functions/..." or "plugins/..." etc.)
 * @param string $text      The link text
 * @param string $target    The target frame for this link
 * @param string $accesskey The access key to be used, if any
 */
function makeInternalLink($path, $text, $target='', $accesskey='NONE') {
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

    return create_hyperlink($base_uri . $path, $text, $target,
                            '', '', '', '',
                            ($accesskey == 'NONE'
                            ? array()
                            : array('accesskey' => $accesskey)));
}

/**
 * Outputs a complete SquirrelMail page header, starting with <!doctype> and
 * including the default menu bar. Uses displayHtmlHeader and takes
 * JavaScript and locale settings into account.
 *
 * @param array color the array of theme colors
 * @param string mailbox the current mailbox name to display
 * @param string sHeaderJs javascipt code to be inserted in a script block in the header
 * @param string sOnload JavaScript code to be added inside the body's onload handler
 *                       as of 1.5.2, this replaces $sBodyTagJs argument
 * @return void
 */
function displayPageHeader($color, $mailbox='', $sHeaderJs='', $sOnload = '') {

    global $reply_focus, $hide_sm_attributions, $frame_top,
        $provider_name, $provider_uri, $startMessage,
        $action, $oTemplate, $org_title, $base_uri,
        $data_dir, $username;

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

    $startMessage = (int)$startMessage;

    sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION );

    if (!isset($frame_top)) {
        $frame_top = '_top';
    }

//FIXME: does checkForJavascript() make the 2nd part of the if() below unneccessary?? (that is, I think checkForJavascript() might already look for new_js_autodetect_results...(?))
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
        $sOnload = '';
    }
    if ($mailbox) {
        /*
        * this explains the imap_mailbox.php dependency. We should instead store
        * the selected mailbox in the session and fallback to the session var.
        */
        $shortBoxName = htmlspecialchars(imap_utf7_decode_local(
                    readShortMailboxName($mailbox, $delimiter)));
        if (getPref($data_dir, $username, 'translate_special_folders')) {
            global $sent_folder, $trash_folder, $draft_folder;
            if ($mailbox == $sent_folder)
                $shortBoxName = _("Sent");
            else if ($mailbox == $trash_folder)
                $shortBoxName = _("Trash");
            else if ($mailbox == $sent_folder)
                $shortBoxName = _("Drafts");
        }
        $urlMailbox = urlencode($mailbox);
    } else {
        $shortBoxName = '';
        $urlMailbox = '';
    }

    $provider_link = '';
    if (!empty($provider_uri) && !empty($provider_name) && $provider_name != 'SquirrelMail') {
        $provider_link = create_hyperlink($provider_uri, $provider_name, '_blank');
    }

    $oTemplate->assign('onload', $sOnload);
    $oTemplate->assign('shortBoxName', $shortBoxName);
    $oTemplate->assign('provider_link', $provider_link);
    $oTemplate->assign('frame_top', $frame_top);
    $oTemplate->assign('urlMailbox', $urlMailbox);
    $oTemplate->assign('startMessage', $startMessage);
    $oTemplate->assign('hide_sm_attributions', $hide_sm_attributions);

    // access keys
    //
    global $accesskey_menubar_compose, $accesskey_menubar_addresses,
           $accesskey_menubar_folders, $accesskey_menubar_options,
           $accesskey_menubar_search, $accesskey_menubar_help,
           $accesskey_menubar_signout;
    $oTemplate->assign('accesskey_menubar_compose', $accesskey_menubar_compose);
    $oTemplate->assign('accesskey_menubar_addresses', $accesskey_menubar_addresses);
    $oTemplate->assign('accesskey_menubar_folders', $accesskey_menubar_folders);
    $oTemplate->assign('accesskey_menubar_options', $accesskey_menubar_options);
    $oTemplate->assign('accesskey_menubar_search', $accesskey_menubar_search);
    $oTemplate->assign('accesskey_menubar_help', $accesskey_menubar_help);
    $oTemplate->assign('accesskey_menubar_signout', $accesskey_menubar_signout);

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
        $sOnload = '';
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
