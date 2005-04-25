<?php
/**
 * mail_fetch/options.php
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Setup of the mailfetch plugin.
 *
 * @version $Id$
 * @package plugins
 * @subpackage mail_fetch
 */

/** @ignore */
define('SM_PATH','../../');

require_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'functions/imap.php');

/* globals */
sqgetGlobalVar('username',   $username,   SQ_SESSION);
sqgetGlobalVar('key',        $key,        SQ_COOKIE);
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);
sqgetGlobalVar('delimiter',  $delimiter,  SQ_SESSION);

if(!sqgetGlobalVar('mf_cypher', $mf_cypher, SQ_POST)) {
    $mf_cypher = '';
}
if(! sqgetGlobalVar('mf_action', $mf_action, SQ_POST) ) {
    if (sqgetGlobalVar('mf_action_mod', $mf_action_mod, SQ_POST)) {
        $mf_action = 'Modify';
    }
    elseif (sqgetGlobalVar('mf_action_del', $mf_action_del, SQ_POST)) {
        $mf_action = 'Delete';
    }
    else {
        $mf_action = 'config';
    }
}

sqgetGlobalVar('mf_sn',            $mf_sn,            SQ_POST);
sqgetGlobalVar('mf_server',        $mf_server,        SQ_POST);
sqgetGlobalVar('mf_port',          $mf_port,          SQ_POST);
sqgetGlobalVar('mf_alias',         $mf_alias,         SQ_POST);
sqgetGlobalVar('mf_user',          $mf_user,          SQ_POST);
sqgetGlobalVar('mf_pass',          $mf_pass,          SQ_POST);
sqgetGlobalVar('mf_subfolder',     $mf_subfolder,     SQ_POST);
sqgetGlobalVar('mf_login',         $mf_login,         SQ_POST);
sqgetGlobalVar('mf_fref',          $mf_fref,          SQ_POST);
sqgetGlobalVar('mf_lmos',          $mf_lmos,          SQ_POST);
sqgetGlobalVar('submit_mailfetch', $submit_mailfetch, SQ_POST);


/* end globals */

displayPageHeader( $color, 'None' );

switch( $mf_action ) {
 case 'add':
     if ($mf_sn<1) $mf_sn=0;
     if (!isset($mf_server)) return;
     setPref($data_dir,$username,"mailfetch_server_$mf_sn", (isset($mf_server)?$mf_server:""));
     setPref($data_dir,$username,"mailfetch_port_$mf_sn", (isset($mf_port)?$mf_port:110));
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
     setPref($data_dir,$username,"mailfetch_port_$mf_sn", (isset($mf_port)?$mf_port:110));
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
             setPref($data_dir,$username,'mailfetch_server_'.$i,
                     getPref($data_dir,$username, 'mailfetch_server_'.$tmp));
             setPref($data_dir,$username,'mailfetch_port_'.$i,
                     getPref($data_dir,$username, 'mailfetch_port_'.$tmp));
             setPref($data_dir,$username,'mailfetch_alias_'.$i,
                     getPref($data_dir,$username, 'mailfetch_alias_'.$tmp));
             setPref($data_dir,$username,'mailfetch_user_'.$i,
                     getPref($data_dir,$username, 'mailfetch_user_'.$tmp));
             setPref($data_dir,$username,'mailfetch_pass_'.$i,
                     getPref($data_dir,$username, 'mailfetch_pass_'.$tmp));
             setPref($data_dir,$username,'mailfetch_lmos_'.$i,
                     getPref($data_dir,$username, 'mailfetch_lmos_'.$tmp));
             setPref($data_dir,$username,'mailfetch_login_'.$i,
                     getPref($data_dir,$username, 'mailfetch_login_'.$tmp));
             setPref($data_dir,$username,'mailfetch_fref_'.$i,
                     getPref($data_dir,$username, 'mailfetch_fref_'.$tmp));
             setPref($data_dir,$username,'mailfetch_subfolder_'.$i,
                     getPref($data_dir,$username, 'mailfetch_subfolder_'.$tmp));
             setPref($data_dir,$username,'mailfetch_uidl_'.$i,
                     getPref($data_dir,$username, 'mailfetch_uidl_'.$tmp));
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

echo '<br /><form method="post" action="'.$PHP_SELF.'">' .
    html_tag( 'table',
        html_tag( 'tr',
            html_tag('td',
                     '<b>' . _("Remote POP server settings") . '</b>',
                     'center', $color[0] )
                  ),
              'center', '', 'width="95%"' ) .
    html_tag( 'table',
        html_tag( 'tr',
            html_tag( 'td',
                      _("You should be aware that the encryption used to store your password is not perfectly secure. However, if you are using pop, there is inherently no encryption anyway. Additionally, the encryption that we do to save it on the server can be undone by a hacker reading the source to this file.") ,
                      'left' )
                  ) .
        html_tag( 'tr',
            html_tag( 'td',
                      _("If you leave password empty, it will be asked when you fetch mail.") ,
                      'left' )
                  ) .
        html_tag( 'tr',
            html_tag( 'td',
                      '<input type="checkbox" name="mf_cypher" ' .
                      (($mailfetch_cypher=='on')?'checked="checked" />':' />') .
                      _("Encrypt passwords (informative only)") ,
                      'right' )
                  ) ,
              'center', '', 'width="95%"' );

switch( $mf_action ) {
 case 'config':
     echo html_tag( 'table', '', 'center', '', 'width="70%" cellpadding="5" cellspacing="1"' ) .
         html_tag( 'tr',
                   html_tag( 'td', '<b>' . _("Add Server") . '</b>', 'center', $color[9] )
                   ) .
         html_tag( 'tr' ) .
         html_tag( 'td', '', 'center', $color[0] ) .

         "<input type=\"hidden\" name=\"mf_sn\" value=\"$mailfetch_server_number\" />" .
         '<input type="hidden" name="mf_action" value="add" />' .
         html_tag( 'table' ) .
         html_tag( 'tr',
             html_tag( 'th', _("Server:"), 'right' ) .
             html_tag( 'td', '<input type="text" name="mf_server" value="" size="40" />', 'left' )
                   ) .
         html_tag( 'tr',
             html_tag( 'th', _("Port:"), 'right') .
             html_tag( 'td', '<input type="text" name="mf_port" value="110" size="20" />', 'left')
                   ) .
         html_tag( 'tr',
             html_tag( 'th', _("Alias:"), 'right' ) .
             html_tag( 'td', '<input type="text" name="mf_alias" value="" size="20" />', 'left' )
                   ) .
         html_tag( 'tr',
             html_tag( 'th', _("Username:"), 'right' ) .
             html_tag( 'td', '<input type="text" name="mf_user" value="" size="20" />', 'left' )
                   ) .
         html_tag( 'tr',
             html_tag( 'th', _("Password:"), 'right' ) .
             html_tag( 'td', '<input type="password" name="mf_pass" value="" size="20" />', 'left' )
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
             html_tag( 'td', '<input type="checkbox" name="mf_lmos" checked="checked" />' . _("Leave Mail on Server"), 'left' )
                   ) .
         html_tag( 'tr',
             html_tag( 'th', '&nbsp;', 'right' ) .
             html_tag( 'td', '<input type="checkbox" name="mf_login" />' . _("Check mail at login"), 'left' )
                   ) .
         html_tag( 'tr',
             html_tag( 'th', '&nbsp;', 'right' ) .
             html_tag( 'td', '<input type="checkbox" name="mf_fref" />' . _("Check mail at folder refresh"), 'left' )
                   ) .
         html_tag( 'tr',
             html_tag( 'td',
                       '<input type="submit" name="submit_mailfetch" value="' . _("Add Server") . '" />',
                       'center', '', 'colspan="2"' )
                   ) .
         '</table></td></tr></table></form>';

     // Modify Server
     echo '<font size="-5"><br /></font>' .
         html_tag( 'table', '', 'center', '', 'width="70%" cellpadding="5" cellspacing="1"' ) .
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
                 htmlspecialchars( (($mailfetch_alias_[$i]=='')?$mailfetch_server_[$i]:$mailfetch_alias_[$i])) . "</option>";
         }
         echo '</select>'.
             '&nbsp;&nbsp;<input type="submit" name="mf_action_mod" value="' . _("Modify") . '" />'.
             '&nbsp;&nbsp;<input type="submit" name="mf_action_del" value="' . _("Delete") . '" />'.
             '</form>';
     } else {
         echo _("No servers known.");
     }
     echo '</td></tr></table>';
     break;
 case 'Delete':                                     //erase confirmation about a server
     echo html_tag( 'table',
              html_tag( 'tr',
                  html_tag( 'td', '<b>' . _("Fetching Servers") . '</b>', 'center', $color[0] )
                        ) ,
                    'center', '', 'width="95%" cellpadding="5" cellspacing="1"' ) .
         '<br />' .
         html_tag( 'table',
             html_tag( 'tr',
                 html_tag( 'td', '<b>' . _("Confirm Deletion of a Server") . '</b>', 'center', $color[9] )
                       ) .
             html_tag( 'tr',
                 html_tag( 'td',
                     "<input type=\"hidden\" name=\"mf_sn\" value=\"$mf_sn\" />" .
                     '<input type="hidden" name="mf_action" value="confirm_delete" />' .
                     '<br />' . _("Selected Server:") . " <b>" . htmlspecialchars($mailfetch_server_[$mf_sn]) . "</b><br />" .
                     _("Confirm delete of selected server?") . '<br /><br />' .
                     '<input type="submit" name="submit_mailfetch" value="' . _("Confirm Delete") . '" />' .
                     '<br /></form>' ,
                           'center', $color[9] )
                       ) ,
                   'center', '', 'width="70%" cellpadding="5" cellspacing="1"' );
     break;                                  //modify a server
 case 'Modify':
     echo html_tag( 'table',
              html_tag( 'tr',
                  html_tag( 'td', '<b>' . _("Fetching Servers") . '</b>', 'center', $color[0] )
                        ) ,
                    'center', '', 'width="95%" cellpadding="5" cellspacing="1"' ) .
         '<br />' .
         html_tag( 'table', '', 'center', '', 'width="70%" cellpadding="5" cellspacing="1"' ) .
             html_tag( 'tr',
                 html_tag( 'td', '<b>' . _("Modify Server") . '</b>', 'center', $color[9] )
                       ) .
             html_tag( 'tr' ) .
                 html_tag( 'td', '', 'center', $color[0] ) .

         "<input type=\"hidden\" name=\"mf_sn\" value=\"$mf_sn\" />" .
         '<input type="hidden" name="mf_action" value="confirm_modify" />' .
         html_tag( 'table' ) .
             html_tag( 'tr',
                 html_tag( 'th', _("Server:"), 'right' ) .
                 html_tag( 'td', '<input type="text" name="mf_server" value="' .
                           htmlspecialchars($mailfetch_server_[$mf_sn]) . '" size="40" />', 'left' )
                       ) .
             html_tag( 'tr',
                 html_tag( 'th', _("Port:"), 'right' ) .
                 html_tag( 'td', '<input type="text" name="mf_port" value="' .
                           htmlspecialchars($mailfetch_port_[$mf_sn]) . '" size="40" />', 'left' )
                       ) .
             html_tag( 'tr',
                 html_tag( 'th', _("Alias:"), 'right' ) .
                 html_tag( 'td', '<input type="text" name="mf_alias" value="' .
                           htmlspecialchars($mailfetch_alias_[$mf_sn]) . '" size="40" />', 'left' )
                       ) .
             html_tag( 'tr',
                 html_tag( 'th', _("Username:"), 'right' ) .
                 html_tag( 'td', '<input type="text" name="mf_user" value="' .
                           htmlspecialchars($mailfetch_user_[$mf_sn]) . '" size="20" />', 'left' )
                       ) .
             html_tag( 'tr',
                 html_tag( 'th', _("Password:"), 'right' ) .
                 html_tag( 'td', '<input type="password" name="mf_pass" value="' .
                           htmlspecialchars($mailfetch_pass_[$mf_sn]) . '" size="20" />', 'left' )
                       ) .
             html_tag( 'tr' ) .
                 html_tag( 'th', _("Store in Folder:"), 'right' ) .
                 html_tag( 'td', '', 'left' );

     $imapConnection = sqimap_login ($username, $key, $imapServerAddress, $imapPort, 0);
     $boxes = sqimap_mailbox_list($imapConnection);
     echo '<select name="mf_subfolder">';
     $selected = 0;
     if ( isset($mailfetch_subfolder_[$mf_sn]) ) {
         $selected = array(strtolower($mailfetch_subfolder_[$mf_sn]));
     }
     echo sqimap_mailbox_option_list($imapConnection, $selected) .
         '</select></td></tr>' .
         html_tag( 'tr',
             html_tag( 'th', '&nbsp;', 'right' ) .
             html_tag( 'td',
                       '<input type="checkbox" name="mf_lmos" ' . (($mailfetch_lmos_[$mf_sn] == 'on')?'checked="checked"':'') .
                       ' />' . _("Leave Mail on Server") ,
                       'left' )
                   ) .
         html_tag( 'tr',
             html_tag( 'th', '&nbsp;', 'right' ) .
             html_tag( 'td',
                       '<input type="checkbox" name="mf_login" ' . ( ($mailfetch_login_[$mf_sn] == 'on')?'checked="checked"':'') .
                       ' />' . _("Check mail at login"),
                       'left' )
                   ) .
         html_tag( 'tr',
             html_tag( 'th', '&nbsp;', 'right' ) .
             html_tag( 'td',
                       '<input type="checkbox" name="mf_fref" ' . ( ($mailfetch_fref_[$mf_sn] == 'on')?'checked="checked"':'') .
                       ' />' . _("Check mail at folder refresh") ,
                       'left' )
                   ) .
         html_tag( 'tr',
             html_tag( 'td',
                       '<input type="submit" name="submit_mailfetch" value="' . _("Modify Server") . '" />',
                       'center', '', 'colspan="2"' )
                   ) .
         '</table></form></td></tr></table>';
     break;
 default:  //unsupported action
     echo '</form>' .
         html_tag( 'table',
             html_tag( 'tr',
                 html_tag( 'td', '<b>' . _("Fetching Servers") . '</b>', 'center', $color[0] )
                       ) ,
                   'center', '', 'width="95%"' ) .
         '<br />' .
         html_tag( 'table',
             html_tag( 'tr',
                 html_tag( 'td', '<b>' . _("Undefined Function") . '</b>', 'center', $color[9] ) .
                 html_tag( 'td', '<b>' . _("The function you requested is unknown.") . '</b>', 'center', $color[0] )
                       ) ,
                   'center', '', 'width="70%"' );
}
?>
</body></html>