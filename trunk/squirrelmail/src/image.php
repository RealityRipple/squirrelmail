<?php
/**
 ** image.php
 **
 ** This file shows an attached image
 **
 ** $Id$
 **/

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
