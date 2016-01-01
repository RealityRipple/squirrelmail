<?php

/**
 * SquirrelMail NewMail plugin
 *
 * Script loads user's media file.
 *
 * @copyright 2001-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage newmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
require('../../include/init.php');
/** Load plugin functions */
include_once(SM_PATH . 'plugins/newmail/functions.php');

sqgetGlobalVar('username',$username,SQ_SESSION);
global $data_dir;

$media = getPref($data_dir,$username,'newmail_media', '(none)');
// get other prefs
$newmail_userfile_type=getPref($data_dir,$username,'newmail_userfile_type',false);

$newmail_userfile_location=getHashedFile($username, $data_dir, $username . '.sound');

if ($newmail_uploadsounds && $newmail_userfile_type!=false && file_exists($newmail_userfile_location)) {
    // open media file
    $newmail_userfile_handle = fopen($newmail_userfile_location,'rb');
    if ($newmail_userfile_handle) {
        $newmail_userfile_filesize = filesize($newmail_userfile_location);
        $newmail_userfile_contents = fread($newmail_userfile_handle,$newmail_userfile_filesize);
        fclose ($newmail_userfile_handle);

        // user prefs use only integer values to store file type
        switch($newmail_userfile_type) {
        case SM_NEWMAIL_FILETYPE_WAV:
            // wav file
            $newmail_userfile_contenttype='audio/x-wav';
            break;
        case SM_NEWMAIL_FILETYPE_MP3:
            // mp3 file
            $newmail_userfile_contenttype='audio/mpeg';
            break;
        case SM_NEWMAIL_FILETYPE_OGG:
            // ogg file
            $newmail_userfile_contenttype='application/ogg';
            break;
        case SM_NEWMAIL_FILETYPE_SWF:
            // flash file
            $newmail_userfile_contenttype='application/x-shockwave-flash';
            break;
        case SM_NEWMAIL_FILETYPE_SVG:
            // svg file
            $newmail_userfile_contenttype='image/svg+xml';
            break;
        default:
            // none of above
            $newmail_userfile_contenttype='unknown';
        }

        // make sure that media file is in correct format
        $newmail_userfile_extension=newmail_detect_filetype($newmail_userfile_contents,$newmail_userfile_contenttype);

        // last check before sending file contents to browser.
        if ($newmail_userfile_extension!=false) {
            $newmail_send_filename='mediafile.' . $newmail_userfile_extension;
            header ('Content-Disposition: inline; filename="' . $newmail_send_filename . '"');
            header('Content-Type: "' . $newmail_userfile_contenttype .'"; ' .
                   'name="' . $newmail_send_filename . '"');
            header('Content-Length: ' . $newmail_userfile_filesize );
            echo $newmail_userfile_contents;
            exit;
        } // file type detection failed
    } // failed to open userfile
} // userfile is missing or preferences don't store file type.
// maybe we should send some error code
