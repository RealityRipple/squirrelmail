<?php

/**
 * compose.php
 *
 * Functions for message compositon: writing a message, attaching files etc.
 *
 * @author Thijs Kinkhorst <kink at squirrelmail.org>
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */


/**
 * Get a new file to write an attachment to.
 * This function makes sure it doesn't overwrite other attachments,
 * preventing collisions and race conditions.
 *
 * @return filename
 * @since 1.5.2
 */
function sq_get_attach_tempfile()
{
    global $username, $attachment_dir;

    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);

    // using PHP >= 4.3.2 we can be truly atomic here
    $filemods = check_php_version ( 4,3,2 ) ? 'x' : 'w';

    // give up after 1000 tries
    $TMP_MAX = 1000;
    for ($try=0; $try<$TMP_MAX; ++$try) {

        $localfilename = GenerateRandomString(32, '', 7);
        $full_localfilename = "$hashed_attachment_dir/$localfilename";

        // filename collision. try again
        if ( file_exists($full_localfilename) ) {
            continue;
        }

        // try to open for (binary) writing
        $fp = @fopen( $full_localfilename, $filemods);

        if ( $fp !== FALSE ) {
            // success! make sure it's not readable, close and return filename
            chmod($full_localfilename, 0600);
            fclose($fp);
            return $full_localfilename;
        }
    }

    // we tried 1000 times but didn't succeed.
    error_box( _("Could not open temporary file to store attachment. Contact your system administrator to resolve this issue.") );
    return FALSE;
}


