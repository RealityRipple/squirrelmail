<?php
/**
 * SquirrelMail NewMail plugin
 *
 * Sample configuration file
 * @version $Id$
 * @package plugins
 * @subpackage new_mail
 */

// Set $allowsound to false if you don't want sound files available
$newmail_allowsound = true;

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
?>