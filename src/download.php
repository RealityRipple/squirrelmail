<?php

/**
 * download.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Handles attachment downloads to the users computer.
 * Also allows displaying of attachments when possible.
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/imap.php');
require_once('../functions/mime.php');
require_once('../functions/date.php');

header('Pragma: ');
header('Cache-Control: cache');

function viewText($color, $body, $id, $entid, $mailbox, $type1, $wrap_at) {
    global $where, $what, $charset;
    global $startMessage;

    displayPageHeader($color, 'None');

    echo "<BR><TABLE WIDTH=\"100%\" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">".
         "<B><CENTER>".
         _("Viewing a text attachment") . " - ";
    if ($where && $what) {
        // from a search
        echo "<a href=\"read_body.php?mailbox=".urlencode($mailbox)."&passed_id=$id&where=".urlencode($where)."&what=".urlencode($what)."\">". _("View message") . "</a>";
    } else {
        echo "<a href=\"read_body.php?mailbox=".urlencode($mailbox)."&passed_id=$id&startMessage=$startMessage&show_more=0\">". _("View message") . "</a>";
    }

    $urlmailbox = urlencode($mailbox);
    echo "</b></td><tr><tr><td><CENTER><A HREF=\"../src/download.php?absolute_dl=true&passed_id=$id&passed_ent_id=$entid&mailbox=$urlmailbox\">".
         _("Download this as a file").
         "</A></CENTER><BR>".
         "</CENTER></B>".
         "</TD></TR></TABLE>".
         "<TABLE WIDTH=\"98%\" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">".
         "<TR><TD BGCOLOR=\"$color[4]\"><TT>";

    if ($type1 == 'html') {
        $body = MagicHTML( $body, $id );
    } else {
        translateText($body, $wrap_at, $charset);
    }

    flush();
    echo $body .
         "</TT></TD></TR></TABLE>";
}

function viewMessage($imapConnection, $id, $mailbox, $ent_id, $color, $wrap_at) {
    global $startMessage;


    $msg  = sqimap_get_message($imapConnection, $id, $mailbox);    
    $msg = getEntity($msg, $ent_id);    

    $header = sqimap_get_ent_header($imapConnection,$id,$mailbox,$ent_id);
    $msg->header = $header;
    $msg->header->id = $id;
    $body = formatBody($imapConnection, $msg, $color, $wrap_at);
    $bodyheader = viewHeader($header, $color);
    displayPageHeader($color, 'None');

    echo "<BR><TABLE WIDTH=\"100%\" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">".
    	"<B><CENTER>". 	_("Viewing a message attachment") . " - ";
    
    echo "<a href=\"read_body.php?mailbox=".urlencode($mailbox)."&passed_id=$id&startMessage=$startMessage&show_more=0\">". _("View message") . "</a>";
    
    $urlmailbox = urlencode($mailbox);
    
    echo "</b></td><tr><tr><td><CENTER><A HREF=\"../src/download.php?absolute_dl=true&passed_id=$id&passed_ent_id=$ent_id&mailbox=$urlmailbox\">".
    	_("Download this as a file").
    	"</A></CENTER><BR>".
    	"</CENTER></B>".
    	"</TD></TR></TABLE>";
    echo "<TABLE WIDTH=\"100%\" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">".
    	"<TR><TD BGCOLOR=\"$color[4]\">";
    echo "$bodyheader </TD></TR></TABLE>";	     
    	
    echo "<TABLE WIDTH=\"98%\" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">".
    	"<TR><TD BGCOLOR=\"$color[4]\"><TT>";
	echo "$body </TT></TD></TR></TABLE>";	 
}


function viewHeader($header,$color) {

    $bodyheader = '';

    /** FORMAT THE FROM STRING **/
    $from_name = decodeHeader(htmlspecialchars($header->from));
    if(isset($from_name) && $from_name !='') {    
	$bodyheader .= makeTableEntry($from_name,_("From"), $color);    
    }
    
    $subject_string = decodeHeader(htmlspecialchars($header->subject));    
    if(isset($subject_string) && $subject_string !='') {        
	$bodyheader .= makeTableEntry($subject_string,_("Subject:"), $color);    
    } 
    /** FORMAT THE TO STRING **/
    $to = formatRecipientString($header->to, "to");
    $to_string = $to['str'];
    $url_to_string = $to['url_str'];
    if(isset($to_string) && $to_string !='') {
	$bodyheader .= makeTableEntry($to_string,_("To:"), $color);
    }

    /** FORMAT THE DATE STRING **/    
    $dateString = getLongDateString($header->date);
    if(isset($dateString) && $dateString !='') {            
	$bodyheader .= makeTableEntry($dateString,_("Date:"), $color);    
    }
    
    /** FORMAT THE CC STRING **/
    $cc = formatRecipientString($header->cc, "cc");
    $cc_string = $cc['str'];
    $url_cc_string = $cc['url_str'];
    if(isset($cc_string) && $cc_string !='') {    
	$bodyheader .= makeTableEntry($cc_string,_("Cc:"), $color);    	
    }
    
    /** FORMAT THE BCC STRING **/
    $bcc = formatRecipientString($header->bcc, "bcc");
    $bcc_string = $bcc['str'];
    $url_bcc_string = $bcc['url_str'];
    if(isset($bcc_string) && $bcc_string !='') {    
	$bodyheader .= makeTableEntry($bcc_string,_("Bcc:"), $color);
    }
    
    return $bodyheader;
}

function makeTableEntry($str, $str_name, $color) {
    $entry = '<tr><td bgcolor="'."$color[0]".'" align right valign top>'."$str_name".'</td><td bgcolor="'."$color[0]".
	     '" valign top colspan=2><b>'."$str".'</b>&nbsp;</td></tr>'."\n";
    return $entry;
}

function formatRecipientString($recipients, $item ) {
    global $base_uri, $passed_id, $startMessage, $show_more_cc, $show_more, $show_more_bcc, $passed_ent_id;
    global $where, $what, $mailbox, $sort;

    /** TEXT STRINGS DEFINITIONS **/
    $echo_more = _("more");
    $echo_less = _("less");

    if (!isset($show_more_cc)) {
	$show_more_cc = FALSE;
    }
    if (!isset($show_more_bcc)) {
	$show_more_bcc = FALSE;
    }


    $urlMailbox = urlencode($mailbox);
    $i = 0;
    $url_string = '';
    
    if (isset ($recipients[0]) && trim($recipients[0])) {
	$string = '';
        $ary = explode(",",$recipients[0]);

	switch ($item) {
	    case 'to':
		$show = "&amp;show_more=1&amp;show_more_cc=$show_more_cc&amp;show_more_bcc=$show_more_bcc";
		$show_n = "&amp;show_more=0&amp;show_more_cc=$show_more_cc&amp;show_more_bcc=$show_more_bcc";
		break;
	    case 'cc':
		$show = "&amp;show_more=$show_more&amp;show_more_cc=1&amp;show_more_bcc=$show_more_bcc";
		$show_n = "&amp;show_more=$show_more&amp;show_more_cc=0&amp;show_more_bcc=$show_more_bcc";
		$show_more = $show_more_cc;
		break;
	    case 'bcc':
		$show = "&amp;show_more=$show_more&amp;show_more_cc=$show_more_cc&amp;show_more_bcc=1";
		$show_n = "&amp;show_more=$show_more&amp;show_more_cc=$show_more_cc&amp;show_more_bcc=0";
		$show_more = $show_more_bcc;
		break;
	    default:
		$break;
	}

	while ($i < count($ary)) {
    	    $ary[$i] = htmlspecialchars(decodeHeader($ary[$i]));
    	    $url_string .= $ary[$i];
    	    if ($string) {
        	$string = "$string<BR>$ary[$i]";
    	    } else {
        	$string = "$ary[$i]";
    	    }

    	    $i++;
    	    if (count($ary) > 1) {
        	if ($show_more == false) {
            	    if ($i == 1) {

                	$string .= '&nbsp;(<A HREF="' . $base_uri .
                                   "src/download.php?mailbox=$urlMailbox&amp;passed_id=$passed_id&amp;";
                	if (isset($where) && isset($what)) {
                    	    $string .= 'what=' . urlencode($what)."&amp;where=".urlencode($where)."&amp;passed_ent_id=$passed_ent_id$show\">$echo_more</A>)";
                	} else {
                    	    $string .= "sort=$sort&amp;startMessage=$startMessage"."&amp;passed_ent_id=$passed_ent_id$show\">$echo_more</A>)";
                	}
                	$i = count($ary);
            	    }
        	} else if ($i == 1) {

            	    $string .= '&nbsp;(<A HREF="' . $base_uri .
                               "src/download.php?mailbox=$urlMailbox&amp;passed_id=$passed_id&amp;";
            	    if (isset($where) && isset($what)) {
                	$string .= 'what=' . urlencode($what)."&amp;where=".urlencode($where)."&amp;passed_ent_id=$passed_ent_id$show_n\">$echo_less</A>)";
            	    } else {
                	$string .= "sort=$sort&amp;startMessage=$startMessage"."&amp;passed_ent_id=$passed_ent_id$show_n\">$echo_less</A>)";
            	    }
        	}
    	    }

	}
    }
    else {
	$string = '';
    }
    $url_string = urlencode($url_string);
    $result = array();
    $result['str'] = $string;
    $result['url_str'] = $url_string;
    return $result;
    
}


$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
sqimap_mailbox_select($imapConnection, $mailbox);

if (isset($showHeaders)) {
  $top_header = sqimap_get_message_header ($imapConnection, $passed_id, $mailbox);
}
/*
 * lets redefine message as this particular entity that we wish to display.
 * it should hold only the header for this entity.  We need to fetch the body
 * yet before we can display anything.
 */

$header = sqimap_get_mime_ent_header ($imapConnection, $passed_id, $mailbox, $passed_ent_id);
$header->entity_id = $passed_ent_id;
$header->mailbox = $mailbox;

$charset = $header->charset;
$type0 = $header->type0;
$type1 = $header->type1;
if (isset($override_type0)) {
    $type0 = $override_type0;
}
if (isset($override_type1)) {
    $type1 = $override_type1;
}
$filename = decodeHeader($header->filename);
if (!$filename) {
    $filename = decodeHeader($header->name);
}

if (strlen($filename) < 1) {
    if ($type1 == 'plain' && $type0 == 'text') {
        $suffix = 'txt';
    } else if ($type1 == 'richtext' && $type0 == 'text') {
        $suffix = 'rtf';
    } else if ($type1 == 'postscript' && $type0 == 'application') {
        $suffix = 'ps';
    } else if ($type1 == 'rfc822' && $type0 == 'message') {
        $suffix = 'eml';
    } else {
        $suffix = $type1;
    }

    $filename = "untitled$passed_ent_id.$suffix";
}


/*
 * Note:
 *    The following sections display the attachment in different
 *    ways depending on how they choose.  The first way will download
 *    under any circumstance.  This sets the Content-type to be
 *    applicatin/octet-stream, which should be interpreted by the
 *    browser as "download me".
 *      The second method (view) is used for images or other formats
 *    that should be able to be handled by the browser.  It will
 *    most likely display the attachment inline inside the browser.
 *      And finally, the third one will be used by default.  If it
 *    is displayable (text or html), it will load them up in a text
 *    viewer (built in to squirrelmail).  Otherwise, it sets the
 *    content-type as application/octet-stream
 */
if (isset($absolute_dl) && $absolute_dl == 'true') {
    switch($type0) {
    case 'text':
        DumpHeaders($type0, $type1, $filename, 1);
        $body = mime_fetch_body($imapConnection, $passed_id, $passed_ent_id);
        $body = decodeBody($body, $header->encoding);
        if ($type1 == 'plain' && isset($showHeaders)) {
            echo _("Subject") . ": " . decodeHeader($top_header->subject) . "\n".
                 "   " . _("From") . ": " . decodeHeader($top_header->from) . "\n".
                 "     " . _("To") . ": " . decodeHeader(getLineOfAddrs($top_header->to)) . "\n".
                 "   " . _("Date") . ": " . getLongDateString($top_header->date) . "\n\n";
        } elseif ($type1 == 'html' && isset($showHeaders)) {
            echo '<table><tr><th align=right>' . _("Subject").
                 ':</th><td>' . decodeHeader($top_header->subject).
                 "</td></tr>\n<tr><th align=right>" . _("From").
                 ':</th><td>' . decodeHeader($top_header->from).
                 "</td></tr>\n<tr><th align=right>" . _("To").
                 ':</th><td>' . decodeHeader(getLineOfAddrs($top_header->to)).
                 "</td></tr>\n<tr><th align=right>" . _("Date").
                 ':</th><td>' . getLongDateString($top_header->date).
                 "</td></tr>\n</table>\n<hr>\n";
        } 
        echo $body;
        break;
    
    default:
        DumpHeaders($type0, $type1, $filename, 1);
        mime_print_body_lines ($imapConnection, $passed_id, $passed_ent_id, $header->encoding);
        break;
    }
} else {
    switch ($type0) {
    case 'text':
        if ($type1 == 'plain' || $type1 == 'html') {
            $body = mime_fetch_body($imapConnection, $passed_id, $passed_ent_id);
            $body = decodeBody($body, $header->encoding);
            viewText($color, $body, $passed_id, $passed_ent_id, $mailbox, $type1, $wrap_at);
        } else {
            DumpHeaders($type0, $type1, $filename, 0);
            $body = mime_fetch_body($imapConnection, $passed_id, $passed_ent_id);
            $body = decodeBody($body, $header->encoding);
            echo $body;
        }
        break;
    case 'message':
	if ($type1 == 'rfc822' ) {
	    viewMessage($imapConnection, $passed_id, $mailbox, $passed_ent_id, $color, $wrap_at);
	} else {
    	    $body = mime_fetch_body($imapConnection, $passed_id, $passed_ent_id);
    	    $body = decodeBody($body, $msgheader->encoding);
    	    viewText($color, $body, $passed_id, $passed_ent_id, $mailbox, $type1, $wrap_at);
        }
        break;
    default:
        DumpHeaders($type0, $type1, $filename, 0);
        mime_print_body_lines ($imapConnection, $passed_id, $passed_ent_id, $header->encoding);
        break;
    }
}


/*
 * This function is verified to work with Netscape and the *very latest*
 * version of IE.  I don't know if it works with Opera, but it should now.
 */
function DumpHeaders($type0, $type1, $filename, $force) {
    global $HTTP_USER_AGENT;

    $isIE = 0;

    if (strstr($HTTP_USER_AGENT, 'compatible; MSIE ') !== false &&
        strstr($HTTP_USER_AGENT, 'Opera') === false) {
        $isIE = 1;
    }

    if (strstr($HTTP_USER_AGENT, 'compatible; MSIE 6') !== false &&
        strstr($HTTP_USER_AGENT, 'Opera') === false) {
        $isIE6 = 1;
    }

    $filename = ereg_replace('[^-a-zA-Z0-9\.]', '_', $filename);

    // A Pox on Microsoft and it's Office!
    if (! $force) {
        // Try to show in browser window
        header("Content-Disposition: inline; filename=\"$filename\"");
        header("Content-Type: $type0/$type1; name=\"$filename\"");
    } else {
        // Try to pop up the "save as" box
        // IE makes this hard.  It pops up 2 save boxes, or none.
        // http://support.microsoft.com/support/kb/articles/Q238/5/88.ASP
        // But, accordint to Microsoft, it is "RFC compliant but doesn't
        // take into account some deviations that allowed within the
        // specification."  Doesn't that mean RFC non-compliant?
        // http://support.microsoft.com/support/kb/articles/Q258/4/52.ASP
        //
        // The best thing you can do for IE is to upgrade to the latest
        // version
        if ($isIE && !isset($isIE6)) {
            // http://support.microsoft.com/support/kb/articles/Q182/3/15.asp
            // Do not have quotes around filename, but that applied to
            // "attachment"... does it apply to inline too?
            //
            // This combination seems to work mostly.  IE 5.5 SP 1 has
            // known issues (see the Microsoft Knowledge Base)
            header("Content-Disposition: inline; filename=$filename");

            // This works for most types, but doesn't work with Word files
            header("Content-Type: application/download; name=\"$filename\"");

            // These are spares, just in case.  :-)
            //header("Content-Type: $type0/$type1; name=\"$filename\"");
            //header("Content-Type: application/x-msdownload; name=\"$filename\"");
            //header("Content-Type: application/octet-stream; name=\"$filename\"");
        } else {
            header("Content-Disposition: attachment; filename=\"$filename\"");
            // application/octet-stream forces download for Netscape
            header("Content-Type: application/octet-stream; name=\"$filename\"");
        }
    }
}
?>
