<?php

/**
 * addressbook.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Manage personal address book.
 *
 * $Id$
 */

require_once('../src/validate.php');
require_once('../functions/array.php');
require_once('../functions/display_messages.php');
require_once('../functions/addressbook.php');
require_once('../functions/strings.php');

/* Make an input field */
function adressbook_inp_field($label, $field, $name, $size, $values, $add) {
    global $color;
    echo '<TR><TD BGCOLOR="' . $color[4] . '" ALIGN=RIGHT>' .
         $label . ':</TD>' .
         '<TD BGCOLOR="' . $color[4] . '" ALIGN=left>' .
         '<INPUT NAME="' . $name . '[' . $field . ']" SIZE="' . $size . '" VALUE="';
    if (isset($values[$field])) {
        echo htmlspecialchars($values[$field]);
    }
    echo '">' . $add . '</TD></TR>' . "\n";
}

/* Output form to add and modify address data */
function address_form($name, $submittext, $values = array()) {
    global $color;

    echo '<TABLE BORDER=0 CELLPADDING=1 COLS=2 WIDTH="90%" ALIGN=center>' ."\n";

    adressbook_inp_field(_("Nickname"),     'nickname', $name, 15, $values,
        '<SMALL>' . _("Must be unique") . '</SMALL>');
    adressbook_inp_field(_("E-mail address"),  'email', $name, 45, $values, '');
    adressbook_inp_field(_("First name"),  'firstname', $name, 45, $values, '');
    adressbook_inp_field(_("Last name"),    'lastname', $name, 45, $values, '');
    adressbook_inp_field(_("Additional info"), 'label', $name, 45, $values, '');

    echo '<TR><TD COLSPAN=2 BGCOLOR="' . $color[4] . '" ALIGN=center>' . "\n" .
         '<INPUT TYPE=submit NAME="' . $name . '[SUBMIT]" VALUE="' .
         $submittext . '"></TD></TR>' .
         "\n</TABLE>\n";
}


/* Open addressbook, with error messages on but without LDAP (the *
 * second "true"). Don't need LDAP here anyway                    */
$abook = addressbook_init(true, true);
if($abook->localbackend == 0) {
    plain_error_message(
        _("No personal address book is defined. Contact administrator."),
        $color);
    exit();
}

displayPageHeader($color, 'None');


$defdata   = array();
$formerror = '';
$abortform = false;
$showaddrlist = true;
$defselected  = array();


/* Handle user's actions */
if($REQUEST_METHOD == 'POST') {

    /**************************************************
     * Add new address                                *
     **************************************************/
    if (!empty($addaddr['nickname'])) {

        $r = $abook->add($addaddr, $abook->localbackend);

        /* Handle error messages */
        if (!$r) {
            /* Remove backend name from error string */
            $errstr = $abook->error;
            $errstr = ereg_replace('^\[.*\] *', '', $errstr);

            $formerror = $errstr;
            $showaddrlist = false;
            $defdata = $addaddr;
        }

    } else {

        /************************************************
         * Delete address(es)                           *
         ************************************************/
        if ((!empty($deladdr)) && sizeof($sel) > 0) {
            $orig_sel = $sel;
            sort($sel);

            /* The selected addresses are identidied by "backend:nickname". *
             * Sort the list and process one backend at the time            */
            $prevback  = -1;
            $subsel    = array();
            $delfailed = false;

            for ($i = 0 ; (($i < sizeof($sel)) && !$delfailed) ; $i++) {
                list($sbackend, $snick) = explode(':', $sel[$i]);

                /* When we get to a new backend, process addresses in *
                 * previous one.                                      */
                if ($prevback != $sbackend && $prevback != -1) {

                    $r = $abook->remove($subsel, $prevback);
                    if (!$r) {
                        $formerror = $abook->error;
                        $i = sizeof($sel);
                        $delfailed = true;
                        break;
                    }
                    $subsel   = array();
                }

                /* Queue for processing */
                array_push($subsel, $snick);
                $prevback = $sbackend;
            }

            if (!$delfailed) {
                $r = $abook->remove($subsel, $prevback);
                if (!$r) { /* Handle errors */
                    $formerror = $abook->error;
                    $delfailed = true;
                }
            }

            if ($delfailed) {
                $showaddrlist = true;
                $defselected  = $orig_sel;
            }

        } else {

            /***********************************************
             * Update/modify address                       *
             ***********************************************/
            if (!empty($editaddr)) {

                /* Stage one: Copy data into form */
                if (isset($sel) && sizeof($sel) > 0) {
                    if(sizeof($sel) > 1) {
                        $formerror = _("You can only edit one address at the time");
                        $showaddrlist = true;
                        $defselected = $sel;
                    } else {
                        $abortform = true;
                        list($ebackend, $enick) = explode(':', $sel[0]);
                        $olddata = $abook->lookup($enick, $ebackend);

                        /* Display the "new address" form */
                        echo '<FORM ACTION="' . $PHP_SELF . '" METHOD="POST">' .
                             "\n" .
                             '<TABLE WIDTH="100%" COLS=1 ALIGN=CENTER>' . "\n" .
                             '<TR><TD BGCOLOR="' . $color[0] .
                             '" ALIGN=CENTER>' . "\n" . '<STRONG>' .
                             _("Update address") .
                             "</STRONG>\n</TD></TR>\n</TABLE>\n";
                        address_form("editaddr", _("Update address"), $olddata);
                        echo '<INPUT TYPE=hidden NAME=oldnick VALUE="' . 
                             htmlspecialchars($olddata["nickname"]) . "\">\n" .
                             '<INPUT TYPE=hidden NAME=backend VALUE="' .
                             htmlspecialchars($olddata["backend"]) . "\">\n" .
                             '<INPUT TYPE=hidden NAME=doedit VALUE=1>' . "\n" .
                             '</FORM>';
                    }
                } else {

                    /* Stage two: Write new data */
                    if ($doedit = 1) {
                        $newdata = $editaddr;
                        $r = $abook->modify($oldnick, $newdata, $backend);

                        /* Handle error messages */
                        if (!$r) {
                            /* Display error */
                            echo '<TABLE WIDTH="100%" COLS=1 ALIGN=CENTER>' .
                                 "\n" . '<TR><TD ALIGN=CENTER>' . "\n" .
                                 '<br><STRONG><FONT COLOR="' . $color[2] .
                                 '">' . _("ERROR") . ": " . $abook->error .
                                 '</FONT></STRONG>' . "\n</TD></TR>\n</TABLE>\n";

                            /* Display the "new address" form again */
                            echo '<FORM ACTION="' . $PHP_SELF .
                                 '" METHOD="POST">' . "\n" .
                                 '<TABLE WIDTH="100%" COLS=1 ALIGN=CENTER>' .
                                 "\n" . '<TR><TD BGCOLOR="' . $color[0] .
                                 '" ALIGN=CENTER>' . "\n" . '<STRONG>' .
                                 _("Update address") .
                                 "</STRONG>\n</TD></TR>\n</TABLE>\n";
                            address_form("editaddr", _("Update address"), $newdata);
                            echo '<INPUT TYPE=hidden NAME=oldnick VALUE="' .
                                 htmlspecialchars($oldnick) . "\">\n" .
                                 '<INPUT TYPE=hidden NAME=backend VALUE="' .
                                 htmlspecialchars($backend) . "\">\n" .
                                 '<INPUT TYPE=hidden NAME=doedit VALUE=1>' .
                                 "\n" . '</FORM>';
                            $abortform = true;
                        }
                    } else {

                        /* Should not get here... */
                        plain_error_message(_("Unknown error"), $color);
                        $abortform = true;
                    }
                }
            } /* !empty($editaddr)                  - Update/modify address */
        } /* (!empty($deladdr)) && sizeof($sel) > 0 - Delete address(es) */
    } /* !empty($addaddr['nickname'])               - Add new address */

    // Some times we end output before forms are printed
    if($abortform) {
       echo "</BODY></HTML>\n";
       exit();
    }
}


/* =================================================================== *
 * The following is only executed on a GET request, or on a POST when  *
 * a user is added, or when "delete" or "modify" was successful.       *
 * =================================================================== */

/* Display error messages */
if (!empty($formerror)) {
    echo '<TABLE WIDTH="100%" COLS=1 ALIGN=CENTER>' . "\n" .
         '<TR><TD ALIGN=CENTER>' . "\n" . '<br><STRONG>' .
         '<FONT COLOR="' . $color[2]. '">' . _("ERROR") . ': ' . $formerror .
         '</FONT></STRONG>' . "\n</TD></TR>\n</TABLE>\n";
}


/* Display the address management part */
if ($showaddrlist) {
    /* Get and sort address list */
    $alist = $abook->list_addr();
    if(!is_array($alist)) {
        plain_error_message($abook->error, $color);
        exit;
    }

    usort($alist,'alistcmp');
    $prevbackend = -1;
    $headerprinted = false;

    echo '<p align=center><a href="#AddAddress">' .
         _("Add address") . "</a></p>\n";

    /* List addresses */
    if (count($alist) > 0) {
        echo '<FORM ACTION="' . $PHP_SELF . '" METHOD="POST">' . "\n";
        while(list($undef,$row) = each($alist)) {
    
            /* New table header for each backend */
            if($prevbackend != $row['backend']) {
                if($prevbackend < 0) {
                    echo '<TR><TD COLSPAN=5 ALIGN=center>' . "\n" .
                         '<INPUT TYPE=submit NAME=editaddr VALUE="' . 
                         _("Edit selected") . "\">\n" .
                         '<INPUT TYPE=submit NAME=deladdr VALUE="' .
                         _("Delete selected") . "\">\n</tr>\n" .
                         '<TR><TD COLSPAN="5" ALIGN=center>' .
                         '&nbsp;<BR></TD></TR></TABLE>' . "\n";
                }
    
                echo '<TABLE WIDTH="95%" COLS=1 ALIGN=CENTER>' . "\n" .
                     '<TR><TD BGCOLOR="' . $color[0] . '" ALIGN=CENTER>' . "\n" .
                     '<STRONG>' . $row['source'] .
                     "</STRONG>\n</TD></TR>\n</TABLE>\n" .
                     '<TABLE COLS="5" BORDER="0" CELLPADDING="1" CELLSPACING="0"' .
                     ' WIDTH="90%" ALIGN="center">' .
                     '<TR BGCOLOR="' . $color[9] .
                     '"><TH ALIGN=left WIDTH="1%">&nbsp;<TH ALIGN=left WIDTH="1%">' .
                     _("Nickname") . '<TH ALIGN=left WIDTH="1%">' . _("Name") .
                     '<TH ALIGN=left WIDTH="1%">' . _("E-mail") .
                     '<TH ALIGN=left WIDTH="%">' . _("Info") . "</TR>\n";
    
                $line = 0;
                $headerprinted = true;
            } /* End of header */
    
            $prevbackend = $row['backend'];
    
            /* Check if this user is selected */
            if(in_array($row['backend'] . ':' . $row['nickname'], $defselected)) {
                $selected = 'CHECKED';
            } else {
                $selected = '';
            }
    
            /* Print one row */
            echo '<TR';
            if ($line % 2) { echo ' bgcolor="' . $color[0]. '"'; }
            echo '><TD VALIGN=top ALIGN=center WIDTH="1%"><SMALL>' .
                 '<INPUT TYPE=checkbox ' . $selected . ' NAME="sel[]" VALUE="' .
                 $row['backend'] . ':' . $row['nickname'] . '"></SMALL></TD>' .
                 '<TD VALIGN=top NOWRAP WIDTH="1%">&nbsp;' . $row['nickname'] .
                 '&nbsp;</TD>' .
                 '<TD VALIGN=top NOWRAP WIDTH="1%">&nbsp;' . $row['name'] .
                 '&nbsp;</TD>',
                 '<TD VALIGN=top NOWRAP WIDTH="1%">&nbsp;' .
                 '<A HREF="compose.php?send_to=' . rawurlencode($row['email']);
                if ($compose_new_win == '1') {
                     echo '" TARGET="compose_window" onClick="comp_in_new()"';
                }
                echo '">' . $row['email'] . '</A>&nbsp;</TD>'."\n",
                 '<TD VALIGN=top WIDTH="1%">&nbsp;' . $row['label'] . '&nbsp;</TD>' .
                 "</TR>\n";
            $line++;
        }
    
        /* End of list. Close table. */
        if ($headerprinted) {
            echo '<TR><TD COLSPAN=5 ALIGN=center>' . "\n" .
                 '<INPUT TYPE=submit NAME=editaddr VALUE="' . _("Edit selected") .
                 "\">\n" .
                 '<INPUT TYPE=submit NAME=deladdr VALUE="' . _("Delete selected") .
                 "\">\n" . '</TR></TABLE>';
        }
        echo '</FORM>';
    }
} /* end of addresslist */


/* Display the "new address" form */
echo '<a name="AddAddress"></a>' . "\n" .
     '<FORM ACTION="' . $PHP_SELF . '" NAME=f_add METHOD="POST">' . "\n" .
     '<TABLE WIDTH="100%" COLS=1 ALIGN=CENTER>' . "\n" .
     '<TR><TD BGCOLOR="' . $color[0] . '" ALIGN=CENTER>' . "\n" . '<STRONG>',
     sprintf(_("Add to %s"), $abook->localbackendname) .
     "</STRONG>\n</TD></TR>\n" .
     "</TABLE>\n";
address_form('addaddr', _("Add address"), $defdata);
echo '</FORM>';

/* Add hook for anything that wants on the bottom */
do_hook('addressbook_bottom');
?>

</BODY></HTML>
