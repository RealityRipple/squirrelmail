<?php

/**
 * setup.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Implementation of RFC 2369 for SquirrelMail.
 * When viewing a message from a mailinglist complying with this RFC,
 * this plugin displays a menu which gives the user a choice of mailinglist
 * commands such as (un)subscribe, help and list archives.
 *
 * $Id$
 */

function squirrelmail_plugin_init_listcommands () {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['read_body_header']['listcommands'] = 'plugin_listcommands_menu';
}

function plugin_listcommands_menu() {
    global $passed_id, $passed_ent_id, $color, $mailbox,
           $message, $compose_new_win;

    /**
     * Array of commands we can deal with from the header. The Reply option
     * is added later because we generate it using the Post information.
     */
    $fieldsdescr = array('post'        => _("Post to List"),
                         'reply'       => _("Reply to List"),
                         'subscribe'   => _("Subscribe"),
                         'unsubscribe' => _("Unsubscribe"),
                         'archive'     => _("List Archives"),
                         'owner'       => _("Contact Listowner"),
                         'help'        => _("Help"));
    $output = array();

    foreach ($message->rfc822_header->mlist as $cmd => $actions) {

	/* I don't know this action... skip it */
	if(!array_key_exists($cmd, $fieldsdescr)) {
            continue;
        }

        /* proto = {mailto,href} */
	$proto = array_shift(array_keys($actions));
	$act   = array_shift($actions);

        if ($proto == 'mailto') {

            if (($cmd == 'post') || ($cmd == 'owner')) {
                $url = 'compose.php?';
            } else {
                $url = "../plugins/listcommands/mailout.php?action=$cmd&amp;";
            }
            $url .= 'send_to=' . strtr($act,'?','&');

            if ($compose_new_win == '1') {
                $output[] = "<a href=\"javascript:void(0)\" onclick=\"comp_in_new('$url')\">" . $fieldsdescr[$cmd] . '</A>';
            }
            else {
                $output[] = '<A HREF="' . $url . '">' . $fieldsdescr[$cmd] . '</A>';
            }
            if ($cmd == 'post') {
	        $url .= '&amp;passed_id='.$passed_id.
		        '&amp;mailbox='.urlencode($mailbox).
		        (isset($passed_ent_id)?'&amp;passed_ent_id='.$passed_ent_id:'');
                $url .= '&amp;action=reply';
                if ($compose_new_win == '1') {
                    $output[] = "<A HREF=\"javascript:void(0)\" onClick=\"comp_in_new('$url')\">" . $fieldsdescr['reply'] . '</A>';
                } else {
                    $output[] = '<A HREF="' . $url . '">' . $fieldsdescr['reply'] . '</A>';
                }
            }
        } else if ($proto == 'href') {
            $output[] = '<A HREF="' . $act . '" TARGET="_blank">'
                      . $fieldsdescr[$cmd] . '</A>';
        }
    }

    if (count($output) > 0) {
        echo '<TR>';
        echo html_tag('TD', '<B>' . _("Mailing List") . ':&nbsp;&nbsp;</B>',
                      'right', '', 'VALIGN="MIDDLE" WIDTH="20%"') . "\n";
        echo html_tag('TD', '<SMALL>' . implode('&nbsp;|&nbsp;', $output) . '</small>',
                      'left', $color[0], 'VALIGN="MIDDLE" WIDTH="80%"') . "\n";
        echo "</TR>";
    }
}

?>
