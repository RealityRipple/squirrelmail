<?php

/**
 * SquirrelMail NewMail plugin
 *
 * Sample configuration file
 * @copyright &copy; 2005-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage newmail
 */

// Set $newmail_allowsound to false if you don't want sound files available
$newmail_allowsound = true;

/**
 * Don't allow custom sounds 
 * prefs are stored in DB and data directory is not shared between
 * web cluster hosts.
 */
$newmail_uploadsounds = false;

// controls insertion of embed tags
$newmail_mediacompat_mode=false;

// List of enabled media files
$newmail_mmedia['notify']['types'] = array(SM_NEWMAIL_FILETYPE_SWF,SM_NEWMAIL_FILETYPE_MP3,SM_NEWMAIL_FILETYPE_WAV);
$newmail_mmedia['notify']['args']  = array('width'=>0,'height'=>0);
$newmail_mmedia['got_a_message']['types'] = array(SM_NEWMAIL_FILETYPE_SWF,SM_NEWMAIL_FILETYPE_MP3,SM_NEWMAIL_FILETYPE_WAV);
$newmail_mmedia['got_a_message']['args']  = array('width'=>0,'height'=>0);
$newmail_mmedia['monty_message']['types'] = array(SM_NEWMAIL_FILETYPE_SWF,SM_NEWMAIL_FILETYPE_MP3,SM_NEWMAIL_FILETYPE_WAV);
$newmail_mmedia['monty_message']['args']  = array('width'=>0,'height'=>0);
$newmail_mmedia['austin_mail']['types'] = array(SM_NEWMAIL_FILETYPE_SWF,SM_NEWMAIL_FILETYPE_MP3,SM_NEWMAIL_FILETYPE_WAV);
$newmail_mmedia['austin_mail']['args']  = array('width'=>0,'height'=>0);
