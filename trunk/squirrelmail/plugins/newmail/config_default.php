<?php
/**
 * SquirrelMail NewMail plugin
 *
 * Default configuration file
 * @version $Id$
 * @package plugins
 * @subpackage newmail
 */

/**
 * Set $allowsound to false if you don't want sound files available
 * @global boolean $newmail_allowsound
 */
global $newmail_allowsound;
$newmail_allowsound = true;

/**
 * controls insertion of embed tags
 * @global boolean $newmail_mediacompat_mode
 */
global $newmail_mediacompat_mode;
$newmail_mediacompat_mode=false;

/**
 * List of available multimedia files.
 *
 * For example.
 * $newmail_mmedia['notify']['types'] = array(SM_NEWMAIL_FILETYPE_SWF,SM_NEWMAIL_FILETYPE_MP3,SM_NEWMAIL_FILETYPE_WAV);
 * $newmail_mmedia['notify']['args']  = array('width'=>0,'height'=>0);
 *
 * These two entries say that media/ directory contains notify.swf, notify.mp3 and notify.wav files
 * Object entities for these files should be use zero width and height attributes
 * @global array $newmail_mmedia
 */
 global $newmail_mmedia;
$newmail_mmedia=array();
?>