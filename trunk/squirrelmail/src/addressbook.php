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
require_once('../functions/html.php');

/* Make an input field */
function adressbook_inp_field($label, $field, $name, $size, $values, $add) {
    global $color;
    $td_str = '<INPUT NAME="' . $name . '[' . $field . ']" SIZE="' . $size . '" VALUE="';
    if (isset($values[$field])) {
        $td_str .= htmlspecialchars($values[$field]);
    }
    $td_str .= '">' . $add . '';
    return html_tag( 'tr' ,
        html_tag( 'td', $label . ':', 'right', $color[4]) .
        html_tag( 'td', $td_str, 'left', $color[4])
        )
    . "\n";
}

/* Output form to add and modify address data */
function address_form($name, $submittext, $values = array()) {
    global $color;
    echo html_tag( 'table',
                       adressbook_inp_field(_("Nickname"),     'nickname', $name, 15, $values,
                           '<SMALL>' . _("Must be unique") . '</SMALL>') .
                       adressbook_inp_field(_("E-mail address"),  'email', $name, 45, $values, '') .
                       adressbook_inp_field(_("First name"),  'firstname', $name, 45, $values, '') .
                       adressbook_inp_field(_("Last name"),    'lastname', $name, 45, $values, '') .
                       adressbook_inp_field(_("Additional info"), 'label', $name, 45, $values, '') .
                       html_tag( 'tr',
                           html_tag( 'td',
                                       '<INPUT TYPE=submit NAME="' . $name . '[SUBMIT]" VALUE="' .
                                       $submittext . '">',
                                   'center', $color[4], 'colspan="2"')
                       )
    , 'center', '', 'border="0" cellpadding="1" cols="2" width="90%"') ."\n";
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
                             html_tag( 'table',
                                html_tag( 'tr',
                                   html_tag( 'td',
                                      "\n". '<strong>' . _("Update address") . '</strong>' ."\n",
                                      'center', $color[0] )
                                   ),
                             'center', '', 'width="100%" cols="1"' ) .
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
                             echo html_tag( 'table',
                                html_tag( 'tr',
                                   html_tag( 'td',
                                      "\n". '<br><strong><font color="' . $color[2] .
                                      '">' . _("ERROR") . ': ' . $abook->error . '</font></strong>' ."\n",
                                      'center' )
                                   ),
                             'center', '', 'width="100%" cols="1"' );

                            /* Display the "new address" form again */
                            echo '<FORM ACTION="' . $PHP_SELF .
                                 '" METHOD="POST">' . "\n" .
                                 html_tag( 'table',
                                     html_tag( 'tr',
                                         html_tag( 'td',
                                                    "\n". '<br><strong>' . _("Update address") . '</strong>' ."\n",
                                         'center', $color[0] )
                                     ),
                                 'center', '', 'width="100%" cols="1"' ) .
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
    echo html_tag( 'table',
        html_tag( 'tr',
            html_tag( 'td',
                   "\n". '<br><strong><font color="' . $color[2] .
                   '">' . _("ERROR") . ': ' . $formerror . '</font></strong>' ."\n",
            'center' )
        ),
    'center', '', 'width="100%" cols="1"' );
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

    echo html_tag( 'p', '<a href="#AddAddress">' . _("Add address") . '</a>', 'center' ) . "\n";

    /* List addresses */
    if (count($alist) > 0) {
        echo '<FORM ACTION="' . $PHP_SELF . '" METHOD="POST">' . "\n";
        while(list($undef,$row) = each($alist)) {
    
            /* New table header for each backend */
            if($prevbackend != $row['backend']) {
                if($prevbackend < 0) {
                    echo html_tag( 'table',
                                    html_tag( 'tr',
                                          html_tag( 'td',
                                                     '<INPUT TYPE=submit NAME=editaddr VALUE="' . 
                                                     _("Edit selected") . "\">\n" .
                                                     '<INPUT TYPE=submit NAME=deladdr VALUE="' .
                                                     _("Delete selected") . "\">\n",
                                          'center', '', 'colspan="5"' )
                                    ) .
                                    html_tag( 'tr',
                                          html_tag( 'td', '&nbsp;<br>', 'center', '', 'colspan="5"' )
                                    ) ,
                             'center' );
                }
    
                echo html_tag( 'table',
                                html_tag( 'tr',
                                    html_tag( 'td', "\n" . '<strong>' . $row['source'] . '</strong>' . "\n", 'center', $color[0] )
                                ) ,
                        'center', '', 'width="95%" cols="1"' ) ."\n"
                . html_tag( 'table', '', 'center', '', 'cols="5" border="0" cellpadding="1" cellspacing="0" width="90%"' ) .
                      html_tag( 'tr', "\n" .
                          html_tag( 'th', '&nbsp;', 'left', '', 'width="1%"' ) .
                          html_tag( 'th', _("Nickname"), 'left', '', 'width="1%"' ) .
                          html_tag( 'th', _("Name"), 'left', '', 'width="1%"' ) .
                          html_tag( 'th', _("E-mail"), 'left', '', 'width="1%"' ) .
                          html_tag( 'th', _("Info"), 'left', '', 'width="1%"' ) ,
                      '', $color[9] ) . "\n";
    
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
            $tr_bgcolor = '';
            if ($line % 2) { $tr_bgcolor = $color[0]; }
            echo html_tag( 'tr', '') .
            html_tag( 'td',
                '<SMALL>' .
                '<INPUT TYPE=checkbox ' . $selected . ' NAME="sel[]" VALUE="' .
                $row['backend'] . ':' . $row['nickname'] . '"></SMALL>' ,
                'center', '', 'valign="top" width="1%"' ) .
            html_tag( 'td', '&nbsp;' . $row['nickname'] . '&nbsp;', 'left', '', 'valign="top" width="1%" nowrap' ) .
            html_tag( 'td', '&nbsp;' . $row['name'] . '&nbsp;', 'left', '', 'valign="top" width="1%" nowrap' ) .
            html_tag( 'td', '', 'left', '', 'valign="top" width="1%" nowrap' ) . '&nbsp;';
            $email = $abook->full_address($row);
            if ($compose_new_win == '1') {
                echo '<a href="javascript:void(0)" onclick=comp_in_new(false,"compose.php?send_to='.rawurlencode($email).'")>';
            }
            else {
                echo '<A HREF="compose.php?send_to=' . rawurlencode($email).'">';
            }
            echo $row['email'] . '</A>&nbsp;</td>'."\n".
            html_tag( 'td', '&nbsp;' . $row['label'] . '&nbsp;', 'left', '', 'valign="top" width="1%"' ) .
            "</tr>\n";
            $line++;
        }
    
        /* End of list. Close table. */
        if ($headerprinted) {
            echo html_tag( 'tr',
                        html_tag( 'td',
                                '<INPUT TYPE="submit" NAME="editaddr" VALUE="' . _("Edit selected") .
                                "\">\n" .
                                '<INPUT TYPE="submit" NAME="deladdr" VALUE="' . _("Delete selected") .
                                "\">\n",
                         'center', '', 'colspan="5"' )
                    );
        }
        echo '</table></FORM>';
    }
} /* end of addresslist */


/* Display the "new address" form */
echo '<a name="AddAddress"></a>' . "\n" .
    '<FORM ACTION="' . $PHP_SELF . '" NAME=f_add METHOD="POST">' . "\n" .
    html_tag( 'table',
        html_tag( 'tr',
            html_tag( 'td', "\n". '<strong>' . sprintf(_("Add to %s"), $abook->localbackendname) . '</strong>' . "\n",
                'center', $color[0]
            )
        )
    , 'center', '', 'width="100%" cols="1"' ) ."\n";
address_form('addaddr', _("Add address"), $defdata);
echo '</FORM>';

/* Add hook for anything that wants on the bottom */
do_hook('addressbook_bottom');
?>

</BODY></HTML>
