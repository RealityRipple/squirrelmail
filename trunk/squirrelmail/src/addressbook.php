<?php

/**
 * addressbook.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Manage personal address book.
 *
 * $Id$
 * @package squirrelmail
 */

/** Path for SquirrelMail required files. */
define('SM_PATH','../');

/** SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/addressbook.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/forms.php');

/** lets get the global vars we may need */
sqgetGlobalVar('key',       $key,           SQ_COOKIE);

sqgetGlobalVar('username',  $username,      SQ_SESSION);
sqgetGlobalVar('onetimepad',$onetimepad,    SQ_SESSION);
sqgetGlobalVar('base_uri',  $base_uri,      SQ_SESSION);
sqgetGlobalVar('delimiter', $delimiter,     SQ_SESSION);

/* From the address form */
sqgetGlobalVar('addaddr',   $addaddr,   SQ_POST);
sqgetGlobalVar('editaddr',  $editaddr,  SQ_POST);
sqgetGlobalVar('deladdr',   $deladdr,   SQ_POST);
sqgetGlobalVar('sel',       $sel,       SQ_POST);
sqgetGlobalVar('oldnick',   $oldnick,   SQ_POST);
sqgetGlobalVar('backend',   $backend,   SQ_POST);
sqgetGlobalVar('doedit',    $doedit,    SQ_POST); 

/**
 * Make an input field
 * @param string $label
 * @param string $field
 * @param string $name
 * @param string $size
 * @param array $values
 * @param string $add
 */
function addressbook_inp_field($label, $field, $name, $size, $values, $add) {
    global $color;
    $value = ( isset($values[$field]) ? $values[$field] : '');
    
    $td_str = addInput($name.'['.$field.']', $value, $size)
        . $add ;
	
    return html_tag( 'tr' ,
        html_tag( 'td', $label . ':', 'right', $color[4]) .
        html_tag( 'td', $td_str, 'left', $color[4])
        )
    . "\n";
}

/**
 * Output form to add and modify address data
 */
function address_form($name, $submittext, $values = array()) {
    global $color, $squirrelmail_language;
    
    if ($squirrelmail_language == 'ja_JP')
        {
    echo html_tag( 'table',
                       addressbook_inp_field(_("Nickname"),     'nickname', $name, 15, $values,
                           ' <SMALL>' . _("Must be unique") . '</SMALL>') .
                       addressbook_inp_field(_("E-mail address"),  'email', $name, 45, $values, '') .
                       addressbook_inp_field(_("Last name"),    'lastname', $name, 45, $values, '') .
                       addressbook_inp_field(_("First name"),  'firstname', $name, 45, $values, '') .
                       addressbook_inp_field(_("Additional info"), 'label', $name, 45, $values, '') .
		       list_writable_backends($name) .
                       html_tag( 'tr',
                           html_tag( 'td',
                                       '<INPUT TYPE=submit NAME="' . htmlentities($name) . '[SUBMIT]" VALUE="' .
                                       $submittext . '">',
                                   'center', $color[4], 'colspan="2"')
                       )
    , 'center', '', 'border="0" cellpadding="1" width="90%"') ."\n";
        } else {
    echo html_tag( 'table',
                       addressbook_inp_field(_("Nickname"),     'nickname', $name, 15, $values,
                           ' <SMALL>' . _("Must be unique") . '</SMALL>') .
                       addressbook_inp_field(_("E-mail address"),  'email', $name, 45, $values, '') .
                       addressbook_inp_field(_("First name"),  'firstname', $name, 45, $values, '') .
                       addressbook_inp_field(_("Last name"),    'lastname', $name, 45, $values, '') .
                       addressbook_inp_field(_("Additional info"), 'label', $name, 45, $values, '') .
		       list_writable_backends($name) .
                       html_tag( 'tr',
                           html_tag( 'td',
                                       '<INPUT TYPE=submit NAME="' . htmlentities($name) . '[SUBMIT]" VALUE="' .
                                       $submittext . '">',
                                   'center', $color[4], 'colspan="2"')
                       )
    , 'center', '', 'border="0" cellpadding="1" width="90%"') ."\n";
}
}

function list_writable_backends($name) {
  global $color, $abook;
  if ( $name != 'addaddr' ) { return; }
  if ( $abook->numbackends > 1 ) {
    $ret = "<select name=backend>";
    $backends = $abook->get_backend_list();
    while (list($undef,$v) = each($backends)) {
      if ($v->writeable) {
	$ret .= '<OPTION VALUE=' . $v->bnum;
	$ret .= '>' . $v->sname . "\n";
      }
    }
    $ret .= "</select>";
    return html_tag( 'tr',
		     html_tag( 'td', _("Add to:"),'right', $color[4] ) .
		     html_tag( 'td', $ret, 'left', $color[4] )) . "\n";
  } else {
  return html_tag( 'tr',
		   html_tag( 'td',
		             addHidden('backend', '1'),
                             'center', $color[4], 'colspan="2"')) . "\n";
  }
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
$form_url = 'addressbook.php';


/* Handle user's actions */
if(sqgetGlobalVar('REQUEST_METHOD', $req_method, SQ_SERVER) && $req_method == 'POST') {

    /**************************************************
     * Add new address                                *
     **************************************************/
    if (isset($addaddr)) {
        foreach( $addaddr as $k => $adr ) {
            $addaddr[$k] = strip_tags( $adr );
        }
	if (isset($backend)) {
	  $r = $abook->add($addaddr, $backend);
	} else {
	  $r = $abook->add($addaddr, $abook->localbackend);
	}

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
                        echo '<FORM ACTION="' . $form_url . '" METHOD="POST">' .
                             "\n" .
                             html_tag( 'table',
                                html_tag( 'tr',
                                   html_tag( 'td',
                                      "\n". '<strong>' . _("Update address") . '</strong>' ."\n",
                                      'center', $color[0] )
                                   ),
                             'center', '', 'width="100%" ' );
                        address_form("editaddr", _("Update address"), $olddata);
			echo addHidden('oldnick', $olddata['nickname']).
                             addHidden('backend', $olddata['backend']).
			     addHidden('doedit', '1').
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
                                      "\n". '<strong><font color="' . $color[2] .
                                      '">' . _("ERROR") . ': ' . $abook->error . '</font></strong>' ."\n",
                                      'center' )
                                   ),
                             'center', '', 'width="100%"' );

                            /* Display the "new address" form again */
                            echo '<FORM ACTION="' . $form_url .
                                 '" METHOD="POST">' . "\n" .
                                 html_tag( 'table',
                                     html_tag( 'tr',
                                         html_tag( 'td',
                                                    "\n". '<strong>' . _("Update address") . '</strong>' ."\n",
                                         'center', $color[0] )
                                     ),
                                 'center', '', 'width="100%"' );
                            address_form("editaddr", _("Update address"), $newdata);
                            echo 
			    	addHidden('oldnick', $oldnick).
				addHidden('backend', $backend).
				addHidden('doedit',  '1').
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
    'center', '', 'width="100%"' );
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
        echo '<form action="' . $form_url . '" method="post">' . "\n";
        while(list($undef,$row) = each($alist)) {
    
            /* New table header for each backend */
            if($prevbackend != $row['backend']) {
                if($prevbackend < 0) {
                    echo html_tag( 'table',
                                    html_tag( 'tr',
                                          html_tag( 'td',
                                                     '<input type=submit name=editaddr value="' . 
                                                     _("Edit selected") . "\" />\n" .
                                                     '<input type=submit name=deladdr value="' .
                                                     _("Delete selected") . "\" />\n",
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
                        'center', '', 'width="95%"' ) ."\n" .
		    "<!-- start of address book table -->\n" .
            	  html_tag( 'table', '', 'center', '', 'border="0" cellpadding="1" cellspacing="0" width="90%"' ) .
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
            $selected = in_array($row['backend'] . ':' . $row['nickname'], $defselected);
    
            /* Print one row */
            $tr_bgcolor = '';
            if ($line % 2) { $tr_bgcolor = $color[0]; }
            if ($squirrelmail_language == 'ja_JP')
                {
            echo html_tag( 'tr', '', '', $tr_bgcolor) .
                html_tag( 'td',
                          '<small>' .
			  addCheckBox('sel[]', $selected, $row['backend'].':'.$row['nickname']).
                          '</small>' ,
                          'center', '', 'valign="top" width="1%"' ) .
                html_tag( 'td', '&nbsp;' . $row['nickname'] . '&nbsp;', 'left', '', 'valign="top" width="1%" nowrap' ) . 
                html_tag( 'td', '&nbsp;' . $row['lastname'] . ' ' . $row['firstname'] . '&nbsp;', 'left', '', 'valign="top" width="1%" nowrap' ) .
                html_tag( 'td', '', 'left', '', 'valign="top" width="1%" nowrap' ) . '&nbsp;';
                } else {
            echo html_tag( 'tr', '', '', $tr_bgcolor) .
            html_tag( 'td',
                '<small>' .
                '<input type=checkbox ' . $selected . ' name="sel[]" value="' .
                $row['backend'] . ':' . $row['nickname'] . '" /></small>' ,
                'center', '', 'valign="top" width="1%"' ) .
            html_tag( 'td', '&nbsp;' . $row['nickname'] . '&nbsp;', 'left', '', 'valign="top" width="1%" nowrap' ) .
            html_tag( 'td', '&nbsp;' . $row['name'] . '&nbsp;', 'left', '', 'valign="top" width="1%" nowrap' ) .
            html_tag( 'td', '', 'left', '', 'valign="top" width="1%" nowrap' ) . '&nbsp;';
                }
            $email = $abook->full_address($row);
            echo makeComposeLink('src/compose.php?send_to='.rawurlencode($email),
                 htmlspecialchars($row['email']) ) .
            '&nbsp;</td>'."\n".
            html_tag( 'td', '&nbsp;' . htmlspecialchars($row['label']) . '&nbsp;', 'left', '', 'valign="top" width="1%"' ) .
            "</tr>\n";
            $line++;
	    echo "</table>\n".
		"<!-- end of address book table -->\n";
        }
    
        /* End of list. Add edit/delete select buttons */
        if ($headerprinted) {
            echo html_tag( 'table',
		    html_tag( 'tr',
                        html_tag( 'td',
                                '<input type="submit" name="editaddr" value="' . _("Edit selected") .
                                "\" />\n" .
                                '<input type="submit" name="deladdr" value="' . _("Delete selected") .
                                "\" />\n",
                         'center', '', 'colspan="5"' )
                    ),
		'center' );
        }
        echo "</form>\n";
    }
} /* end of addresslist */


/* Display the "new address" form */
echo '<a name="AddAddress"></a>' . "\n" .
    '<form action="' . $form_url . '" name="f_add" method="post">' . "\n" .
    html_tag( 'table',  
        html_tag( 'tr',
            html_tag( 'td', "\n". '<strong>' . _("Add to address book") . '</strong>' . "\n",
                'center', $color[0]
            )
        )
    , 'center', '', 'width="100%"' ) ."\n";
address_form('addaddr', _("Add address"), $defdata);
echo "</form>\n";

/* Add hook for anything that wants on the bottom */
do_hook('addressbook_bottom');
?>

</body></html>