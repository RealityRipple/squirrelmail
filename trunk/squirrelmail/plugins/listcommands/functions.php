<?php

/**
 * functions.php
 *
 * Implementation of RFC 2369 for SquirrelMail.
 * When viewing a message from a mailinglist complying with this RFC,
 * this plugin displays a menu which gives the user a choice of mailinglist
 * commands such as (un)subscribe, help and list archives.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage listcommands
 */

/**
  * Get current list of subscribed non-RFC-compliant mailing lists for logged-in user
  *
  * @return array The list of mailing list addresses, keyed by integer index
  */
function get_non_rfc_lists() {
    global $username, $data_dir;
    $lists = getPref($data_dir, $username, 'non_rfc_lists', array());
    $new_lists = array();
    if (!empty($lists)) {
        $lists = explode(':', $lists);
        foreach ($lists as $list) {
            list($index, $list_addr) = explode('_', $list);
            if ((!empty($index) || $index === '0') && !empty($list_addr))
                $new_lists[$index] = $list_addr;
        }
    }
    $lists = $new_lists;
    sort($lists);
    return $lists;
}

/**
  * Show mailing list management option section on options page
  */
function plugin_listcommands_optpage_register_block_do()
{
    global $optpage_blocks, $listcommands_allow_non_rfc_list_management;

    // only allow management of non-RFC lists if admin deems necessary
    //
    @include_once(SM_PATH . 'plugins/listcommands/config.php');
    if (!$listcommands_allow_non_rfc_list_management)
        return;

    $optpage_blocks[] = array(
        'name' => _("Mailing Lists"),
        'url'  => '../plugins/listcommands/options.php',
        'desc' => _("Manage the (non-RFC-compliant) mailing lists that you are subscribed to for the purpose of providing one-click list replies when responding to list messages."),
        'js'   => false
    );

}

/**
 * internal function that builds mailing list links
 */
function plugin_listcommands_menu_do() {
    global $passed_id, $passed_ent_id, $mailbox, $message, 
           $startMessage, $oTemplate, $listcommands_allow_non_rfc_list_management;

    @include_once(SM_PATH . 'plugins/listcommands/config.php');

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
        // note that we only use the first cmd/action, ignore the rest
        $proto = array_shift($aActions);
        $act   = array_shift($actions);

        if ($proto == 'mailto') {

            $identity = '';

            if (($cmd == 'post') || ($cmd == 'owner')) {
                $url = 'src/compose.php?'.
                    (isset($startMessage)?'startMessage='.$startMessage.'&amp;':'');
            } else {
                $url = "plugins/listcommands/mailout.php?action=$cmd&amp;";

                // try to find which identity the mail should come from
                include_once(SM_PATH . 'functions/identity.php');
                $idents = get_identities();
                // ripped from src/compose.php
                $identities = array();
                if (count($idents) > 1) {
                    foreach($idents as $nr=>$data) {
                        $enc_from_name = '"'.$data['full_name'].'" <'. $data['email_address'].'>';
                        $identities[] = $enc_from_name;
                    }

                    $identity_match = $message->rfc822_header->findAddress($identities);
                    if ($identity_match !== FALSE) {
                        $identity = $identity_match;
                    }
                }
            }

            // if things like subject are given, peel them off and give
            // them to src/compose.php as is (not encoded)
            if (strpos($act, '?') > 0) {
               list($act, $parameters) = explode('?', $act, 2);
               $parameters = '&amp;identity=' . $identity . '&amp;' . $parameters;
            } else {
               $parameters = '&amp;identity=' . $identity;
            }

            $url .= 'send_to=' . urlencode($act) . $parameters;

            $links[$cmd] = makeComposeLink($url, $fieldsdescr[$cmd]);

            if ($cmd == 'post') {
                if (!isset($mailbox))
                    $mailbox = 'INBOX';
                $url .= '&amp;passed_id='.$passed_id.
                    '&amp;mailbox='.urlencode($mailbox).
                    (isset($passed_ent_id)?'&amp;passed_ent_id='.$passed_ent_id:'');
                $url .= '&amp;smaction=reply';

                $links['reply'] = makeComposeLink($url, $fieldsdescr['reply']);
            }
        } else if ($proto == 'href') {
            $links[$cmd] = create_hyperlink($act, $fieldsdescr[$cmd], '_blank');
        }
    }


    // allow non-rfc reply link if admin allows and message is from 
    // non-rfc list the user has configured
    //
    if ($listcommands_allow_non_rfc_list_management) {

        $non_rfc_lists = get_non_rfc_lists();

        $recipients = formatRecipientString($message->rfc822_header->to, "to") . ' '
                    . formatRecipientString($message->rfc822_header->cc, "cc") . ' '
                    . formatRecipientString($message->rfc822_header->bcc, "bcc");

        if (!in_array('post', array_keys($links))) {

            foreach ($non_rfc_lists as $non_rfc_list) {
                if (preg_match('/(^|,|<|\s)' . preg_quote($non_rfc_list) . '($|,|>|\s)/', $recipients)) {
                    $url = 'src/compose.php?'
                         . (isset($startMessage)?'startMessage='.$startMessage.'&amp;':'')
                         . 'send_to=' . str_replace('?','&amp;', $non_rfc_list);

                    $links['post'] = makeComposeLink($url, $fieldsdescr['post']);

                    break;
                }
            }

        }

        if (!in_array('reply', array_keys($links))) {

            foreach ($non_rfc_lists as $non_rfc_list) {
                if (preg_match('/(^|,|\s)' . preg_quote($non_rfc_list) . '($|,|\s)/', $recipients)) {
                    if (!isset($mailbox))
                        $mailbox = 'INBOX';
                    $url = 'src/compose.php?'
                         . (isset($startMessage)?'startMessage='.$startMessage.'&amp;':'')
                         . 'send_to=' . str_replace('?','&amp;', $non_rfc_list)
                         . '&amp;passed_id='.$passed_id
                         . '&amp;mailbox='.urlencode($mailbox)
                         . (isset($passed_ent_id)?'&amp;passed_ent_id='.$passed_ent_id:'')
                         . '&amp;smaction=reply';

                    $links['reply'] = makeComposeLink($url, $fieldsdescr['reply']);

                    break;
                }
            }

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

