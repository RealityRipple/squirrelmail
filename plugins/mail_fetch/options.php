<?php

/**
 * mail_fetch/options.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Setup of the mailfetch plugin.
 *
 * $Id$
 */

define('SM_PATH','../../');

require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'include/load_prefs.php');

    /* globals */
    $username = $_SESSION['username'];
    $key = $_COOKIE['key'];
    $onetimepad = $_SESSION['onetimepad'];
    $delimiter = $_SESSION['delimiter'];
    
    if(isset($_POST['mf_cypher'])) {
        $mf_cypher = $_POST['mf_cypher'];
    }
    if(isset($_POST['mf_sn'])) {
        $mf_sn = $_POST['mf_sn'];
    }
    if(isset($_POST['mf_server'])) {
        $mf_server = $_POST['mf_server'];
    }
    if(isset($_POST['mf_port'])) {
        $mf_port = $_POST['mf_port'];
    }
    if(isset($_POST['mf_alias'])) {
        $mf_alias = $_POST['mf_alias'];
    }
    if(isset($_POST['mf_user'])) {
        $mf_user = $_POST['mf_user'];
    }
    if(isset($_POST['mf_pass'])) {
        $mf_pass = $_POST['mf_pass'];
    }
    if(isset($_POST['mf_subfolder'])) {
        $mf_subfolder = $_POST['mf_subfolder'];
    }
    if(isset($_POST['mf_login'])) {
        $mf_login = $_POST['mf_login'];
    }
    if(isset($_POST['mf_fref'])) {
        $mf_fref = $_POST['mf_fref'];
    }
    if(isset($_POST['submit_mailfetch'])) {
        $submit_mailfetch = $_POST['submit_mailfetch'];
    }
    if(isset($_POST['mf_lmos'])) {
        $mf_lmos = $_POST['mf_lmos'];
    }
    /* end globals */

    displayPageHeader( $color, 'None' );

    //if dosen't select any option
    if (!isset($_POST['mf_action'])) {
        $mf_action = 'config';
    } else {
        $mf_action = $_POST['mf_action'];
    }

    switch( $mf_action ) {
    case 'add':
        if ($mf_sn<1) $mf_sn=0;
        if (!isset($mf_server)) return;
        setPref($data_dir,$username,"mailfetch_server_$mf_sn", (isset($mf_server)?$mf_server:""));
        setPref($data_dir,$username,"mailfetch_port_$mf_sn", (isset($mf_port)?$mf_port:'110'));
        setPref($data_dir,$username,"mailfetch_alias_$mf_sn", (isset($mf_alias)?$mf_alias:""));
        setPref($data_dir,$username,"mailfetch_user_$mf_sn",(isset($mf_user)?$mf_user:""));
        setPref($data_dir,$username,"mailfetch_pass_$mf_sn",(isset($mf_pass)?encrypt( $mf_pass )    :""));
        if( isset($mf_cypher) && $mf_cypher <> 'on' ) SetPref($data_dir,$username,'mailfetch_cypher',    'on');
        setPref($data_dir,$username,"mailfetch_lmos_$mf_sn",(isset($mf_lmos)?$mf_lmos:""));
        setPref($data_dir,$username,"mailfetch_login_$mf_sn",(isset($mf_login)?$mf_login:""));
        setPref($data_dir,$username,"mailfetch_fref_$mf_sn",(isset($mf_fref)?$mf_fref:""));
        setPref($data_dir,$username,"mailfetch_subfolder_$mf_sn",(isset($mf_subfolder)?$mf_subfolder:""));
        $mf_sn++;
        setPref($data_dir,$username,'mailfetch_server_number', $mf_sn);
        $mf_action = 'config';
        break;
    case 'confirm_modify':
        //modify    a server
        if (!isset($mf_server)) return;
        setPref($data_dir,$username,"mailfetch_server_$mf_sn", (isset($mf_server)?$mf_server:""));
        setPref($data_dir,$username,"mailfetch_port_$mf_sn", (isset($mf_port)?$mf_port:'110'));
        setPref($data_dir,$username,"mailfetch_alias_$mf_sn", (isset($mf_alias)?$mf_alias:""));
        setPref($data_dir,$username,"mailfetch_user_$mf_sn",(isset($mf_user)?$mf_user:""));
        setPref($data_dir,$username,"mailfetch_pass_$mf_sn",(isset($mf_pass)?encrypt( $mf_pass )    :""));
        if( $mf_cypher <> 'on' ) setPref($data_dir,$username,"mailfetch_cypher", 'on');
        setPref($data_dir,$username,"mailfetch_lmos_$mf_sn",(isset($mf_lmos)?$mf_lmos:""));
        setPref($data_dir,$username,"mailfetch_login_$mf_sn",(isset($mf_login)?$mf_login:""));
        setPref($data_dir,$username,"mailfetch_fref_$mf_sn",(isset($mf_fref)?$mf_fref:""));
        setPref($data_dir,$username,"mailfetch_subfolder_$mf_sn",(isset($mf_subfolder)?$mf_subfolder:""));
        $mf_action = 'config';
        break;
    case 'confirm_delete':
        //delete    a server
        $mailfetch_server_number    = getPref($data_dir, $username, "mailfetch_server_number");
        if ($mf_sn+1==$mailfetch_server_number) {
            //is the last server, whe can only decrase $mailfetch_server_number
            $mailfetch_server_number--;
            setPref($data_dir,$username,"mailfetch_server_number", $mailfetch_server_number);
        } else {
            //if not the last, all the sequel server come up one step
            //then whe decrase $mailfetch_server_number
            $mailfetch_server_number--;
            for ($i=$mf_sn;$i<$mailfetch_server_number;$i++) {
                $tmp=$i+1;
                setPref($data_dir,$username,"mailfetch_server_$i", getPref($data_dir, $username, "mailfetch_server_$tmp"));
                setPref($data_dir,$username,"mailfetch_port_$i", getPref($data_dir,$username, "mailfetch_port_$tmp"));
                setPref($data_dir,$username,"mailfetch_alias_$i", getPref($data_dir, $username, "mailfetch_alias_$tmp"));
                setPref($data_dir,$username,"mailfetch_user_$i", getPref($data_dir, $username, "mailfetch_user_$tmp"));
                setPref($data_dir,$username,"mailfetch_pass_$i",(isset($mf_pass)?encrypt( $mf_pass ) :""));
                // if( $mf_cypher <> 'on' ) setPref($data_dir,$username,"mailfetch_cypher", 'on');
                setPref($data_dir,$username,"mailfetch_lmos_$i", getPref($data_dir, $username, "mailfetch_lmos_$tmp"));
                setPref($data_dir,$username,"mailfetch_login_$i", getPref($data_dir, $username, "mailfetch_login_$tmp"));
                setPref($data_dir,$username,"mailfetch_fref_$i", getPref($data_dir, $username, "mailfetch_fref_$tmp"));
                setPref($data_dir,$username,"mailfetch_subfolder_$i", getPref($data_dir, $username, "mailfetch_subfolder_$tmp"));
            }
            setPref($data_dir,$username,"mailfetch_server_number", $mailfetch_server_number);
        }
        $mf_action = 'config';
        break;
    }

    $mailfetch_server_number = getPref($data_dir, $username, 'mailfetch_server_number', 0);
    $mailfetch_cypher = getPref( $data_dir, $username, 'mailfetch_cypher' );
    if ($mailfetch_server_number<1) {
        $mailfetch_server_number=0;
    }
    for ($i=0;$i<$mailfetch_server_number;$i++) {
        $mailfetch_server_[$i] = getPref($data_dir, $username, "mailfetch_server_$i");
        $mailfetch_port_[$i] = getPref($data_dir, $username, "mailfetch_port_$i");
        $mailfetch_alias_[$i] = getPref($data_dir, $username, "mailfetch_alias_$i");
        $mailfetch_user_[$i] = getPref($data_dir, $username, "mailfetch_user_$i");
        $mailfetch_pass_[$i] = getPref($data_dir, $username, "mailfetch_pass_$i");
        $mailfetch_lmos_[$i] = getPref($data_dir, $username, "mailfetch_lmos_$i");
        $mailfetch_login_[$i] = getPref($data_dir, $username, "mailfetch_login_$i");
        $mailfetch_fref_[$i] = getPref($data_dir, $username, "mailfetch_fref_$i");
        $mailfetch_uidl_[$i] = getPref($data_dir, $username, "mailfetch_uidl_$i");
        $mailfetch_subfolder_[$i] = getPref($data_dir, $username, "mailfetch_subfolder_$i");
        if( $mailfetch_cypher == 'on' ) $mailfetch_pass_[$i] = decrypt( $mailfetch_pass_[$i] );
    }


    echo '<BR><form method=post action="'.$PHP_SELF.'">' .
            html_tag( 'table',
                html_tag( 'tr',
                    html_tag( 'td',
                        '<b>' . _("Remote POP server settings") . '</b>',
                    'center', $color[0] )
                ),
            'center', '', 'width="95%" cols="1"' ) .
            html_tag( 'table',
                html_tag( 'tr',
                    html_tag( 'td',
                        _("You should be aware that the encryption used to store your password is not perfectly secure.  However, if you are using pop, there is inherently no encryption anyway. Additionally, the encryption that we do to save it on the server can be undone by a hacker reading the source to this file." ) ,
                    'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'td',
                        _("If you leave password empty, it will be required when you fetch mail.") ,
                    'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'td',
                        '<input type=checkbox name=mf_cypher ' .
                        (($mailfetch_cypher=='on')?'checked >':'>') .
                        _("Encrypt passwords (informative only)") ,
                    'right' )
                ) ,
            'center', '', 'width="95%" cols="1"' );

    switch( $mf_action ) {
    case 'config':
        echo html_tag( 'table', '', 'center', '', 'width="70%" cols="1" cellpadding="5" cellspacing="1"' ) .
                    html_tag( 'tr',
                        html_tag( 'td', '<b>' . _("Add Server") . '</b>', 'center', $color[9] )
                    ) .
                    html_tag( 'tr' ) .
                        html_tag( 'td', '', 'center', $color[0] ) .

            "<INPUT TYPE=\"hidden\" NAME=\"mf_sn\" VALUE=\"$mailfetch_server_number\">" .
            '<INPUT TYPE="hidden" NAME="mf_action" VALUE="add">' .
            html_tag( 'table' ) .
                html_tag( 'tr',
                    html_tag( 'th', _("Server:"), 'right' ) .
                    html_tag( 'td', '<input type=text name=mf_server value="" size=40>', 'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'th', _("Port:"), 'right') .
                    html_tag( 'td', '<input type=text name=mf_port value="" size=20', 'left')
                ) .
                html_tag( 'tr',
                    html_tag( 'th', _("Alias:"), 'right' ) .
                    html_tag( 'td', '<input type=text name=mf_alias value="" size=20>', 'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'th', _("Username:"), 'right' ) .
                    html_tag( 'td', '<input type=text name=mf_user value="" size=20>', 'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'th', _("Password:"), 'right' ) .
                    html_tag( 'td', '<input type=password name=mf_pass value="" size=20>', 'left' )
                ) .
                html_tag( 'tr' ) .
                    html_tag( 'th', _("Store in Folder:"), 'right' ) .
                    html_tag( 'td', '', 'left' );
        $imapConnection = sqimap_login ($username, $key, $imapServerAddress, $imapPort, 0);
        $boxes = sqimap_mailbox_list($imapConnection);
        echo '<select name="mf_subfolder">';

        $selected = 0;
        if ( isset($mf_subfolder) )
          $selected = array(strtolower($mf_subfolder));
        echo sqimap_mailbox_option_list($imapConnection, $selected);
        echo '</select></td></tr>' .
                html_tag( 'tr',
                    html_tag( 'th', '&nbsp;', 'right' ) .
                    html_tag( 'td', '<input type="checkbox" name="mf_lmos" checked>' . _("Leave Mail on Server"), 'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'th', '&nbsp;', 'right' ) .
                    html_tag( 'td', '<input type="checkbox" name="mf_login">' . _("Check mail during login"), 'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'th', '&nbsp;', 'right' ) .
                    html_tag( 'td', '<input type="checkbox" name="mf_fref">' . _("Check mail during folder refresh"), 'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'td',
                        '<input type=submit name="submit_mailfetch" value="' . _("Add Server") . '">',
                    'center', '', 'colspan="2"' )
                ) .
            '</table></form></td></tr></table>';

        // Modify Server
        echo '<font size=-5><BR></font>' .
            html_tag( 'table', '', 'center', '', 'width="70%" cols="1" cellpadding="5" cellspacing="1"' ) .
                html_tag( 'tr',
                    html_tag( 'td', '<b>' . _("Modify Server") . '</b>', 'center', $color[9] )
                ) .
                html_tag( 'tr' ) .
                    html_tag( 'td', '', 'center', $color[0] );
        if ($mailfetch_server_number>0) {
            echo "<form action=\"$PHP_SELF\" method=\"post\" target=\"_self\">";
            echo '<b>' . _("Server Name:") . '</b> <select name="mf_sn">';
            for ($i=0;$i<$mailfetch_server_number;$i++) {
                echo "<option value=\"$i\">" .
                    (($mailfetch_alias_[$i]=='')?$mailfetch_server_[$i]:$mailfetch_alias_[$i]) . "</option>>";
            }
            echo '</select>'.
                 '&nbsp;&nbsp;<INPUT TYPE=submit name=mf_action value="' . _("Modify") . '">'.
                 '&nbsp;&nbsp;<INPUT TYPE=submit name=mf_action value="' . _("Delete") . '">'.
                 '</form>';
        } else {
            echo _("No-one server in use. Try to add.");
        }
        echo '</td></tr></table>';
        break;
    case _("Delete"):                                     //erase confirmation about a server
        echo html_tag( 'table',
                    html_tag( 'tr',
                        html_tag( 'td', '<b>' . _("Fetching Servers") . '</b>', 'center', $color[0] )
                    ) ,
                'center', '', 'width="95%" cols="1" cellpadding="5" cellspacing="1"' ) .
            '<br>' .
            html_tag( 'table',
                html_tag( 'tr',
                    html_tag( 'td', '<b>' . _("Confirm Deletion of a Server") . '</b>', 'center', $color[9] )
                ) .
                html_tag( 'tr',
                    html_tag( 'td',
                        "<INPUT TYPE=\"hidden\" NAME=\"mf_sn\" VALUE=\"$mf_sn\">" .
                        '<INPUT TYPE="hidden" NAME="mf_action" VALUE="confirm_delete">' .
                        '<br>' . _("Selected Server:") . " <b>$mailfetch_server_[$mf_sn]</b><br>" .
                        _("Confirm delete of selected server?") . '<br><br>' .
                        '<input type=submit name=submit_mailfetch value="' . _("Confirm Delete") . '">' .
                        '<br></form>' ,
                    'center', $color[9] )
                ) ,
            'center', '', 'width="70%" cols="1" cellpadding="5" cellspacing="1"' );
        break;                                  //modify a server
    case _("Modify"):
        echo html_tag( 'table',
                    html_tag( 'tr',
                        html_tag( 'td', '<b>' . _("Fetching Servers") . '</b>', 'center', $color[0] )
                    ) ,
                'center', '', 'width="95%" cols="1" cellpadding="5" cellspacing="1"' ) .
            '<br>' .
            html_tag( 'table', '', 'center', '', 'width="70%" cols="1" cellpadding="5" cellspacing="1"' ) .
                html_tag( 'tr',
                    html_tag( 'td', '<b>' . _("Mofify a Server") . '</b>', 'center', $color[9] )
                ) .
                html_tag( 'tr' ) .
                    html_tag( 'td', '', 'center', $color[0] ) .

            "<INPUT TYPE=\"hidden\" NAME=\"mf_sn\" VALUE=\"$mf_sn\">" .
            '<INPUT TYPE="hidden" NAME="mf_action" VALUE="confirm_modify">' .
            html_tag( 'table' ) .
                html_tag( 'tr',
                    html_tag( 'th', _("Server:"), 'right' ) .
                    html_tag( 'td', '<input type="text" name="mf_server" value="' . $mailfetch_server_[$mf_sn] . '" size="40">', 'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'th', _("Port:"), 'right' ) .
                    html_tag( 'td', '<input type="text" name="mf_port" value="' . $mailfetch_port_[$mf_sn] . '" size="40">', 'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'th', _("Alias:"), 'right' ) .
                    html_tag( 'td', '<input type="text" name="mf_alias" value="' . $mailfetch_alias_[$mf_sn] . '" size="40">', 'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'th', _("Username:"), 'right' ) .
                    html_tag( 'td', '<input type="text" name="mf_user" value="' . $mailfetch_user_[$mf_sn] . '" size="20">', 'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'th', _("Password:"), 'right' ) .
                    html_tag( 'td', '<input type="password" name="mf_pass" value="' . $mailfetch_pass_[$mf_sn] . '" size="20">', 'left' )
                ) .
                html_tag( 'tr' ) .
                    html_tag( 'th', _("Store in Folder:"), 'right' ) .
                    html_tag( 'td', '', 'left' );

        $imapConnection = sqimap_login ($username, $key, $imapServerAddress, $imapPort, 0);
        $boxes = sqimap_mailbox_list($imapConnection);
        echo '<select name="mf_subfolder">';
        $selected = 0;
        if ( isset($mf_subfolder) )
          $selected = array(strtolower($mf_subfolder));
        echo sqimap_mailbox_option_list($imapConnection, $selected);
        echo '</select></td></tr>' .

                html_tag( 'tr',
                    html_tag( 'th', '&nbsp;', 'right' ) .
                    html_tag( 'td',
                        '<input type=checkbox name=mf_lmos ' . (($mailfetch_lmos_[$mf_sn] == 'on')?'checked':'') .
                        '>' . _("Leave Mail on Server") ,
                    'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'th', '&nbsp;', 'right' ) .
                    html_tag( 'td',
                        '<input type=checkbox name=mf_login ' . ( ($mailfetch_login_[$mf_sn] == 'on')?'checked':'') .
                        '>' . _("Check mail during login"),
                    'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'th', '&nbsp;', 'right' ) .
                    html_tag( 'td',
                        '<input type=checkbox name=mf_fref ' . ( ($mailfetch_fref_[$mf_sn] == 'on')?'checked':'') .
                        '>' . _("Check mail during folder refresh") ,
                    'left' )
                ) .
                html_tag( 'tr',
                    html_tag( 'td',
                        '<input type=submit name="submit_mailfetch" value="' . _("Modify Server") . '">',
                    'center', '', 'colspan="2"' )
                ) .

            '</table></form></td></tr></table>';
        break;
    default:                                    //unsupported action
        echo '</form>' .
        html_tag( 'table',
            html_tag( 'tr',
                html_tag( 'td', '<b>' . _("Fetching Servers") . '</b>', 'center', $color[0] )
            ) ,
        'center', '', 'width="95%" cols="1"' ) .
        '<br>' .
        html_tag( 'table',
            html_tag( 'tr',
                html_tag( 'td', '<b>' . _("Undefined Function") . '</b>', 'center', $color[9] ) .
                html_tag( 'td', '<b>' . _("Hey! Wath do You are looking for?") . '</b>', 'center', $color[0] )
            ) ,
        'center', '', 'width="70%" cols="1"' );
    }

    ?>
</body></html>
