<?php
/**
 * SquirrelMail NewMail plugin
 *
 * Functions
 * @version $Id$
 * @package plugins
 * @subpackage new_mail
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
 * @param string $type
 * @param string $filename
 * @return integer
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
 * Ex. Opera.
 * @param string $filename
 * @return string
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

    if (isset($newmail_mediacompat_mode) && $newmail_mediacompat_mode)
        $ret.= newmail_media_embed_close($types[0]);

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
 * * Returned string does not contain html closing tag.
 * * This is internal function, use newmail_media_objects() instead
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
    case SM_NEWMAIL_FILETYPE_OGG:
    case SM_NEWMAIL_FILETYPE_MP3:
    case SM_NEWMAIL_FILETYPE_SVG:
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
       $ret_end="</embed>\n";
        break;
    case SM_NEWMAIL_FILETYPE_OGG:
    case SM_NEWMAIL_FILETYPE_MP3:
    case SM_NEWMAIL_FILETYPE_SVG:
    default:
        $ret_end='';    
    }
    return $ret_end;
}

/**
 * Converts media attributes to string
 * Warning:
 * * attribute values are automatically sanitized by htmlspecialchars()
 * * This is internal function, use newmail_media_objects() instead 
 * @param array $args array with object attributes
 * @return string string with object attributes
 */
function newmail_media_prepare_args($args) {
    $ret_args='';
    foreach ($args as $arg => $value) {
        $ret_args.= $arg . '="' . htmlspecialchars($value) . '" '; 
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
?>