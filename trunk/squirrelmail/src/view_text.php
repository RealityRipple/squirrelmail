<?php

   require_once('../src/validate.php');
   require_once('../functions/imap.php');
   require_once('../functions/mime.php');
   require_once('../functions/html.php');
   
   $mailbox = urldecode($mailbox);
   if (!isset($passed_ent_id)) {
      $passed_ent_id = '';
   }

   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   $mbx_response =  sqimap_mailbox_select($imapConnection, $mailbox);

   $message = &$messages[$mbx_response['UIDVALIDITY']]["$passed_id"];
   $message_ent = &$message->getEntity($ent_id);
   if ($passed_ent_id) {
      $message = &$message->getEntity($passed_ent_id);
   }
   
   $header = $message_ent->header;
   $charset = $header->getParameter('charset');
   $type0 = $header->type0;
   $type1 = $header->type1;
   $encoding = strtolower($header->encoding);

   $msg_url = 'read_body.php?' . $QUERY_STRING;
   $msg_url = set_url_var($msg_url, 'ent_id', 0);

   $body = mime_fetch_body($imapConnection, $passed_id, $ent_id);
   $body = decodeBody($body, $encoding);

    displayPageHeader($color, 'None');

    echo "<BR><TABLE WIDTH=\"100%\" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">".
         "<B><CENTER>".
         _("Viewing a text attachment") . " - ";
    echo '<a href="'.$msg_url.'">'. _("View message") . '</a>';

    $dwnld_url = '../src/download.php?'. $QUERY_STRING.'&amp;absolute_dl=true';
    echo '</b></td><tr><tr><td><CENTER><A HREF="'.$dwnld_url. '">'.
         _("Download this as a file").
         "</A></CENTER><BR>".
         "</CENTER></B>".
         "</TD></TR></TABLE>".
         "<TABLE WIDTH=\"98%\" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">".
         "<TR><TD BGCOLOR=\"$color[4]\"><TT>";
    if ($type1 == 'html' || $override_type1 == 'html') {
        $body = MagicHTML( $body, $passed_id, $message, $mailbox);
    } else {
        translateText($body, $wrap_at, $charset);
    }
    echo $body .
         "</TT></TD></TR></TABLE>";
?>
