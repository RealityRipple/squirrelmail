<?php

/**
 * addressbook.php
 *
 * Copyright (c) 1999-2001 The Squirrelmail Development Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Manage personal address book.
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
require_once('../functions/array.php');
require_once('../functions/display_messages.php');
require_once('../functions/addressbook.php');

    // Sort array by the key "name"
    function alistcmp($a,$b) {
        if($a['backend'] > $b['backend'])
            return 1;
        else if($a['backend'] < $b['backend'])
            return -1;

        return (strtolower($a['name']) > strtolower($b['name'])) ? 1 : -1;
    }

    // Output form to add and modify address data
    function address_form($name, $submittext, $values = array()) {
        global $color;
        echo "<TABLE BORDER=0 CELLPADDING=1 COLS=2 WIDTH=\"90%\" ALIGN=center>\n" .
             "<TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>" .
             _("Nickname") . ' : </TD>'.
             "<TD BGCOLOR=\"$color[4]\" ALIGN=left>".
             "<INPUT NAME=\"$name[nickname]\" SIZE=15 VALUE=\"";
        if (isset($values['nickname']))
            echo htmlspecialchars($values['nickname']);
        echo '">'.
            "&nbsp;<SMALL>" . _("Must be unique") . "</SMALL></TD></TR>\n";
        printf("<TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
            _("E-mail address"));
        printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
            "<INPUT NAME=\"%s[email]\" SIZE=45 VALUE=\"%s\"></TD></TR>\n",
            $color[4], $name,
            (isset($values["email"]))?
                htmlspecialchars($values["email"]):"");
        printf("<TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
            _("First name"));
        printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
            "<INPUT NAME=\"%s[firstname]\" SIZE=45 VALUE=\"%s\"></TD></TR>\n",
            $color[4], $name,
            (isset($values["firstname"]))?
                htmlspecialchars($values["firstname"]):"");
        printf("<TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
            _("Last name"));
        printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
            "<INPUT NAME=\"%s[lastname]\" SIZE=45 VALUE=\"%s\"></TD></TR>\n",
            $color[4], $name,
            (isset($values["lastname"]))?
                htmlspecialchars($values["lastname"]):"");
        printf("<TR><TD BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
            _("Additional info"));
        printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
            "<INPUT NAME=\"%s[label]\" SIZE=45 VALUE=\"%s\"></TD></TR>\n",
            $color[4], $name,
            (isset($values["label"]))?
                htmlspecialchars($values["label"]):"");

        printf("<TR><TD COLSPAN=2 BGCOLOR=\"%s\" ALIGN=center>\n".
            "<INPUT TYPE=submit NAME=\"%s[SUBMIT]\" VALUE=\"%s\"></TD></TR>\n",
            $color[4], $name, $submittext);

        print "</TABLE>\n";
    }


    // Open addressbook, with error messages on but without LDAP (the
    // second "true"). Don't need LDAP here anyway
    $abook = addressbook_init(true, true);
    if($abook->localbackend == 0) {
        plain_error_message(_("No personal address book is defined. Contact administrator."), $color);
        exit();
    }

    displayPageHeader($color, 'None');


    $defdata   = array();
    $formerror = '';
    $abortform = false;
    $showaddrlist = true;
    $defselected  = array();


    // Handle user's actions
    if($REQUEST_METHOD == 'POST') {

        // ***********************************************
        // Add new address
        // ***********************************************
        if(!empty($addaddr['nickname'])) {

        $r = $abook->add($addaddr, $abook->localbackend);

        // Handle error messages
        if(!$r) {
            // Remove backend name from error string
            $errstr = $abook->error;
            $errstr = ereg_replace('^\[.*\] *', '', $errstr);

            $formerror = $errstr;
            $showaddrlist = false;
            $defdata = $addaddr;
        }

        }


        // ***********************************************
        // Delete address(es)
        // ***********************************************
        else if((!empty($deladdr)) &&
            sizeof($sel) > 0) {
        $orig_sel = $sel;
        sort($sel);

        // The selected addresses are identidied by "backend:nickname".
        // Sort the list and process one backend at the time
        $prevback  = -1;
        $subsel    = array();
        $delfailed = false;

        for($i = 0 ; (($i < sizeof($sel)) && !$delfailed) ; $i++) {
            list($sbackend, $snick) = explode(':', $sel[$i]);

            // When we get to a new backend, process addresses in
            // previous one.
            if($prevback != $sbackend && $prevback != -1) {

            $r = $abook->remove($subsel, $prevback);
            if(!$r) {
            $formerror = $abook->error;
            $i = sizeof($sel);
            $delfailed = true;
            break;
            }
            $subsel   = array();
            }

            // Queue for processing
            array_push($subsel, $snick);
            $prevback = $sbackend;
        }

        if(!$delfailed) {
            $r = $abook->remove($subsel, $prevback);
            if(!$r) { // Handle errors
            $formerror = $abook->error;
            $delfailed = true;
            }
        }

        if($delfailed) {
            $showaddrlist = true;
            $defselected  = $orig_sel;
        }
        }


        // ***********************************************
        // Update/modify address
        // ***********************************************
        else if(!empty($editaddr)) {

        // Stage one: Copy data into form
            if (isset($sel) && sizeof($sel) > 0) {
            if(sizeof($sel) > 1) {
            $formerror = _("You can only edit one address at the time");
            $showaddrlist = true;
            $defselected = $sel;
            } else {
            $abortform = true;
            list($ebackend, $enick) = explode(':', $sel[0]);
            $olddata = $abook->lookup($enick, $ebackend);

            // Display the "new address" form
            print "<FORM ACTION=\"$PHP_SELF\" METHOD=\"POST\">\n";
            print "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
            print "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n<STRONG>";
            print _("Update address");
            print "<STRONG>\n</TD></TR>\n";
            print "</TABLE>\n";
            address_form("editaddr", _("Update address"), $olddata);
            printf("<INPUT TYPE=hidden NAME=oldnick VALUE=\"%s\">\n",
                htmlspecialchars($olddata["nickname"]));
            printf("<INPUT TYPE=hidden NAME=backend VALUE=\"%s\">\n",
                htmlspecialchars($olddata["backend"]));
            print "<INPUT TYPE=hidden NAME=doedit VALUE=1>\n";
            print '</FORM>';
            }
        }

        // Stage two: Write new data
        else if($doedit = 1) {
            $newdata = $editaddr;
            $r = $abook->modify($oldnick, $newdata, $backend);

            // Handle error messages
            if(!$r) {
            // Display error
            print "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
            print "<TR><TD ALIGN=CENTER>\n<br><STRONG>";
            print "<FONT COLOR=\"$color[2]\">"._("ERROR").": ".
                $abook->error."</FONT>";
            print "<STRONG>\n</TD></TR>\n";
            print "</TABLE>\n";

            // Display the "new address" form again
            printf("<FORM ACTION=\"%s\" METHOD=\"POST\">\n", $PHP_SELF);
            print "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
            print "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n<STRONG>";
            print _("Update address");
            print "<STRONG>\n</TD></TR>\n";
            print "</TABLE>\n";
            address_form("editaddr", _("Update address"), $newdata);
            printf("<INPUT TYPE=hidden NAME=oldnick VALUE=\"%s\">\n",
                htmlspecialchars($oldnick));
            printf("<INPUT TYPE=hidden NAME=backend VALUE=\"%s\">\n",
                htmlspecialchars($backend));
            print "<INPUT TYPE=hidden NAME=doedit VALUE=1>\n";
            print '</FORM>';

            $abortform = true;
            }
        }

        // Should not get here...
        else {
            plain_error_message(_("Unknown error"), $color);
            $abortform = true;
        }
        } // End of edit address



        // Some times we end output before forms are printed
        if($abortform) {
        print "</BODY></HTML>\n";
        exit();
        }
    }


    // ===================================================================
    // The following is only executed on a GET request, or on a POST when
    // a user is added, or when "delete" or "modify" was successful.
    // ===================================================================

    // Display error messages
    if(!empty($formerror)) {
        print "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
        print "<TR><TD ALIGN=CENTER>\n<br><STRONG>";
        print "<FONT COLOR=\"$color[2]\">"._("ERROR").": $formerror</FONT>";
        print "<STRONG>\n</TD></TR>\n";
        print "</TABLE>\n";
    }


    // Display the address management part
    if($showaddrlist) {
        // Get and sort address list
        $alist = $abook->list_addr();
        if(!is_array($alist)) {
        plain_error_message($abook->error, $color);
        exit;
        }

        usort($alist,'alistcmp');
        $prevbackend = -1;
        $headerprinted = false;

        echo "<p align=center><a href=\"#AddAddress\">" .
            _("Add address") . "</a></p>\n";

        // List addresses
        printf("<FORM ACTION=\"%s\" METHOD=\"POST\">\n", $PHP_SELF);
        while(list($undef,$row) = each($alist)) {

        // New table header for each backend
        if($prevbackend != $row["backend"]) {
            if($prevbackend >= 0) {
            print "<TR><TD COLSPAN=5 ALIGN=center>\n";
            printf("<INPUT TYPE=submit NAME=editaddr VALUE=\"%s\">\n",
                _("Edit selected"));
            printf("<INPUT TYPE=submit NAME=deladdr VALUE=\"%s\">\n",
                _("Delete selected"));
            echo "</tr>\n";
            print '<TR><TD COLSPAN="5" ALIGN=center>';
            print "&nbsp;<BR></TD></TR></TABLE>\n";
            }

            print "<TABLE WIDTH=\"95%\" COLS=1 ALIGN=CENTER>\n";
            print "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n<STRONG>";
            print $row["source"];
            print "<STRONG>\n</TD></TR>\n";
            print "</TABLE>\n";

            print '<TABLE COLS="5" BORDER="0" CELLPADDING="1" CELLSPACING="0" WIDTH="90%" ALIGN="center">';
            printf('<TR BGCOLOR="%s"><TH ALIGN=left WIDTH="%s">&nbsp;'.
            '<TH ALIGN=left WIDTH="%s">%s<TH ALIGN=left WIDTH="%s">%s'.
            '<TH ALIGN=left WIDTH="%s">%s<TH ALIGN=left WIDTH="%s">%s'.
            "</TR>\n", $color[9], "1%",
            "1%", _("Nickname"),
            "1%", _("Name"),
            "1%", _("E-mail"),
            "%",  _("Info"));
            $line = 0;
            $headerprinted = true;
        } // End of header

        $prevbackend = $row['backend'];

        // Check if this user is selected
        if(in_array($row['backend'].':'.$row['nickname'], $defselected))
            $selected = 'CHECKED';
        else
            $selected = '';

        // Print one row
        printf("<TR%s>",
            (($line % 2) ? " bgcolor=\"$color[0]\"" : ""));
        print  '<TD VALIGN=top ALIGN=center WIDTH="1%"><SMALL>';
        printf('<INPUT TYPE=checkbox %s NAME="sel[]" VALUE="%s:%s"></SMALL></TD>',
            $selected, $row["backend"], $row["nickname"]);
        printf('<TD VALIGN=top NOWRAP WIDTH="%s">&nbsp;%s&nbsp;</TD>'.
            '<TD VALIGN=top NOWRAP WIDTH="%s">&nbsp;%s&nbsp;</TD>',
            "1%", $row["nickname"],
            "1%", $row["name"]);
        printf('<TD VALIGN=top NOWRAP WIDTH="%s">&nbsp;<A HREF="compose.php?send_to=%s">%s</A>&nbsp;</TD>'."\n",
            "1%", rawurlencode($row["email"]), $row["email"]);
        printf('<TD VALIGN=top WIDTH="%s">&nbsp;%s&nbsp;</TD>',
            "%", $row["label"]);
        print "</TR>\n";
        $line++;
        }

        // End of list. Close table.
        if($headerprinted) {
        print "<TR><TD COLSPAN=5 ALIGN=center>\n";
        printf("<INPUT TYPE=submit NAME=editaddr VALUE=\"%s\">\n",
            _("Edit selected"));
        printf("<INPUT TYPE=submit NAME=deladdr VALUE=\"%s\">\n",
            _("Delete selected"));
        print "</TR></TABLE></FORM>";
        }
    } // end of addresslist


    // Display the "new address" form
    echo "<a name=\"AddAddress\"></a>\n" .
         "<FORM ACTION=\"$PHP_SELF\" NAME=f_add METHOD=\"POST\">\n".
         "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n".
         "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n<STRONG>";
    printf(_("Add to %s"), $abook->localbackendname);
    echo "<STRONG>\n</TD></TR>\n".
         "</TABLE>\n";
    address_form('addaddr', _("Add address"), $defdata);
    echo '</FORM>';

    // Add hook for anything that wants on the bottom
    do_hook('addressbook_bottom');
?>

</BODY></HTML>
