<?php

/**
 * display_messages.php
 *
 * This contains all messages, including information, error, and just
 * about any other message you can think of.
 *
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */


/**
 * Displays error message and URL to message listing
 *
 * Fifth argument ($color array) is removed in 1.5.2.
 * @param string $message error message
 * @param string $mailbox mailbox name
 * @param integer $sort sort order
 * @param integer $startMessage first message
 * @since 1.0
 */
function error_message($message, $mailbox, $sort, $startMessage) {
    $urlMailbox = urlencode($mailbox);
    $link = array (
        'URL'   => sqm_baseuri()."src/right_main.php?sort=$sort&amp;startMessage=$startMessage&amp;mailbox=$urlMailbox",
        'TEXT'  => sprintf (_("Click here to return to %s"),
                            strtoupper($mailbox) == 'INBOX' ? _("INBOX") : htmlspecialchars(imap_utf7_decode_local($mailbox))) 
                   );
    error_box($message, $link);
}

/**
 * Displays error message
 * 
 * Second argument ($color array) is removed in 1.5.2.
 * @param string $message error message
 * @since 1.0
 */
function plain_error_message($message) {
    error_box($message);
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
    global $frame_top, $org_logo, $org_logo_width, $org_logo_height, $org_name,
           $hide_sm_attributions, $squirrelmail_language, $oTemplate;

    $base_uri = sqm_baseuri();

    $login_link = array (
                            'URI'   => $base_uri . 'src/login.php',
                            'FRAME' => $frame_top
                        );
                        
    /* As of 1.5.2, plugin parameters are combined into one array; 
       plugins on this hook must be updated */
    do_hook('logout_error', $temp=array(&$errString, &$errTitle, &$login_link));

    if ( $errTitle == '' ) {
        $errTitle = $errString;
    }
    set_up_language($squirrelmail_language, true);

    displayHtmlHeader( $org_name.' - '.$errTitle, '', false );

    /* If they don't have a logo, don't bother.. */
    $logo_str = '';
    if (isset($org_logo) && $org_logo) {
        /* Display width and height like good little people */
        $width_and_height = '';
        if (isset($org_logo_width) && is_numeric($org_logo_width) &&
         $org_logo_width>0) {
            $width_and_height = " width=\"$org_logo_width\"";
        }
        if (isset($org_logo_height) && is_numeric($org_logo_height) &&
         $org_logo_height>0) {
            $width_and_height .= " height=\"$org_logo_height\"";
        }
        
        $logo_str = '<img src="'.$org_logo.'" ' .
                    'alt="'. sprintf(_("%s Logo"), $org_name).'" ' .
                    $width_and_height .
                    'class="sqm_loginImage" ' .
                    ' /><br />'."\n";
    }
    
    $sm_attribute_str = '';
    if (isset($hide_sm_attributions) && !$hide_sm_attributions) {
        $sm_attribute_str = _("SquirrelMail Webmail Application") . "\n" 
                          . _("By the SquirrelMail Project Team");
    }

    $oTemplate->assign('logo_str', $logo_str);
    $oTemplate->assign('sm_attribute_str', $sm_attribute_str);
    $oTemplate->assign('login_link', $login_link);
    $oTemplate->assign('errorMessage', $errString);
    $oTemplate->display('error_logout.tpl');

    $oTemplate->display('footer.tpl');
}

/**
 * Displays error message
 * 
 * Since 1.4.1 function checks if page header is already displayed.
 * 
 * Since 1.4.3 and 1.5.1, this function contains the error_box hook.
 * Use plain_error_message() and make sure that page header is created,
 * if you want compatibility with 1.4.0 and older.
 *
 * In 1.5.2 second function argument is changed. Older functions used it
 * for $color array, new function uses it for optional link data. Function 
 * will ignore color array and use standard colors instead.
 *
 * @param string $string Error message to be displayed
 * @param array $link Optional array containing link details to be displayed.
 *  Array uses three keys. 'URL' key is required and should contain link URL.
 *  'TEXT' key is optional and should contain link name. 'FRAME' key is 
 *  optional and should contain link target attribute.
 *
 * @since 1.3.2
 */
function error_box($string, $link=NULL) {
    global $pageheader_sent, $oTemplate;

    $err = _("ERROR");
    do_hook('error_box', $string);

    /* check if the page header has been sent; if not, send it! */
    if (!isset($pageheader_sent) && !$pageheader_sent) {
        displayHtmlHeader('SquirrelMail: '.$err);
        $pageheader_sent = TRUE;
        echo "<body>\n\n";
    }

    // Double check the link for everything we need
    if (!is_null($link)) {
        // safety check for older code
        if (isset($link['URL'])) {
            if (!isset($link['FRAME'])) $link['FRAME'] = '';
            if (!isset($link['TEXT'])) $link['TEXT'] = $link['URL'];
        } else {
            // somebody used older error_box() code
            $link=null;
        }
    }
    
    /** ERROR is pre-translated to avoid multiple translation calls. **/
    $oTemplate->assign('error', $err);
    $oTemplate->assign('errorMessage', $string);
    $oTemplate->assign('link', $link);
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
