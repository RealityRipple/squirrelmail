<?php

/**
 * functions.php
 *
 * Implementation of RFC 2369 for SquirrelMail.
 * When viewing a message from a mailinglist complying with this RFC,
 * this plugin displays a menu which gives the user a choice of mailinglist
 * commands such as (un)subscribe, help and list archives.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage listcommands
 */

/**
 * internal function that builds mailing list links
 */
function plugin_listcommands_menu_do() {
    global $passed_id, $passed_ent_id, $color, $mailbox, $message, $startMessage, $oTemplate;

    /**
     * Array of commands we can deal with from the header. The Reply option
     * is added later because we generate it using the Post information.
     */
    $fieldsdescr = listcommands_fieldsdescr();
    $links = array();

    foreach ($message->rfc822_header->mlist as $cmd => $actions) {

        /* I don't know this action... skip it */
        if ( !array_key_exists($cmd, $fieldsdescr) ) {
            continue;
        }

        /* proto = {mailto,href} */
        $aActions = array_keys($actions);
        $proto = array_shift($aActions);
        $act   = array_shift($actions);

        if ($proto == 'mailto') {

            if (($cmd == 'post') || ($cmd == 'owner')) {
                $url = 'src/compose.php?'.
                    (isset($startMessage)?'startMessage='.$startMessage.'&amp;':'');
            } else {
                $url = "plugins/listcommands/mailout.php?action=$cmd&amp;";
            }
            $url .= 'send_to=' . str_replace('?','&amp;', $act);

            $links[] = makeComposeLink($url, $fieldsdescr[$cmd]);

            if ($cmd == 'post') {
                if (!isset($mailbox))
                    $mailbox = 'INBOX';
                $url .= '&amp;passed_id='.$passed_id.
                    '&amp;mailbox='.urlencode($mailbox).
                    (isset($passed_ent_id)?'&amp;passed_ent_id='.$passed_ent_id:'');
                $url .= '&amp;smaction=reply';

                $links[] = makeComposeLink($url, $fieldsdescr['reply']);
            }
        } else if ($proto == 'href') {
            $oTemplate->assign('uri', $act);
            $oTemplate->assign('target', '_blank');
            $oTemplate->assign('text', $fieldsdescr[$cmd]);
            $oTemplate->assign('class', '');
            $oTemplate->assign('onclick', '');
            $output = $oTemplate->fetch('hyperlink.tpl');
            $links[] = $output;
        }
    }

    if (count($links) > 0) {
        $oTemplate->assign('links', $links);
        $output = $oTemplate->fetch('plugins/listcommands/read_body_header.tpl');
        return array('read_body_header' => $output);
    }

}

/**
 * Returns an array with the actions as translated strings.
 * @return array action as key, translated string as value
 */
function listcommands_fieldsdescr() {
    return array('post'   => _("Post to List"),
            'reply'       => _("Reply to List"),
            'subscribe'   => _("Subscribe"),
            'unsubscribe' => _("Unsubscribe"),
            'archive'     => _("List Archives"),
            'owner'       => _("Contact Listowner"),
            'help'        => _("Help"));
}

