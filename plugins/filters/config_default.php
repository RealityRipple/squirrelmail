<?php

/**
 * Message and Spam Filter Plugin - Setup script
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage filters
 */

/**
 * Imap connection control
 *
 * Set this to true if you have problems -- check the README file
 * Note:  This doesn't work all of the time (No idea why)
 *        Seems to be related to UW
 * @global bool $UseSeparateImapConnection
 */
$UseSeparateImapConnection = false;

/**
 * User level spam filters control
 *
 * Set this to false if you do not want the user to be able to enable
 * spam filters
 * @global bool $AllowSpamFilters
 */
$AllowSpamFilters = true;

/**
 * SpamFilters YourHop Setting
 *
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
 * @global string $SpamFilters_YourHop
 */
$SpamFilters_YourHop = ' ';

/**
 * Commercial Spam Filters Control
 *
 * Some of the SPAM filters are COMMERCIAL and require a fee. If your users
 * select them and you're not allowed to use them, it will make SPAM filtering
 * very slow. If you don't want them to even be offered to the users, you
 * should set SpamFilters_ShowCommercial to false.
 * @global bool $SpamFilters_ShowCommercial
 */
$SpamFilters_ShowCommercial = false;

/**
 * SpamFiltering Cache
 *
 * A cache of IPs we've already checked or are known bad boys or good boys
 * ie. $SpamFilters_DNScache["210.54.220.18"] = true;
 * would tell filters to not even bother doing the DNS queries for that
 * IP and any email coming from it are SPAM - false would mean that any
 * email coming from it would NOT be SPAM
 * @global array $SpamFilters_DNScache
 */
$SpamFilters_DNScache=array();

/**
 * Path to bulkquery program
 *
 * Absolute path to the bulkquery program. Leave blank if you don't have
 * bulkquery compiled, installed, and lwresd running. See the README file
 * in the bulkquery directory for more information on using bulkquery.
 * @global string $SpamFilters_BulkQuery
 */
$SpamFilters_BulkQuery = '';

/**
 * Shared filtering cache control
 *
 * Do you want to use a shared file for the DNS cache or a session variable?
 * Using a shared file means that every user can benefit from any queries
 * made by other users. The shared file is named "dnscache" and is in the
 * data directory.
 * @global bool $SpamFilters_SharedCache
 */
$SpamFilters_SharedCache = true;

/**
 * DNS query TTL
 *
 * How long should DNS query results be cached for by default (in seconds)?
 * @global integer $SpamFilters_CacheTTL
 */
$SpamFilters_CacheTTL = 7200;

