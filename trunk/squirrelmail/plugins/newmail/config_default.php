<?php

/**
 * SquirrelMail NewMail plugin
 *
 * Default configuration file
 * @copyright 2005-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage newmail
 */


/**
 * Custom formatting for both new mail popup window title
 * bar and changed main SquirrelMail window title bar
 *
 * If you change these, they will ONLY show up in the
 * language you use here unless you add it to your
 * SquirrelMail translation files!
 *
 * Use ###USERNAME### in these strings if you want to insert
 * the username in the title.
 *
 * Use ###ORG_TITLE### in these strings if you want to insert
 * the $org_title setting from the main SquirrelMail config
 * in the title.
 *
 * Use %s in these strings if you want to insert the number
 * of new messages in the title.
 *
 * Leave blank to use default title bar strings
 *
 * $newmail_title_bar_singular = '###USERNAME### - %s New Message';
 * $newmail_title_bar_plural = '###USERNAME### - %s New Messages';
 * $newmail_popup_title_bar_singular = '###ORG_TITLE### - New Mail';
 * $newmail_popup_title_bar_plural = '###ORG_TITLE### - New Mail';
 *
 */
global $newmail_title_bar_singular, $newmail_title_bar_plural,
       $newmail_popup_title_bar_singular, $newmail_popup_title_bar_plural;
$newmail_title_bar_singular = '';
$newmail_title_bar_plural = '';
$newmail_popup_title_bar_singular = '';
$newmail_popup_title_bar_plural = '';


/**
 * Set $newmail_allowsound to false if you don't want sound files available
 * @global boolean $newmail_allowsound
 */
global $newmail_allowsound;
$newmail_allowsound = true;


/**
 * Set $newmail_uploadsounds to false if you don't want to allow uploading 
 * of custom sound files.
 * @global boolean $newmail_uploadsounds
 */
global $newmail_uploadsounds;
$newmail_uploadsounds = true;


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
 * Object elements for these files should use zero width and height attributes
 * @global array $newmail_mmedia
 */
global $newmail_mmedia;
$newmail_mmedia=array();


