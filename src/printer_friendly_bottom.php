<?php

/**
 * printer_friendly_bottom.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * with javascript on, it is the bottom frame of printer_friendly_main.php
 * else, it is alone in a new window
 *
 * - this is the page that does all the work, really.
 *
 * $Id$
 */

/*****************************************************************/
/*** THIS FILE NEEDS TO HAVE ITS FORMATTING FIXED!!!           ***/
/*** PLEASE DO SO AND REMOVE THIS COMMENT SECTION.             ***/
/***    + Base level indent should begin at left margin, as    ***/
/***      the require_once below looks.                        ***/
/***    + All identation should consist of four space blocks   ***/
/***    + Tab characters are evil.                             ***/
/***    + all comments should use "slash-star ... star-slash"  ***/
/***      style -- no pound characters, no slash-slash style   ***/
/***    + FLOW CONTROL STATEMENTS (if, while, etc) SHOULD      ***/
/***      ALWAYS USE { AND } CHARACTERS!!!                     ***/
/***    + Please use ' instead of ", when possible. Note "     ***/
/***      should always be used in _( ) function calls.        ***/
/*** Thank you for your help making the SM code more readable. ***/
/*****************************************************************/

require_once('../src/validate.php');
require_once('../functions/strings.php');
require_once('../config/config.php');
require_once('../src/load_prefs.php');
require_once('../functions/imap.php');
require_once('../functions/page_header.php');

    $pf_cleandisplay = getPref($data_dir, $username, 'pf_cleandisplay');

    $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
    sqimap_mailbox_select($imap_stream, $mailbox);
    $message = sqimap_get_message($imap_stream, $passed_id, $mailbox);


// --start display setup--

     // From and Date are usually fine as they are...
    $from = decodeHeader($message->header->from);
    $date = getLongDateString($message->header->date);

     // we can clean these up if the list is too long...
    $cc = decodeHeader(getLineOfAddrs($message->header->cc));
    $to = decodeHeader(getLineOfAddrs($message->header->to));

     // and Body and Subject could easily stream off the page...
    $body = mime_fetch_body($imap_stream, $passed_id, $passed_ent_id);
    $body = str_replace("\n", "\n", trim(decodeBody($body, $message->header->encoding)));
    $subject = trim(decodeHeader($message->header->subject));

     // now, if they choose to, we clean up the display a bit...
    if ( empty($pf_cleandisplay) || $pf_cleandisplay != 'no' )
    {

        $num_leading_spaces = 9; // nine leading spaces for indentation

         // sometimes I see ',,' instead of ',' seperating addresses *shrug*
        $cc = pf_clean_string(str_replace(',,', ',', $cc), $num_leading_spaces);
        $to = pf_clean_string(str_replace(',,', ',', $to), $num_leading_spaces);

         // the body should have no leading zeros
        $body = pf_clean_string($body, 0);

         // clean up everything else...
        $subject = pf_clean_string($subject, $num_leading_spaces);
        $from = pf_clean_string($from, $num_leading_spaces);
        $date = pf_clean_string($date, $num_leading_spaces);

    } // end cleanup

    // --end display setup--


    // --start browser output--
    displayHtmlHeader( _("Printer Friendly"), '', FALSE );

    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\">\n" .
         // headers (we use table becasue translations are not all the same width)
         '<table>'.
         '<tr><td>' . _("From") . ':</td><td>' . htmlentities($from) . "</td></td>\n".
         '<tr><td>' . _("To") . ':</td><td>' . htmlentities($to) . "</td></td>\n";
    if ( strlen($cc) > 0 ) { // only show CC: if it's there...
         echo '<tr><td>' . _("CC") . ':</td><td>' . htmlentities($cc) . "</td></td>\n";
    }
    echo '<tr><td>' . _("Date") . ':</td><td>' . htmlentities($date) . "</td></td>\n".
         '<tr><td>' . _("Subject") . ':</td><td>' . htmlentities($subject) . "</td></td>\n".
         '</table>'.
         "\n<pre>";


     // body
    echo "<hr noshade size=1>\n";
    echo htmlentities($body);

// --end browser output--


?></pre>
  </body>
</html>

<?

// --start pf-specific functions--


 // $string = pf_clean_string($string, 9);
function pf_clean_string ( $unclean_string, $num_leading_spaces ) {
    global $data_dir, $username;

    $wrap_at = getPref($data_dir, $username, 'wrap_at');
    $wrap_at = $wrap_at - $num_leading_spaces; // header stuff

    $leading_spaces = '';
    while ( strlen($leading_spaces) < $num_leading_spaces )
        $leading_spaces .= ' ';

    $clean_string = '';
    while ( strlen($unclean_string) > $wrap_at )
    {
        $this_line = substr($unclean_string, 0, $wrap_at);
        if ( strrpos( $this_line, "\n" ) ) // this should NEVER happen with anything but the $body
        {
            $clean_string .= substr( $this_line, 0, strrpos( $this_line, "\n" ));
            $clean_string .= $leading_spaces;
            $unclean_string = substr($unclean_string, strrpos( $this_line, "\n" ));
        }
        else
        {
            $clean_string .= substr( $this_line, 0, strrpos( $this_line, ' ' ));
            $clean_string .= "\n" . $leading_spaces;
            $unclean_string = substr($unclean_string, (1+strrpos( $this_line, ' ' )));
        }
    }
    $clean_string .= $unclean_string;

    return $clean_string;
} // end pf_clean_string() function

// --end pf-specific functions

?>
