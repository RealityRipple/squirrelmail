<?php
/**
 * SquirrelMail NewMail plugin
 *
 * Default configuration file
 * @version $Id$
 * @package plugins
 * @subpackage new_mail
 */

// Set $allowsound to false if you don't want sound files available
global $newmail_allowsound;
$newmail_allowsound = true;

// controls insertion of embed tags
global $newmail_mediacompat_mode;
$newmail_mediacompat_mode=false;

// Default setting should create empty array.
global $newmail_mmedia;
$newmail_mmedia=array();
?>