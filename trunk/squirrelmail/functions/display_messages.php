<?php

/**
 * display_messages.php
 *
 * This contains all messages, including information, error, and just
 * about any other message you can think of.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** @ignore */
if (! defined('SM_PATH')) define('SM_PATH','../');

/**
 * including plugin functions
 */
include_once(SM_PATH . 'functions/plugin.php');

/**
 * Displays error message and URL to message listing
 * @param string $message error message
 * @param string $mailbox mailbox name
 * @param integer $sort sort order
 * @param integer $startMessage first message
 * @param array $color color theme
 * @since 1.0
 */
function error_message($message, $mailbox, $sort, $startMessage, $color) {
    $urlMailbox = urlencode($mailbox);
    $string = '<tr><td align="center">' . $message . '</td></tr>'.
              '<tr><td align="center">'.
              '<a href="'.sqm_baseuri()."src/right_main.php?sort=$sort&amp;startMessage=$startMessage&amp;mailbox=$urlMailbox\">".
              sprintf (_("Click here to return to %s"),
                  strtoupper($mailbox) == 'INBOX' ? _("INBOX") : imap_utf7_decode_local($mailbox)).
              '</a></td></tr>';
    error_box($string, $color);
}

/**
 * Displays error message
 * @param string $message error message
 * @param array $color color theme
 * @since 1.0
 */
function plain_error_message($message, $color) {
    error_box($message, $color);
}

/**
 * Displays error when user is logged out
 * 
 * Error strings can be overriden by logout_error hook
 * @param string $errString error message
 * @param string $errTitle title of page with error message
 * @since 1.2.6
 */
function logout_error( $errString, $errTitle = '' ) {
    global $frame_top, $org_logo, $org_name, $org_logo_width, $org_logo_height,
           $hide_sm_attributions, $version, $squirrelmail_language, 
           $color, $theme, $theme_default;

    $base_uri = sqm_baseuri();

    include_once( SM_PATH . 'functions/page_header.php' );
    if ( !isset( $org_logo ) ) {
        // Don't know yet why, but in some accesses $org_logo is not set.
        include( SM_PATH . 'config/config.php' );
    }
    /* Display width and height like good little people */
    $width_and_height = '';
    if (isset($org_logo_width) && is_numeric($org_logo_width) && $org_logo_width>0) {
        $width_and_height = " width=\"$org_logo_width\"";
    }
    if (isset($org_logo_height) && is_numeric($org_logo_height) && $org_logo_height>0) {
        $width_and_height .= " height=\"$org_logo_height\"";
    }

    if (!isset($frame_top) || $frame_top == '' ) {
        $frame_top = '_top';
    }

    // load default theme if possible
    if (!isset($color) && @file_exists($theme[$theme_default]['PATH']))
        @include ($theme[$theme_default]['PATH']);

    if ( !isset( $color ) ) {
        $color = array();
        $color[0]  = '#dcdcdc';  /* light gray    TitleBar               */
        $color[1]  = '#800000';  /* red                                  */
        $color[2]  = '#cc0000';  /* light red     Warning/Error Messages */
        $color[4]  = '#ffffff';  /* white         Normal Background      */
        $color[7]  = '#0000cc';  /* blue          Links                  */
        $color[8]  = '#000000';  /* black         Normal text            */
    }

    list($junk, $errString, $errTitle) = do_hook('logout_error', $errString, $errTitle);

    if ( $errTitle == '' ) {
        $errTitle = $errString;
    }
    set_up_language($squirrelmail_language, true);

    displayHtmlHeader( $org_name.' - '.$errTitle, '', false );

    echo '<body text="'.$color[8].'" bgcolor="'.$color[4].'" link="'.$color[7].'" vlink="'.$color[7].'" alink="'.$color[7]."\">\n\n".
         '<div style="text-align: center;">';

    if (isset($org_logo) && ($org_logo != '')) {
        echo '<img src="'.$org_logo.'" alt="'.sprintf(_("%s Logo"), $org_name).
             "\"$width_and_height /><br />\n";
    }
    echo ( $hide_sm_attributions ? '' :
            '<small>' .  _("SquirrelMail Webmail Application") . '<br />'.
            _("By the SquirrelMail Project Team") . "<br /></small>\n" ).
         '<table cellspacing="1" cellpadding="0" bgcolor="'.$color[1].'" width="70%" align="center">'.
         '<tr><td>'.
         '<table width="100%" border="0" bgcolor="'.$color[4].'" align="center">'.
         '<tr><td bgcolor="'.$color[0].'" align="center">'.
         '<font color="'.$color[2].'"><b>' . _("ERROR") . '</b></font>'.
         '</td></tr>'.
         '<tr><td align="center">' . $errString . '</td></tr>'.
         '<tr><td bgcolor="'.$color[0].'" align="center">'.
         '<font color="'.$color[2].'"><b>'.
         '<a href="'.$base_uri.'src/login.php" target="'.$frame_top.'">'.
         _("Go to the login page") . '</a></b></font></td></tr>'.
         '</table></td></tr></table></div></body></html>';
}

/**
 * Displays error message
 * 
 * Since 1.4.1 function checks if page header is already displayed.
 * Since 1.4.3 and 1.5.1 function contains error_box hook.
 * Use plain_error_message() and make sure that page header is created,
 * if you want compatibility with 1.4.0 and older.
 * @param string $string
 * @param array $color
 * @since 1.3.2
 */
function error_box($string, $color) {
    global $pageheader_sent, $oTemplate;

    $err = _("ERROR");
    $ret = concat_hook_function('error_box', $string);
    if($ret != '') {
        $string = $ret;
    }

    /* check if the page header has been sent; if not, send it! */
    if(!isset($pageheader_sent) && !$pageheader_sent) {
        /* include this just to be sure */
        include_once( SM_PATH . 'functions/page_header.php' );
        displayHtmlHeader('SquirrelMail: '.$err);
        $pageheader_sent = TRUE;
        echo "<body>\n\n";
    }

    /** ERROR is pre-translated to avoid multiple translation calls. **/
    $oTemplate->assign('error', $err);
    $oTemplate->assign('errorMessage', $string);
    $oTemplate->display('error_box.tpl');
}

/**
 * Adds message that informs about non fatal error that can happen while saving preferences
 * @param string $message error message
 * @since 1.5.1 and 1.4.5
 */
function error_option_save($message) {
    global $optpage_save_error;

    if (! is_array($optpage_save_error) )
        $optpage_save_error=array();

    $optpage_save_error=array_merge($optpage_save_error,array($message));
}
?>
