<?php
/**
 * Message and Spam Filter Plugin
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This plugin filters your inbox into different folders based upon given
 * criteria.  It is most useful for people who are subscibed to mailing lists
 * to help organize their messages.  The argument stands that filtering is
 * not the place of the client, which is why this has been made a plugin for
 * SquirrelMail.  You may be better off using products such as Sieve or
 * Procmail to do your filtering so it happens even when SquirrelMail isn't
 * running.
 *
 * If you need help with this, or see improvements that can be made, please
 * email me directly at the address above.  I definately welcome suggestions
 * and comments.  This plugin, as is the case with all SquirrelMail plugins,
 * is not directly supported by the developers.  Please come to me off the
 * mailing list if you have trouble with it.
 *
 * Also view plugins/README.plugins for more information.
 *
 * $Id$
 */

/*
 * Set this to true if you have problems -- check the README file
 * Note:  This doesn't work all of the time (No idea why)
 *        Seems to be related to UW
 */
global $UseSeparateImapConnection;
$UseSeparateImapConnection = false;

/*
 * Set this to false if you do not want the user to be able to enable
 * spam filters
 */
global $AllowSpamFilters;
$AllowSpamFilters = true;

/*
 * Set this to a string containing something unique to the line in the
 * header you want me to find IPs to scan the databases with.  For example,
 * All the email coming IN from the internet to my site has a line in
 * the header that looks like (all on one line):
 * Received: [from usw-sf-list1.sourceforge.net (usw-sf-fw2.sourceforge.net
 *    [216.136.171.252]) by firewall.persistence.com (SYSADMIN-antispam
 *     0.2) with
 * Since this line indicates the FIRST hop the email takes into my network,
 * I set my SpamFilters_YourHop to 'by firewall.persistence.com' but any
 * case-sensitive string will do.  You can set it to something found on
 * every line in the header (like ' ') if you want to scan all IPs in
 * the header (lots of false alarms here tho).
 */
global $SpamFilters_YourHop;
$SpamFilters_YourHop = ' ';

/*
 * Some of the SPAM filters are COMMERCIAL and require a fee. If your users
 * select them and you're not allowed to use them, it will make SPAM filtering
 * very slow. If you don't want them to even be offered to the users, you
 * set SpamFilters_ShowCommercial to false.
 */
global $SpamFilters_ShowCommercial;
$SpamFilters_ShowCommercial = true;

/*
 * A cache of IPs we've already checked or are known bad boys or good boys
 * ie. $SpamFilters_DNScache["210.54.220.18"] = true;
 * would tell filters to not even bother doing the DNS queries for that
 * IP and any email coming from it are SPAM - false would mean that any
 * email coming from it would NOT be SPAM
 */
global $SpamFilters_DNScache;

require_once ('../plugins/filters/filters.php');

function squirrelmail_plugin_init_filters() {
    global $squirrelmail_plugin_hooks;
    global $mailbox, $imap_stream, $imapConnection;

    $squirrelmail_plugin_hooks['left_main_before']['filters'] = 'start_filters';
    if ($mailbox == 'INBOX') {
        $squirrelmail_plugin_hooks['right_main_after_header']['filters'] = 'start_filters';
    }
    $squirrelmail_plugin_hooks['optpage_register_block']['filters'] = 'squirrelmail_plugin_optpage_register_block';
#    $squirrelmail_plugin_hooks['special_mailbox']['filters'] = 'filters_special_mailbox';
//    $squirrelmail_plugin_hooks['rename_or_delete_folder']['filters'] = 'update_for_folder';
}

function filters_special_mailbox( $mb ) {
    GLOBAL $data_dir, $username;

    return( $mb == getPref($data_dir, $username, 'filters_spam_folder', 'na' ) );

}

function squirrelmail_plugin_optpage_register_block() {
    global $optpage_blocks;
    global $AllowSpamFilters;

    $optpage_blocks[] = array(
        'name' => _("Message Filters"),
        'url'  => '../plugins/filters/options.php',
        'desc' => _("Filtering enables messages with different criteria to be automatically filtered into different folders for easier organization."),
        'js'   => false
    );

    if ($AllowSpamFilters) {
        $optpage_blocks[] = array(
            'name' => _("SPAM Filters"),
            'url'  => '../plugins/filters/spamoptions.php',
            'desc' => _("SPAM filters allow you to select from various DNS based blacklists to detect junk email in your INBOX and move it to another folder (like Trash)."),
            'js'   => false
        );
    }
}
?>
