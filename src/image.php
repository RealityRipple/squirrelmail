<?php

/**
 * image.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file shows an attached image
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
require_once('../functions/date.php');
require_once('../functions/page_header.php');
require_once('../src/load_prefs.php');

   displayPageHeader($color, 'None');

   echo '<BR>' . 
        '<TABLE WIDTH=100% BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER>' .
        "\n" .
        '<TR><TD BGCOLOR="' . $color[0] . '">' .
        '<B><CENTER>' .
        _("Viewing an image attachment") . " - ";
   if (isset($where) && isset($what)) {
      // from a search
      echo '<a href="../src/read_body.php?mailbox=' . urlencode($mailbox) .
            '&passed_id=' . $passed_id . '&where=' . urlencode($where) . 
            '&what=' . urlencode($what). '">' . _("View message") . '</a>';
   } else {   
      echo '<a href="../src/read_body.php?mailbox=' . urlencode($mailbox) .
           '&passed_id=' . $passed_id . '&startMessage=' . $startMessage .
           '&show_more=0">' . _("View message") . '</a>';
   }   

   $DownloadLink = '../src/download.php?passed_id=' . $passed_id .
                   '&mailbox=' . urlencode($mailbox) . 
                   '&passed_ent_id=' . $passed_ent_id . '&absolute_dl=true';

   echo '</b></td></tr>' . "\n" .
        '<tr><td align=center><A HREF="' . $DownloadLink . '">' .
        _("Download this as a file") .
        '</A></B><BR>&nbsp;' . "\n" .
        '</TD></TR></TABLE>' . "\n" .

        '<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER>' . "\n" .
        '<TR><TD BGCOLOR="' . $color[4] . '">' .
        '<img src="' . $DownloadLink . '">' .

        '</TD></TR></TABLE>' . "\n";
        '</body></html>' . "\n";

?>
