<?php
/*
 * Listcommands plugin v1.3
 *
 * Implementation of RFC 2369 for SquirrelMail.
 * When viewing a message from a mailinglist complying with this RFC,
 * this plugin displays a menu which gives the user a choice of mailinglist
 * commands such as (un)subscribe, help and list archives.
 *
 * last modified: 2002/01/21 by Thijs Kinkhorst
 * please send bug reports to <thijs@kinkhorst.com>
 *
 */
function squirrelmail_plugin_init_listcommands ()
{
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['read_body_header']['listcommands'] = 'plugin_listcommands_menu';
}


function plugin_listcommands_menu () {

    global $imapConnection, $passed_id, $color, $mailbox,
           $subject, $ent_num, $priority_level;

    /* Array of commands we can deal with from the header. The Reply option is
     * added later because we generate it using the Post information.
     */
    $fieldsdescr = array( 'Help' => _("Help"),
                          'Unsubscribe' => _("Unsubscribe"),
                          'Subscribe' => _("Subscribe"),
                          'Post' => _("Post to the list"),
                          'Archive' => _("List Archives"),
                          'Owner' => _("Contact Listowner") );
    $fields = array_keys ($fieldsdescr);
    $fieldsdescr['Reply'] = _("Reply to the list");

    $cmds = array();
    $output = array();

    $lfields = 'List-' . implode (' List-', $fields);

    $sid = sqimap_session_id();
    fputs ($imapConnection, "$sid FETCH $passed_id BODY.PEEK[HEADER.FIELDS ($lfields)]\r\n");
    $read = sqimap_read_data ($imapConnection, $sid, true, $response, $emessage);

    for ($i = 1; $i < count($read); $i++) {
        foreach ($fields as $field) {
            if ( preg_match("/^List-$field: *<(.+?)>/i", $read[$i], $match) ) {
                $cmds[$field] = $match[1];
            }
        }
    }

    foreach ($cmds as $cmd => $url) {
        if ( eregi('mailto:(.+)', $url, $regs) ) {
            $purl = parse_url($url);

            if ( $cmd == 'Post' || $cmd == 'Owner' ) {
                $url = 'compose.php?';
            } else {
                $url = '../plugins/listcommands/mailout.php?action=' . $cmd . '&';
            }

            $url .= 'mailbox=' . urlencode($mailbox) . '&send_to=' . $purl['path'];

            if ( isset($purl['query']) ) {
                $url .= '&' . $purl['query'];
            }

            $output[] = '<A HREF="' . $url . '">' . $fieldsdescr[$cmd] . '</A>';

            if ( $cmd == 'Post' ) {
                $url .= '&reply_subj=' . urlencode($subject) .
                        '&reply_id=' . $passed_id .
                        '&ent_num=' . $ent_num .
                        '&mailprio=' . $priority_level;
                $output[] = '<A HREF="' . $url . '">' . $fieldsdescr['Reply'] . '</A>';
            }
        } elseif ( eregi('^(http|ftp)', $url) ) {
            $output[] = '<A HREF="' . $url . '" TARGET="_blank">' . $fieldsdescr[$cmd] . '</A>';
        }
    }

    if (count($output) > 0) {
        echo "<tr><td BGCOLOR=\"$color[0]\" WIDTH=\"100%\" colspan=\"3\">".
             '<SMALL>' . _("Mailinglist options:") . ' ' . implode ('&nbsp;|&nbsp;', $output) .
             '</SMALL>'.
             '</td></tr>';
    }
}
?>