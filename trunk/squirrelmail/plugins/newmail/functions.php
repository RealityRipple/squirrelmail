<?php

/**
 * SquirrelMail NewMail plugin
 *
 * Functions
 *
 * @copyright 2001-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage newmail
 * @todo add midi support
 */


/** file type defines */
define('SM_NEWMAIL_FILETYPE_WAV',2);
define('SM_NEWMAIL_FILETYPE_MP3',3);
define('SM_NEWMAIL_FILETYPE_OGG',4);
define('SM_NEWMAIL_FILETYPE_SWF',5);
define('SM_NEWMAIL_FILETYPE_SVG',6);

/** load default config */
if (file_exists(SM_PATH . 'plugins/newmail/config_default.php')) {
    include_once(SM_PATH . 'plugins/newmail/config_default.php');
}

/** load config */
if (file_exists(SM_PATH . 'config/newmail_config.php')) {
    include_once(SM_PATH . 'config/newmail_config.php');
} elseif (file_exists(SM_PATH . 'plugins/newmail/config.php')) {
    include_once(SM_PATH . 'plugins/newmail/config.php');
}

// ----- hooked functions -----

/**
 * Register newmail option block
 */
function newmail_optpage_register_block_function() {
    // Gets added to the user's OPTIONS page.
    global $optpage_blocks;

    /* Register Squirrelspell with the $optionpages array. */
    $optpage_blocks[] = array(
        'name' => _("New Mail Options"),
        'url'  => sqm_baseuri() . 'plugins/newmail/newmail_opt.php',
        'desc' => _("This configures settings for playing sounds and/or showing popup windows when new mail arrives."),
        'js'   => TRUE
        );
}

/**
 * Save newmail plugin settings
 */
function newmail_sav_function() {
    global $data_dir, $username, $_FILES, $newmail_uploadsounds;

    if ( sqgetGlobalVar('submit_newmail', $submit, SQ_POST) ) {
        $media_enable = '';
        $media_popup = '';
        $media_recent = '';
        $media_changetitle = '';
        $media_sel = '';
        $popup_width = '';
        $popup_height = '';

        sqgetGlobalVar('media_enable',      $media_enable,      SQ_POST);
        sqgetGlobalVar('media_popup',       $media_popup,       SQ_POST);
        sqgetGlobalVar('media_recent',      $media_recent,      SQ_POST);
        sqgetGlobalVar('media_changetitle', $media_changetitle, SQ_POST);
        sqgetGlobalVar('popup_width',       $popup_width,       SQ_POST);
        sqgetGlobalVar('popup_height',      $popup_height,      SQ_POST);

        // sanitize height and width
        $popup_width = (int) $popup_width;
        if ($popup_width<=0) $popup_width=200;
        $popup_height = (int) $popup_height;
        if ($popup_height<=0) $popup_height=130;

        setPref($data_dir,$username,'newmail_enable',$media_enable);
        setPref($data_dir,$username,'newmail_popup', $media_popup);
        setPref($data_dir,$username,'newmail_recent',$media_recent);
        setPref($data_dir,$username,'newmail_changetitle',$media_changetitle);
        setPref($data_dir,$username,'newmail_popup_width',$popup_width);
        setPref($data_dir,$username,'newmail_popup_height',$popup_height);

        if (sqgetGlobalVar('newmail_unseen_notify', $newmail_unseen_notify, SQ_POST)) {
            $newmail_unseen_notify = (int) $newmail_unseen_notify;
            setPref($data_dir,$username,'newmail_unseen_notify',$newmail_unseen_notify);
        }

        if( sqgetGlobalVar('media_sel', $media_sel, SQ_POST) &&
            $media_sel == '(none)' ) {
            removePref($data_dir,$username,'newmail_media');
        } else {
            setPref($data_dir,$username,'newmail_media',$media_sel);
        }

        // process uploaded file
        if ($newmail_uploadsounds && isset($_FILES['media_file']['tmp_name']) && $_FILES['media_file']['tmp_name']!='') {
            // set temp file and get media file name
            $newmail_tempmedia=getHashedDir($username, $data_dir) . "/$username.tempsound";
            $newmail_mediafile=getHashedFile($username, $data_dir, $username . '.sound');
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $newmail_tempmedia)) {
                // new media file is in $newmail_tempmedia
                if (file_exists($newmail_mediafile)) unlink($newmail_mediafile);
                if (! rename($newmail_tempmedia,$newmail_mediafile)) {
                    // remove (userfile), if file rename fails
                    removePref($data_dir,$username,'newmail_media');
                } else {
                    // store media type
                    if (isset($_FILES['media_file']['type']) && isset($_FILES['media_file']['name'])) {
                        setPref($data_dir,$username,'newmail_userfile_type',
                            newmail_get_mediatype($_FILES['media_file']['type'],$_FILES['media_file']['name']));
                    } else {
                        removePref($data_dir,$username,'newmail_userfile_type');
                    }
                    // store file name
                    if (isset($_FILES['media_file']['name'])) {
                        setPref($data_dir,$username,'newmail_userfile_name',basename($_FILES['media_file']['name']));
                    } else {
                        setPref($data_dir,$username,'newmail_userfile_name','mediafile.unknown');
                    }
                }
            }
        }
    }
}

/**
 * Load newmail plugin settings
 */
function newmail_pref_function() {
    global $username,$data_dir;
    global $newmail_media,$newmail_media_enable,$newmail_popup;
    global $newmail_recent, $newmail_changetitle;
    global $newmail_userfile_type, $newmail_userfile_name;
    global $newmail_popup_width, $newmail_popup_height;
    global $newmail_unseen_notify;

    $newmail_recent = getPref($data_dir,$username,'newmail_recent');
    $newmail_media_enable = getPref($data_dir,$username,'newmail_enable');
    $newmail_media = getPref($data_dir, $username, 'newmail_media', '(none)');
    // remove full location from setting (since SM 1.5.1 plugin uses only filename).
    if ($newmail_media!='(none)')
        $newmail_media = basename($newmail_media);

    $newmail_popup = getPref($data_dir, $username, 'newmail_popup');
    $newmail_popup_width = getPref($data_dir, $username, 'newmail_popup_width',200);
    $newmail_popup_height = getPref($data_dir, $username, 'newmail_popup_height',130);
    $newmail_changetitle = getPref($data_dir, $username, 'newmail_changetitle');

    $newmail_userfile_type = getPref($data_dir, $username, 'newmail_userfile_type');
    $newmail_userfile_name = getPref($data_dir,$username,'newmail_userfile_name','');

    $newmail_unseen_notify = getPref($data_dir,$username,'newmail_unseen_notify',0);
}

/**
 * Set loadinfo data
 *
 * Used by option page when saving settings.
 */
function newmail_set_loadinfo_function() {
    global $optpage, $optpage_name;
    if ($optpage=='newmail') {
        $optpage_name=_("New Mail Options");
    }
}


/* Receive the status of the folder and do something with it */
function newmail_folder_status($statusarr) {
    global $newmail_media_enable,$newmail_popup,$newmail_changetitle,$trash_folder,
           $sent_folder,$totalNewArr, $newmail_unseen_notify, $unseen_notify, $newmail_recent;

    /* if $newmail_unseen_notify is set to zero, plugin follows $unseen_notify */
    if ($newmail_unseen_notify == 0)
        $newmail_unseen_notify = $unseen_notify;

    $mailbox=$statusarr['MAILBOX'];

    if (($newmail_media_enable == 'on' ||
        $newmail_popup == 'on' ||
        $newmail_changetitle == 'on') &&
        /**
         * make sure that $newmail_unseen_notify is set to supported value,
         * currently (1.5.2cvs) SMPREF_UNSEEN_NORMAL has highest integer value
         * in SMPREF_UNSEEN constants
         */
        ($newmail_unseen_notify > SMPREF_UNSEEN_NONE && $newmail_unseen_notify <= SMPREF_UNSEEN_NORMAL)) {

        // Skip folders for Sent and Trash
        // TODO: make this optional
        if ($statusarr['MAILBOX'] == $sent_folder || $statusarr['MAILBOX'] == $trash_folder) {
            return 0;
        }

        if ((($mailbox == 'INBOX') && ($newmail_unseen_notify == SMPREF_UNSEEN_INBOX)) ||
            ($newmail_unseen_notify == SMPREF_UNSEEN_SPECIAL && isSpecialMailbox($mailbox)) ||
            ($newmail_unseen_notify == SMPREF_UNSEEN_NORMAL && ! isSpecialMailbox($mailbox)) ||
            ($newmail_unseen_notify == SMPREF_UNSEEN_ALL)) {
            if (($newmail_recent == 'on') && (!empty($statusarr['RECENT']))) {
                $totalNewArr[$mailbox] = $statusarr['RECENT'];
            } elseif ($newmail_recent != 'on' && !empty($statusarr['UNSEEN'])) {
                $totalNewArr[$mailbox] = $statusarr['UNSEEN'];
            }
        }
    }
}

/**
 * Insert needed data in left_main
 */
function newmail_plugin_function() {
    global $username, $newmail_media, $newmail_media_enable, $newmail_popup,
           $newmail_recent, $newmail_changetitle, $imapConnection,
           $newmail_mmedia, $newmail_allowsound, $newmail_userfile_type,
           $newmail_popup_width, $newmail_popup_height, $totalNewArr,
           $newmail_title_bar_singular, $newmail_title_bar_plural,
           $org_title;

    if ($newmail_media_enable == 'on' ||
        $newmail_popup == 'on' ||
        $newmail_changetitle) {

        $output = '';

        if (!empty($totalNewArr)) { $totalNew=array_sum($totalNewArr); }
        else { $totalNew=0; }

        // If we found unseen messages, then we
        // will play the sound as follows:

        if ($newmail_changetitle) {

            // make sure default strings are in pot file
            $ignore = _("%s New Message");
            $ignore = _("%s New Messages");

            $singular_title = "%s New Message";
            $plural_title = "%s New Messages";
            if (!empty($newmail_title_bar_singular))
                $singular_title = $newmail_title_bar_singular;
            if (!empty($newmail_title_bar_plural))
                $plural_title = $newmail_title_bar_plural;
            list($singular_title, $plural_title) = str_replace(array('###USERNAME###', '###ORG_TITLE###'), array($username, $org_title), array($singular_title, $plural_title));
            $title = sprintf(ngettext($singular_title, $plural_title, $totalNew), $totalNew);

//FIXME: remove HTML from core - put this into a template file
            $output .= "<script type=\"text/javascript\">\n"
                    . "function ChangeTitleLoad() {\n"
                    . "var BeforeChangeTitle;\n"
                    . 'window.parent.document.title = "'
                    . $title
                    . "\";\n"
                    . "if (BeforeChangeTitle != null)\n"
                    . "BeforeChangeTitle();\n"
                    . "}\n"
                    . "BeforeChangeTitle = window.onload;\n"
                    . "window.onload = ChangeTitleLoad;\n"
                    . "</script>\n";
        }

        // create media output if there are new email messages
        if ($newmail_allowsound && $totalNew > 0
         && $newmail_media_enable == 'on'
         && $newmail_media != '' ) {
//FIXME: remove HTML from core - put this into a template file
            $output .= newmail_create_media_tags($newmail_media);
        }

        if ($totalNew > 0 && $newmail_popup == 'on') {
//FIXME: remove HTML from core - put this into a template file
            $output .= "<script type=\"text/javascript\">\n"
                    . "<!--\n"
                    . "function PopupScriptLoad() {\n"
                    . 'window.open("'.sqm_baseuri().'plugins/newmail/newmail.php?numnew='.$totalNew
                    . '", "SMPopup",'
                    . "\"width=$newmail_popup_width,height=$newmail_popup_height,scrollbars=no\");\n"
                    . "if (BeforePopupScript != null)\n"
                    . "BeforePopupScript();\n"
                    . "}\n"
                    . "BeforePopupScript = window.onload;\n"
                    . "window.onload = PopupScriptLoad;\n"
                    . "// End -->\n"
                    . "</script>\n";
        }

        return array('left_main_after' => $output);

    }

}

// ----- end of hooked functions -----



/**
 * Function tries to detect if file contents match declared file type
 *
 * Function returns default extension for detected mime type or 'false'
 *
 * TODO: use $contents to check if file is in specified type
 * @param string $contents file contents
 * @param string $type file mime type
 * @return string
 */
function newmail_detect_filetype($contents,$type) {
    // convert $type to lower case
    $type=strtolower($type);

    $ret=false;

    switch ($type) {
    case 'audio/x-wav':
        $ret='wav';
        break;
    case 'audio/mpeg':
        $ret='mp3';
        break;
    case 'application/ogg':
        $ret='ogg';
        break;
    case 'application/x-shockwave-flash':
        $ret='swf';
        break;
    case 'image/svg+xml':
        $ret='svg';
        break;
    default:
        $ret=false;
    }
    return $ret;
}

/**
 * Function tries to detect uploaded file type
 * @param string $type
 * @param string $filename
 * @return integer One of SM_NEWMAIL_FILETYPE_* defines or false.
 */
function newmail_get_mediatype($type,$filename) {
    switch ($type) {
    // fix for browser's that upload file as application/octet-stream
    case 'application/octet-stream':
        $ret=newmail_get_mediatype_by_ext($filename);
        break;
    case 'audio/x-wav':
        $ret=SM_NEWMAIL_FILETYPE_WAV;
        break;
    case 'audio/mpeg':
        $ret=SM_NEWMAIL_FILETYPE_MP3;
        break;
    case 'application/ogg':
        $ret=SM_NEWMAIL_FILETYPE_OGG;
        break;
    case 'application/x-shockwave-flash':
        $ret=SM_NEWMAIL_FILETYPE_SWF;
        break;
    case 'image/svg+xml':
        $ret=SM_NEWMAIL_FILETYPE_SVG;
        break;
    default:
        $ret=false;
    }
    return $ret;
}

/**
 * Function provides filetype detection for browsers, that
 * upload files with application/octet-stream file type.
 * Ex. some version of Opera.
 * @param string $filename
 * @return integer One of SM_NEWMAIL_FILETYPE_* defines or false.
 */
function newmail_get_mediatype_by_ext($filename) {
    if (preg_match("/\.wav$/i",$filename)) return SM_NEWMAIL_FILETYPE_WAV;
    if (preg_match("/\.mp3$/i",$filename)) return SM_NEWMAIL_FILETYPE_MP3;
    if (preg_match("/\.ogg$/i",$filename)) return SM_NEWMAIL_FILETYPE_OGG;
    if (preg_match("/\.swf$/i",$filename)) return SM_NEWMAIL_FILETYPE_SWF;
    if (preg_match("/\.svg$/i",$filename)) return SM_NEWMAIL_FILETYPE_SVG;
    return false;
}

/**
 * Creates html object tags of multimedia object
 *
 * Main function that creates multimedia object tags
 * @param string $object object name
 * @param integer $type media object type
 * @param string $path URL to media object
 * @param array $args media object attributes
 * @param string $extra tags that have to buried deep inside object tags
 * @param bool $addsuffix controls addition of suffix to media object url
 * @return string object html tags and attributes required by selected media type.
 */
function newmail_media_objects($object,$types,$path,$args=array(),$extra='',$addsuffix=true) {
    global $newmail_mediacompat_mode;

    // first prepare single object for IE
    $ret = newmail_media_object_ie($object,$types[0],$path,$args,$addsuffix);

    // W3.org nested objects
    $ret.= "<!--[if !IE]> <-->\n"; // not for IE

    foreach ($types as $type) {
        $ret.= newmail_media_object($object,$type,$path,$args,$addsuffix);
    }

    if (isset($newmail_mediacompat_mode) && $newmail_mediacompat_mode)
        $ret.= newmail_media_embed($object,$types[0],$path,$args,$addsuffix);
    // add $extra code inside objects
    if ($extra!='')
        $ret.=$extra . "\n";

    // close embed tags
    if (isset($newmail_mediacompat_mode) && $newmail_mediacompat_mode)
        $ret.= newmail_media_embed_close($types[0]);

    // close w3.org nested objects
    foreach (array_reverse($types) as $type) {
        $ret.= newmail_media_object_close($type);
    }
    $ret.= "<!--> <![endif]-->\n"; // end non-IE mode
    // close IE object
    $ret.= newmail_media_object_ie_close($types[0]);

    return $ret;
}

/**
 * Creates object tags of multimedia object for browsers that comply to w3.org
 * specifications.
 *
 * Warnings:
 * <ul>
 *   <li>Returned string does not contain html closing tag.
 *   <li>This is internal function, use newmail_media_objects() instead
 * </ul>
 * @link http://www.w3.org/TR/html4/struct/objects.html#edef-OBJECT W3.org specs
 * @param string $object object name
 * @param integer $type media object type
 * @param string $path URL to media object
 * @param array $args media object attributes
 * @param bool $addsuffix controls addition of suffix to media object url
 * @return string object html tags and attributes required by selected media type.
 */
function newmail_media_object($object,$type,$path,$args=array(),$addsuffix=true) {
    $ret_w3='';
    $suffix='';
    $sArgs=newmail_media_prepare_args($args);

    switch ($type) {
    case SM_NEWMAIL_FILETYPE_SWF:
        if ($addsuffix) $suffix='.swf';
        $ret_w3 = '<object data="' . $path . $object . $suffix . '" '
            .$sArgs
            .'type="application/x-shockwave-flash">' . "\n";
        break;
    case SM_NEWMAIL_FILETYPE_WAV:
        if ($addsuffix) $suffix='.wav';
        $ret_w3 = '<object data="' . $path . $object . $suffix . '" '
            .$sArgs
            .'type="audio/x-wav">' . "\n";
        break;
    case SM_NEWMAIL_FILETYPE_OGG:
        if ($addsuffix) $suffix='.ogg';
        $ret_w3 = '<object data="' . $path . $object . $suffix . '" '
            .$sArgs
            .'type="application/ogg">' . "\n";
        break;
    case SM_NEWMAIL_FILETYPE_MP3:
        if ($addsuffix) $suffix='.mp3';
        $ret_w3 = '<object data="' . $path . $object . $suffix . '" '
            .$sArgs
            .'type="audio/mpeg">' . "\n";
        break;
    case SM_NEWMAIL_FILETYPE_SVG:
        if ($addsuffix) $suffix='.svg';
        $ret_w3 = '<object data="' . $path . $object . $suffix . '" '
            .$sArgs
            .'type="image/svg+xml">' . "\n";
        break;
    default:
        $ret_w3='';
    }
    return $ret_w3;
}

/**
 * Creates multimedia object tags for Internet Explorer (Win32)
 *
 * Warning:
 * * Returned string does not contain html closing tag, because
 * this multimedia object can include other media objects.
 * * This is internal function, use newmail_media_objects() instead
 *
 * @param string $object object name
 * @param integer $type media object type
 * @param string $path URL to media object
 * @param array $args media object attributes
 * @param bool $addsuffix controls addition of suffix to media object url
 * @return string object html tags and attributes required by selected media type.
 * @todo add ogg and svg support
 */
function newmail_media_object_ie($object,$type,$path,$args=array(),$addsuffix) {
    $ret_ie='';
    $suffix='';
    $sArgs=newmail_media_prepare_args($args);

    switch ($type) {
    case SM_NEWMAIL_FILETYPE_SWF:
        if ($addsuffix) $suffix='.swf';
        $ret_ie ='<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" '
            .'codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0" '
            . $sArgs . 'id="' . $object ."\">\n"
            .'<param name="movie" value="' . $path . $object . $suffix . "\">\n"
            .'<param name="hidden" value="true">' . "\n";
        break;
    case SM_NEWMAIL_FILETYPE_WAV:
        if ($addsuffix) $suffix='.wav';
        $ret_ie ='<object classid="clsid:22D6F312-B0F6-11D0-94AB-0080C74C7E95" '
            .'codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,0,02,0902" '
            . $sArgs . 'id="' . $object ."\" \n"
            .'type="audio/x-wav">' ."\n"
            .'<param name="FileName" value="' . $path . $object . $suffix . "\">\n";
        break;
    case SM_NEWMAIL_FILETYPE_MP3:
        if ($addsuffix) $suffix='.mp3';
        $ret_ie ='<object classid="clsid:22D6F312-B0F6-11D0-94AB-0080C74C7E95" '
            .'codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,0,02,0902" '
            . $sArgs . 'id="' . $object ."\" \n"
            .'type="audio/mpeg">' ."\n"
            .'<param name="FileName" value="' . $path . $object . $suffix . "\">\n";
            break;
    case SM_NEWMAIL_FILETYPE_OGG:
    case SM_NEWMAIL_FILETYPE_SVG:
    default:
        $ret_ie='';
    }
    return $ret_ie;
}

/**
 * Creates embed tags of multimedia object
 *
 * docs about embed
 * Apple: http://www.apple.com/quicktime/authoring/embed.html
 *
 * Warnings:
 * * Returned string does not contain html closing tag.
 * * embed tags will be created by newmail_media_objects() only
 *   when $newmail_mediacompat_mode option is enabled. Option is not
 *   enabled by default in order to comply to w3.org specs.
 * * This is internal function, use newmail_media_objects() instead
 * @link http://www.apple.com/quicktime/authoring/embed.html Info about embed tag
 * @param string $object object name
 * @param integer $type media object type
 * @param string $path URL to media object
 * @param array $args media object attributes
 * @param bool $addsuffix controls addition of suffix to media object url
 * @return string embed html tags and attributes required by selected media type.
 */
function newmail_media_embed($object,$type,$path,$args=array(),$addsuffix=true) {
    $ret_embed='';
    $suffix='';
    $sArgs=newmail_media_prepare_args($args);

    switch ($type) {
    case SM_NEWMAIL_FILETYPE_SWF:
        if ($addsuffix) $suffix='.swf';
        $ret_embed='<embed src="' . $path . $object . $suffix . '" '. "\n"
            .'hidden="true" autostart="true" '. "\n"
            .$sArgs . "\n"
            .'name="' . $object .'" ' . "\n"
            .'type="application/x-shockwave-flash" ' . "\n"
            .'pluginspage="http://www.macromedia.com/go/getflashplayer">' . "\n";
        break;
    case SM_NEWMAIL_FILETYPE_WAV:
        if ($addsuffix) $suffix='.wav';
        $ret_embed='<embed src="' . $path . $object . $suffix . '" '. "\n"
            .' hidden="true" autostart="true" '. "\n"
            .' ' .$sArgs . "\n"
            .' name="' . $object .'" ' . "\n"
            .' type="audio/x-wav">' . "\n";
        break;
    case SM_NEWMAIL_FILETYPE_SVG:
        if ($addsuffix) $suffix='.svg';
        $ret_embed='<embed src="' . $path . $object . $suffix . '" '. "\n"
            .'hidden="true" autostart="true" '. "\n"
            .$sArgs . "\n"
            .'name="' . $object .'" ' . "\n"
            .'type="image/svg-xml" ' . "\n"
            .'pluginspage="http://www.adobe.com/svg/viewer/install/">' . "\n";
        break;
    case SM_NEWMAIL_FILETYPE_OGG:
        if ($addsuffix) $suffix='.ogg';
        $ret_embed='<embed src="' . $path . $object . $suffix . '" '. "\n"
            .' hidden="true" autostart="true" '. "\n"
            .' ' .$sArgs . "\n"
            .' name="' . $object .'" ' . "\n"
            .' type="application/ogg">' . "\n";
        break;
    case SM_NEWMAIL_FILETYPE_MP3:
        if ($addsuffix) $suffix='.mp3';
        $ret_embed='<embed src="' . $path . $object . $suffix . '" '. "\n"
            .' hidden="true" autostart="true" '. "\n"
            .' ' .$sArgs . "\n"
            .' name="' . $object .'" ' . "\n"
            .' type="audio/mpeg">' . "\n";
        break;
    default:
        $ret_embed='';
    }
    return $ret_embed;
}

/**
 * Adds closing tags for ie object
 * Warning:
 * * This is internal function, use newmail_media_objects() instead
 * @param integer $type media object type
 * @return string closing tag of media object
 */
function newmail_media_object_ie_close($type) {
    $ret_end='';
    switch ($type) {
    case SM_NEWMAIL_FILETYPE_SWF:
    case SM_NEWMAIL_FILETYPE_WAV:
    case SM_NEWMAIL_FILETYPE_MP3:
        $ret_end="</object>\n";
        break;
    case SM_NEWMAIL_FILETYPE_OGG:
    case SM_NEWMAIL_FILETYPE_SVG:
    default:
        $ret_end='';
    }
    return $ret_end;
}

/**
 * Adds closing tags for object
 * Warning:
 * * This is internal function, use newmail_media_objects() instead
 * @param integer $type media object type
 * @return string closing tag of media object
 */
function newmail_media_object_close($type) {
    $ret_end='';
    switch ($type) {
    case SM_NEWMAIL_FILETYPE_SWF:
    case SM_NEWMAIL_FILETYPE_WAV:
    case SM_NEWMAIL_FILETYPE_OGG:
    case SM_NEWMAIL_FILETYPE_MP3:
    case SM_NEWMAIL_FILETYPE_SVG:
        $ret_end="</object>\n";
        break;
    default:
        $ret_end='';
    }
    return $ret_end;
}

/**
 * Adds closing tags for object
 * Warning:
 * * This is internal function, use newmail_media_objects() instead
 * @param integer $type media object type
 * @return string closing tag of media object
 */
function newmail_media_embed_close($type) {
    $ret_end='';
    switch ($type) {
    case SM_NEWMAIL_FILETYPE_SWF:
    case SM_NEWMAIL_FILETYPE_WAV:
    case SM_NEWMAIL_FILETYPE_OGG:
    case SM_NEWMAIL_FILETYPE_MP3:
    case SM_NEWMAIL_FILETYPE_SVG:
       $ret_end="</embed>\n";
        break;
    default:
        $ret_end='';
    }
    return $ret_end;
}

/**
 * Converts media attributes to string
 * Warning:
 * * attribute values are automatically sanitized by sm_encode_html_special_chars()
 * * This is internal function, use newmail_media_objects() instead
 * @param array $args array with object attributes
 * @return string string with object attributes
 */
function newmail_media_prepare_args($args) {
    $ret_args='';
    foreach ($args as $arg => $value) {
        $ret_args.= $arg . '="' . sm_encode_html_special_chars($value) . '" ';
    }
    return $ret_args;
}

/**
 * Detects used media type and creates all need tags
 * @param string $newmail_media
 * @return string html tags with media objects
 */
function newmail_create_media_tags($newmail_media) {
    global $newmail_mmedia, $newmail_userfile_type;

    if (preg_match("/^mmedia_+/",$newmail_media)) {
        $ret_media = "<!-- newmail mmedia option -->\n";
        // remove mmedia key
        $newmail_mmedia_short=preg_replace("/^mmedia_/",'',$newmail_media);
        // check if media option is not removed
        if (isset($newmail_mmedia[$newmail_mmedia_short])) {
            $ret_media.= newmail_media_objects($newmail_mmedia_short,
                                       $newmail_mmedia[$newmail_mmedia_short]['types'],
                                       sqm_baseuri() . 'plugins/newmail/media/',
                                       $newmail_mmedia[$newmail_mmedia_short]['args']);
        }
        $ret_media.= "<!-- end of newmail mmedia option -->\n";
    } elseif ($newmail_media=='(userfile)') {
        $ret_media = "<!-- newmail usermedia option -->\n";
        $ret_media.= newmail_media_objects('loadfile.php',
                                   array($newmail_userfile_type),
                                   sqm_baseuri() . 'plugins/newmail/',
                                   array('width'=>0,'height'=>0),
                                   '',false);
        $ret_media.= "<!-- end of newmail usermedia option -->\n";
    } else {
        $ret_media = "<!-- newmail sounds from sounds/*.wav -->\n";
        $ret_media.= newmail_media_objects(basename($newmail_media),
                                   array(SM_NEWMAIL_FILETYPE_WAV),
                                   sqm_baseuri() . 'plugins/newmail/sounds/',
                                   array('width'=>0,'height'=>0),
                                   '',false);
        $ret_media.= "<!-- end of newmail sounds from sounds/*.wav -->\n";
    }
    return $ret_media;
}
