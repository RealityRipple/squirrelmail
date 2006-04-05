<?php

/**
 * constants.php
 *
 * Loads constants used by the rest of the SquirrelMail source.
 * This file is include by src/login.php, src/redirect.php and
 * src/load_prefs.php.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @since 1.2.0
 */

/** @ignore */

/**************************************************************/
/* Set values for constants used by SquirrelMail preferences. */
/**************************************************************/

/**
 * Define basic, general purpose preference constants.
 * @since 1.2.0
 */
define('SMPREF_NO', 0);
define('SMPREF_OFF', 0);
define('SMPREF_YES', 1);
define('SMPREF_ON', 1);
define('SMPREF_NONE', 'none');

/**
 * Define constants for location based preferences.
 * @since 1.2.0
 */
define('SMPREF_LOC_TOP', 'top');
define('SMPREF_LOC_BETWEEN', 'between');
define('SMPREF_LOC_BOTTOM', 'bottom');
define('SMPREF_LOC_LEFT', '');
define('SMPREF_LOC_RIGHT', 'right');

/**
 * Define preferences for folder settings.
 * @since 1.2.0
 */
define('SMPREF_UNSEEN_NONE', 1);
define('SMPREF_UNSEEN_INBOX', 2);
define('SMPREF_UNSEEN_ALL', 3);
define('SMPREF_UNSEEN_SPECIAL', 4); // Only special folders (since 1.2.5)
define('SMPREF_UNSEEN_NORMAL', 5);  // Only normal folders (since 1.2.5)
define('SMPREF_UNSEEN_ONLY', 1);
define('SMPREF_UNSEEN_TOTAL', 2);

/**
 * Define constants for time/date display preferences.
 * @since 1.2.0
 */
define('SMPREF_TIME_24HR', 1);
define('SMPREF_TIME_12HR', 2);

/**
 * Define constants for javascript preferences.
 * @since 1.2.0
 */
define('SMPREF_JS_OFF', 0);
define('SMPREF_JS_ON', 1);
define('SMPREF_JS_AUTODETECT', 2);

/**
 * default value for page_selector_max
 * @since 1.5.1
 */
define('PG_SEL_MAX', 10);


/**
 * The number of pages to cache msg headers
 * @since 1.5.1
 */
define('SQM_MAX_PAGES_IN_CACHE',5);

/**
 * The number of mailboxes to cache msg headers
 * @since 1.5.1
 */
define('SQM_MAX_MBX_IN_CACHE',3);

/**
 * Sort constants used for sorting of messages
 * @since 1.5.1
 */
define('SQSORT_NONE',0);
define('SQSORT_DATE_ASC',1);
define('SQSORT_DATE_DESC',2);
define('SQSORT_FROM_ASC',3);
define('SQSORT_FROM_DESC',4);
define('SQSORT_SUBJ_ASC',5);
define('SQSORT_SUBJ_DESC',6);
define('SQSORT_SIZE_ASC',7);
define('SQSORT_SIZE_DESC',8);
define('SQSORT_TO_ASC',9);
define('SQSORT_TO_DESC',10);
define('SQSORT_CC_ASC',11);
define('SQSORT_CC_DESC',12);
define('SQSORT_INT_DATE_ASC',13);
define('SQSORT_INT_DATE_DESC',14);

/**
 * Special sort constant thread which is added to above sort mode.
 * By doing a bitwise check ($sort & SQSORT_THREAD) we know if the mailbox
 * is sorted by thread.
 * @since 1.5.1
 */
define('SQSORT_THREAD',32);

/**
 * Mailbox preference array keys
 * @since 1.5.1
 */
define('MBX_PREF_SORT',0);
define('MBX_PREF_LIMIT',1);
define('MBX_PREF_AUTO_EXPUNGE',2);
define('MBX_PREF_INTERNALDATE',3);
define('MBX_PREF_COLUMNS',4);
// define('MBX_PREF_FUTURE',unique integer key);

/**
 * Email address array keys
 * @since 1.5.1
 */
define('SQM_ADDR_PERSONAL', 0);
define('SQM_ADDR_ADL',      1);
define('SQM_ADDR_MAILBOX',  2);
define('SQM_ADDR_HOST',     3);

/**
 * Supported columns to show in a messages list
 * The MBX_PREF_COLUMNS contains an ordered array with these columns
 * @since 1.5.1
 */
define('SQM_COL_CHECK',0);
define('SQM_COL_FROM',1);
define('SQM_COL_DATE', 2);
define('SQM_COL_SUBJ', 3);
define('SQM_COL_FLAGS', 4);
define('SQM_COL_SIZE', 5);
define('SQM_COL_PRIO', 6);
define('SQM_COL_ATTACHMENT', 7);
define('SQM_COL_INT_DATE', 8);
define('SQM_COL_TO', 9);
define('SQM_COL_CC', 10);
define('SQM_COL_BCC', 11);

/** @since 1.2.0 */
do_hook('loading_constants');

?>