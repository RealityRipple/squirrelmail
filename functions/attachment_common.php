<?php

/**
 * attachment_common.php
 *
 * This file provides the handling of often-used attachment types.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @todo document attachment $type hook arguments
 */

$attachment_common_show_images_list = array();

/**
 * Mapping of file extensions to mime types
 *
 * Used for application/octet-stream mime type detection.
 * Supported extensions: bmp, gif, htm, html, jpg, jpeg, php,
 * png, rtf, txt, patch (since 1.4.2), vcf
 * @global array $FileExtensionToMimeType
 */
$FileExtensionToMimeType = array('bmp'  => 'image/x-bitmap',
                                 'gif'  => 'image/gif',
                                 'htm'  => 'text/html',
                                 'html' => 'text/html',
                                 'jpe'  => 'image/jpeg',
                                 'jpg'  => 'image/jpeg',
                                 'jpeg' => 'image/jpeg',
                                 'php'  => 'text/plain',
                                 'png'  => 'image/png',
                                 'rtf'  => 'text/richtext',
                                 'txt'  => 'text/plain',
                                 'patch'=> 'text/plain',
                                 'vcf'  => 'text/x-vcard');

/* Register browser-supported image types */
sqgetGlobalVar('attachment_common_types', $attachment_common_types);
// FIXME: do we use $attachment_common_types that is not extracted by sqgetGlobalVar() ?
if (isset($attachment_common_types)) {
    // var is used to detect activation of jpeg image types
    unset($jpeg_done);
    /* Don't run this before being logged in. That may happen
       when plugins include mime.php */
    foreach ($attachment_common_types as $val => $v) {
        if ($val == 'image/gif')
            register_attachment_common('image/gif',       'link_image');
        elseif (($val == 'image/jpeg' || $val == 'image/pjpeg' || $val == 'image/jpg') and
                (!isset($jpeg_done))) {
            $jpeg_done = 1;
            register_attachment_common('image/jpg',       'link_image');
            register_attachment_common('image/jpeg',      'link_image');
            register_attachment_common('image/pjpeg',     'link_image');
        }
        elseif ($val == 'image/png')
            register_attachment_common('image/png',       'link_image');
        elseif ($val == 'image/x-xbitmap')
            register_attachment_common('image/x-xbitmap', 'link_image');
        elseif ($val == '*/*' || $val == 'image/*') {
            /**
             * browser (Firefox) declared that anything is acceptable.
             * Lets register some common image types.
             */
            if (! isset($jpeg_done)) {
                $jpeg_done = 1;
                register_attachment_common('image/jpg',   'link_image');
                register_attachment_common('image/jpeg',  'link_image');
                register_attachment_common('image/pjpeg', 'link_image');
            }
            register_attachment_common('image/gif',       'link_image');
            register_attachment_common('image/png',       'link_image');
            register_attachment_common('image/x-xbitmap', 'link_image');
            // register_attachment_common('image/x-ico',     'link_image');
            // register_attachment_common('image/x-icon',    'link_image');
            // register_attachment_common('image/bmp',       'link_image');
            // register_attachment_common('image/x-ms-bmp',  'link_image');
        }
    }
    unset($jpeg_done);
}

/* Register text-type attachments */
register_attachment_common('message/rfc822', 'link_message');
register_attachment_common('text/plain',     'link_text');
register_attachment_common('text/richtext',  'link_text');

/* Register HTML */
register_attachment_common('text/html',      'link_html');

/* Register vcards */
register_attachment_common('text/x-vcard',   'link_vcard');
register_attachment_common('text/directory', 'link_vcard');

/* Register rules for general types.
 * These will be used if there isn't a more specific rule available. */
register_attachment_common('text/*',  'link_text');
register_attachment_common('message/*',  'link_text');

/* Register "unknown" attachments */
register_attachment_common('application/octet-stream', 'octet_stream');


/**
 * Function which optimizes readability of the above code, and also
 * ensures that the attachment_common code is exectuted before any
 * plugin, so that the latter may override the default processing.
 *
 * Registers 'attachment $type' hooks.
 *
 * @param string $type Attachment type
 * @param string $func Suffix of attachment_common_* function, which 
 *                     handles $type attachments.
 *
 * @since 1.2.0
 */
function register_attachment_common($type, $func) {
    global $squirrelmail_plugin_hooks;
    $plugin_type = 'attachment ' . $type;
    $fn = 'attachment_common_' . $func;
    if (!empty($squirrelmail_plugin_hooks[$plugin_type])) {
        $plugins = $squirrelmail_plugin_hooks[$plugin_type];
        $plugins = array_merge(array('attachment_common', $fn), $plugins);
        $squirrelmail_plugin_hooks[$plugin_type] = $plugins;
    } else {
        $squirrelmail_plugin_hooks[$plugin_type]['attachment_common'] = $fn;
    }
}

/**
 * Adds href and text keys to attachment_common array for text attachments
 * @param array $Args attachment $type hook arguments
 * @since 1.2.0
 */
function attachment_common_link_text(&$Args) {
    global $base_uri;
    /* If there is a text attachment, we would like to create a "View" button
       that links to the text attachment viewer.

       $Args[0] = the array of actions

       Use the name of this file for adding an action
       $Args[0]['attachment_common'] = Array for href and text

       $Args[0]['attachment_common']['text'] = What is displayed
       $Args[0]['attachment_common']['href'] = Where it links to */
    sqgetGlobalVar('QUERY_STRING', $QUERY_STRING, SQ_SERVER);

    // if sm_encode_html_special_chars() breaks something - find other way to encode & in url.
    $Args[0]['attachment_common']['href'] = $base_uri  . 'src/view_text.php?'. $QUERY_STRING;
    $Args[0]['attachment_common']['href'] =
          set_url_var($Args[0]['attachment_common']['href'],
          'ent_id',$Args[4]);

    /* The link that we created needs a name. */
    $Args[0]['attachment_common']['text'] = _("View");

    /* Each attachment has a filename on the left, which is a link.
       Where that link points to can be changed.  Just in case the link above
       for viewing text attachments is not the same as the default link for
       this file, we'll change it.

       This is a lot better in the image links, since the defaultLink will just
       download the image, but the one that we set it to will format the page
       to have an image tag in the center (looking a lot like this text viewer) */
    $Args[5] = $Args[0]['attachment_common']['href'];
}

/**
 * Adds href and text keys to attachment_common array for rfc822 attachments
 * @param array $Args attachment $type hook arguments
 * @since 1.2.6
 */
function attachment_common_link_message(&$Args) {
    global $base_uri;
    $Args[0]['attachment_common']['href'] = $base_uri  . 'src/read_body.php?startMessage=' .
        $Args[1] . '&amp;passed_id=' . $Args[2] . '&amp;mailbox=' . $Args[3] .
        '&amp;passed_ent_id=' . $Args[4] . '&amp;override_type0=message&amp;override_type1=rfc822';

    $Args[0]['attachment_common']['text'] = _("View");

    $Args[5] = $Args[0]['attachment_common']['href'];
}

/**
 * Adds href and text keys to attachment_common array for html attachments
 * @param array $Args attachment $type hook arguments
 * @since 1.2.0
 */
function attachment_common_link_html(&$Args) {
    global $base_uri;
    sqgetGlobalVar('QUERY_STRING', $QUERY_STRING, SQ_SERVER);

    $Args[0]['attachment_common']['href'] = $base_uri  . 'src/view_text.php?'. $QUERY_STRING.
        /* why use the overridetype? can this be removed */
        /* override_type might be needed only when we want view other type of messages as html */
       '&amp;override_type0=text&amp;override_type1=html';
    $Args[0]['attachment_common']['href'] =
          set_url_var($Args[0]['attachment_common']['href'],
          'ent_id',$Args[4]);

    $Args[0]['attachment_common']['text'] = _("View");

    $Args[5] = $Args[0]['attachment_common']['href'];
}

/**
 * Adds href and text keys to attachment_common array for image attachments
 * @param array $Args attachment $type hook arguments
 * @since 1.2.0
 */
function attachment_common_link_image(&$Args) {
    global $attachment_common_show_images_list, $base_uri ;

    sqgetGlobalVar('QUERY_STRING', $QUERY_STRING, SQ_SERVER);

    $info['passed_id'] = $Args[2];
    $info['mailbox'] = $Args[3];
    $info['ent_id'] = $Args[4];
    $info['name'] = $Args[6];
    $info['download_href'] = isset($Args[0]['download link']) ? $Args[0]['download link']['href'] : '';
    
    $attachment_common_show_images_list[] = $info;

    $Args[0]['attachment_common']['href'] = $base_uri  . 'src/image.php?'. $QUERY_STRING;
    $Args[0]['attachment_common']['href'] =
          set_url_var($Args[0]['attachment_common']['href'],
          'ent_id',$Args[4]);

    $Args[0]['attachment_common']['text'] = _("View");

    $Args[5] = $Args[0]['attachment_common']['href'];
}

/**
 * Adds href and text keys to attachment_common array for vcard attachments
 * @param array $Args attachment $type hook arguments
 * @since 1.2.0
 */
function attachment_common_link_vcard(&$Args) {
    global $base_uri;
    sqgetGlobalVar('QUERY_STRING', $QUERY_STRING, SQ_SERVER);

    $Args[0]['attachment_common']['href'] = $base_uri  . 'src/vcard.php?'. $QUERY_STRING;
    $Args[0]['attachment_common']['href'] =
          set_url_var($Args[0]['attachment_common']['href'],
          'ent_id',$Args[4]);

    $Args[0]['attachment_common']['text'] = _("View Business Card");

    $Args[5] = $Args[0]['attachment_common']['href'];
}

/**
 * Processes octet-stream attachments.
 * Calls attachment_common-load_mime_types and attachment $type hooks.
 * @param array $Args attachment $type hook arguments
 * @since 1.2.0
 */
function attachment_common_octet_stream(&$Args) {
    global $FileExtensionToMimeType, $null;

//FIXME: I propose removing this hook; I don't like having two hooks close together, but moreover, this hook appears to merely give plugins the chance to add to the global $FileExtensionToMimeType variable, which they can do in any hook before now - I'd recommend prefs_backend (which is what config_override used to be) because it's the one hook run at the beginning of almost all page requests in init.php -- the con is that we don't need it run on ALL page requests, do we?  There may be another hook in THIS page request that we can recommend, in which case, we *really should* remove this hook here.
//FIXME: or at least we can move this hook up to the top of this file where $FileExtensionToMimeType is defined.  What else is this hook here for?  What plugins use it?
    do_hook('attachment_common-load_mime_types', $null);

    preg_match('/\.([^.]+)$/', $Args[7], $Regs);

    $Ext = '';
    if (is_array($Regs) && isset($Regs[1])) {
        $Ext = $Regs[1];
        $Ext = strtolower($Regs[1]);
    }

    if ($Ext == '' || ! isset($FileExtensionToMimeType[$Ext]))
        return;

    $temp = array(&$Args[0], &$Args[1], &$Args[2], &$Args[3], &$Args[4], &$Args[5],
                  &$Args[6], &$Args[7], &$Args[8]);
    do_hook('attachment ' . $FileExtensionToMimeType[$Ext], $temp);

}
