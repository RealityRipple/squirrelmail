<?php

   /**
    **  mail_fetch/options.php
    **
    **  Copyright (c) 1999-2002 The SquirrelMail Project Team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Setup of the mailfetch plugin.
    **
    **  $Id$
    **/

    chdir('..');
    require_once('../src/validate.php');
    require_once('../functions/imap.php');
    require_once('../src/load_prefs.php');

    displayPageHeader( $color, 'None' );

    //if dosen't select any option
    if (!isset($mf_action))
        $mf_action = 'config';

    switch( $mf_action ) {
    case 'add':
        if ($mf_sn<1) $mf_sn=0;
        if (!isset($mf_server)) return;
        setPref($data_dir,$username,"mailfetch_server_$mf_sn", (isset($mf_server)?$mf_server:""));
        setPref($data_dir,$username,"mailfetch_alias_$mf_sn", (isset($mf_alias)?$mf_alias:""));
        setPref($data_dir,$username,"mailfetch_user_$mf_sn",(isset($mf_user)?$mf_user:""));
        setPref($data_dir,$username,"mailfetch_pass_$mf_sn",(isset($mf_pass)?encrypt( $mf_pass )    :""));
        if( $mf_cypher <> 'on' ) SetPref($data_dir,$username,"mailfetch_cypher",    'on');
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
                setPref($data_dir,$username,"mailfetch_server_$mf_sn", getPref($data_dir, $username, "mailfetch_server_$tmp"));
                setPref($data_dir,$username,"mailfetch_alias_$mf_sn", getPref($data_dir, $username, "mailfetch_alias_$tmp"));
                setPref($data_dir,$username,"mailfetch_user_$mf_sn", getPref($data_dir, $username, "mailfetch_user_$tmp"));
                setPref($data_dir,$username,"mailfetch_pass_$mf_sn",(isset($mf_pass)?encrypt( $mf_pass ) :""));
                // if( $mf_cypher <> 'on' ) setPref($data_dir,$username,"mailfetch_cypher", 'on');
                setPref($data_dir,$username,"mailfetch_lmos_$mf_sn", getPref($data_dir, $username, "mailfetch_lmos_$tmp"));
                setPref($data_dir,$username,"mailfetch_login_$mf_sn", getPref($data_dir, $username, "mailfetch_login_$tmp"));
                setPref($data_dir,$username,"mailfetch_fref_$mf_sn", getPref($data_dir, $username, "mailfetch_fref_$tmp"));
                setPref($data_dir,$username,"mailfetch_subfolder_$mf_sn", getPref($data_dir, $username, "mailfetch_subfolder_$tmp"));
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
            "<TABLE WIDTH=95% COLS=1 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><b>" . _("Remote POP server settings") . '</b></TD></TR></TABLE>' .
            '<TABLE WIDTH=95% COLS=1 ALIGN=CENTER><TR><TD>' .
            _("You should be aware that the encryption used to store your password is not perfectly secure.  However, if you are using pop, there is inherently no encryption anyway. Additionally, the encryption that we do to save it on the server can be undone by a hacker reading the source to this file." ) .
            '</TD></TR><TR><TD>' .
            _("If you leave password empty, it will be required when you fetch mail.") .
            '</TD></TR>' .
            '<tr><td align=right><input type=checkbox name=mf_cypher ' .
            (($mailfetch_cypher=='on')?'checked >':'>') .
            _("Encrypt passwords (informative only)") . ' </td><tr>' .
            '</TABLE></td></tr>';

    switch( $mf_action ) {
    case 'config':
        echo '<TABLE WIDTH=70% COLS=1 ALIGN=CENTER cellpadding=5 cellspacing=1>' .
            "  <TR><TD BGCOLOR=\"$color[9]\" ALIGN=CENTER><b>" . _("Add Server") . '</b></TD></TR>' .
            "  <TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>" .
            "<INPUT TYPE=\"hidden\" NAME=\"mf_sn\" VALUE=\"$mailfetch_server_number\">" .
            '<INPUT TYPE="hidden" NAME="mf_action" VALUE="add"><table>' .
            '<tr><th align=right>' . _("Server:") . '</th><td><input type=text name=mf_server value="" size=40></td></tr>' .
            '<tr><th align=right>' . _("Alias:") . '</th><td><input type=text name=mf_alias value="" size=20></td></tr>' .
            '<tr><th align=right>' . _("Username:") . '</th><td><input type=text name=mf_user value="" size=20></td></tr>' .
            '<tr><th align=right>' . _("Password:") . '</th><td><input type=password name=mf_pass value="" size=20></td></tr>' .
            '<tr><th align=right>' . _("Store in Folder:") . '</th><td>';
        $imapConnection = sqimap_login ($username, $key, $imapServerAddress, $imapPort, 0);
        $boxes = sqimap_mailbox_list($imapConnection);
        echo '<SELECT NAME=mf_subfolder>';
        $boxes = sqimap_mailbox_list($imapConnection);
        for ($i = 0; $i < count($boxes); $i++) {
            if (!in_array('noselect', $boxes[$i]['flags'])) {
                $box = $boxes[$i]['unformatted'];
                $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
                if ( strtolower( $box2 ) == 'inbox' ) {
                    $box2 = _("INBOX");
                }
                echo "<OPTION VALUE=\"$box\">$box2</option>\n";
            }
        }        
        echo '</SELECT></td></tr>' .
            '<tr><th align=right>&nbsp;</th><td><input type=checkbox name=mf_lmos checked>' . _("Leave Mail on Server") . '</td></tr>' .
            '<tr><th align=right>&nbsp;</th><td><input type=checkbox name=mf_login>' . _("Check mail during login") . '</td></tr>' .
            '<tr><th align=right>&nbsp;</th><td><input type=checkbox name=mf_fref>' . _("Check mail during folder refresh") . '</td></tr>' .
            '<tr><td align=center colspan=2><input type=submit name=submit_mailfetch value="' . _("Add Server") . '"></td></tr>' .
            '</table></form></td></tr></TABLE>';

        // Modify Server
        echo '<font size=-5><BR></font>' .
            '<TABLE WIDTH=70% COLS=1 ALIGN=CENTER cellpadding=5 cellspacing=1>' .
            "<TR><TD BGCOLOR=\"$color[9]\" ALIGN=CENTER><b>" . _("Modify Server") . '</b></TD></TR>' .
            "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>";
        if ($mailfetch_server_number>0) {
            echo "<form action=\"$PHP_SELF\" METHOD=\"POST\" TARGET=_self>";
            echo '<b>' . _("Server Name:") . '</b> <SELECT NAME="mf_sn">';
            for ($i=0;$i<$mailfetch_server_number;$i++) {
                echo "<OPTION VALUE=\"$i\">" .
                    (($mailfetch_alias_[$i]=='')?$mailfetch_server_[$i]:$mailfetch_alias_[$i]) . "</OPTION>>";
            }
            echo '</SELECT>'.
                 '&nbsp;&nbsp;<INPUT TYPE=submit name=mf_action value="' . _("Modify") . '">'.
                 '&nbsp;&nbsp;<INPUT TYPE=submit name=mf_action value="' . _("Delete") . '">'.
                 '</form>';
        } else {
            echo _("No-one server in use. Try to add.");
        }
        echo '</TD></TR></TABLE>';
        break;
    case _("Delete"):                                     //erase confirmation about a server
        echo '<TABLE WIDTH=95% COLS=1 ALIGN=CENTER cellpadding=5 cellspacing=1>' .
            "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><b>" . _("Fetching Servers") . '</b></TD></TR>' .
            '</TABLE>' .
            '<BR>' .
            '<TABLE WIDTH=70% COLS=1 ALIGN=CENTER cellpadding=5 cellspacing=1>' .
            "<TR><TD BGCOLOR=\"$color[9]\" ALIGN=CENTER><b>" . _("Confirm Deletion of a Server") . '</b></TD></TR>' .
            "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>" .
            "<INPUT TYPE=\"hidden\" NAME=\"mf_sn\" VALUE=\"$mf_sn\">" .
            '<INPUT TYPE="hidden" NAME="mf_action" VALUE="confirm_delete">' .
            '<br>' . _("Selected Server:") . "<b>$mailfetch_server_[$mf_sn]</b><br>" .
            _("Confirm delete of selected server?") . '<br><br>' .
            '<input type=submit name=submit_mailfetch value="' . _("Confirm Delete") . '">' .
            '<br></form></TD></TR></TABLE>';
        break;                                  //modify a server
    case _("Modify"):
        echo '<TABLE WIDTH=95% COLS=1 ALIGN=CENTER cellpadding=5 cellspacing=1>' .
            "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><b>" . _("Fetching Servers") . '</b></TD></TR>' .
            '</TABLE>' .
            '<BR>' .
            '<TABLE WIDTH=70% COLS=1 ALIGN=CENTER cellpadding=5 cellspacing=1>' .
            "<TR><TD BGCOLOR=\"$color[9]\" ALIGN=CENTER><b>" . _("Mofify a Server") . '</b></TD></TR>' .
            "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>" .
            "<INPUT TYPE=\"hidden\" NAME=\"mf_sn\" VALUE=\"$mf_sn\">" .
            '<INPUT TYPE="hidden" NAME="mf_action" VALUE="confirm_modify">' .
            '<table>' .
            '<tr><th align=right>' . _("Server:") . '</th>' .
            "<td><input type=text name=mf_server value=\"$mailfetch_server_[$mf_sn]\" size=40></td></tr>" .
            '<tr><th align=right>' . _("Alias:") . '</th>' .
            "<td><input type=text name=mf_alias value=\"$mailfetch_alias_[$mf_sn]\" size=40></td></tr>" .
            '<tr><th align=right>' . _("Username:") . '</th>' .
            "<td><input type=text name=mf_user value=\"$mailfetch_user_[$mf_sn]\" size=20></td></tr>" .
            '<tr><th align=right>' . _("Password:") . '</th>' .
            "<td><input type=password name=mf_pass value=\"$mailfetch_pass_[$mf_sn]\" size=20></td></tr>" .
            '<tr><th align=right>' . _("Store in Folder:") . '</th><td>';
        $imapConnection = sqimap_login ($username, $key, $imapServerAddress, $imapPort, 0);
        $boxes = sqimap_mailbox_list($imapConnection);
        echo "<SELECT NAME=mf_subfolder>";
        $boxes = sqimap_mailbox_list($imapConnection);
        for ($i = 0; $i < count($boxes); $i++) {
            if (!in_array('noselect', $boxes[$i]['flags'])) {
                $box = $boxes[$i]['unformatted'];
                $box2 = str_replace(' ', '&nbsp;', $boxes[$i]['unformatted-disp']);
                if ( strtolower( $box2 ) == 'inbox' ) {
                    $box2 = _("INBOX");
                }
                echo '<OPTION ' .
                     (strcmp($mailfetch_subfolder_[$mf_sn],$box)==0?'SELECTED':'') .
                     " VALUE=\"$box\">$box2</option>\n";
            }
        }                
        echo '</SELECT></td></tr>' .
            '<tr><th align=right>&nbsp;</th>' .
            '<td><input type=checkbox name=mf_lmos ' . (($mailfetch_lmos_[$mf_sn] == 'on')?'checked':'') .
            '>' . _("Leave Mail on Server") . '</td></tr>' .
            '<tr><th align=right>&nbsp;</TH><td><input type=checkbox name=mf_login ' . ( ($mailfetch_login_[$mf_sn] == 'on')?'checked':'') .
            '>' . _("Check mail during login") . '</td></tr>' .
            '<tr><th align=right>&nbsp;</TH><td><input type=checkbox name=mf_fref ' . ( ($mailfetch_fref_[$mf_sn] == 'on')?'checked':'') .
            '>' . _("Check mail during folder refresh") . '</td></tr>' .
            '<tr><td align=center colspan=2><input type=submit name=submit_mailfetch value="' . _("Modify Server") . '"></td></tr>' .
            '</table></form></TD></TR></TABLE>';
        break;
    default:                                    //unsupported action
        echo '</form><TABLE WIDTH=95% COLS=1 ALIGN=CENTER>' .
            "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><b>" . _("Fetching Servers") . '</b></TD></TR>' .
            '</TABLE><BR>' .
            '<TABLE WIDTH=70% COLS=1 ALIGN=CENTER>' .
            "<TR><TD BGCOLOR=\"$color[9]\" ALIGN=CENTER><b>" . _("Undefined Function") . '</b></TD></TR>' .
            "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER><b>" .
            _("Hey! Wath do You are looking for?") . '</b></TD></TR></TABLE>';
    }

    ?>
</body></html>
