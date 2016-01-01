<?php

/**
 * constants.php
 *
 * Loads constants used by the rest of the SquirrelMail source.
 *
 * Before 1.5.2 script was stored in functions/constants.php
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @since 1.2.0
 */

/** @ignore */

/**
 * SquirrelMail version number -- DO NOT CHANGE
 * @since 1.5.2
 */
define('SM_VERSION', '1.5.2 [SVN]');

/**
 * Year interval for copyright notices in the interface
 * @since 1.5.2
 */
define('SM_COPYRIGHT', '1999-2016');

/**************************************************************/
/* Set values for constants used by SquirrelMail preferences. */
/**************************************************************/

/**
 * Define constants for SquirrelMail debug modes.
 * Note that these are binary so that modes can be
 * mixed and matched, and they are also ordered from
 * minor to severe.  When adding new modes, please
 * order them in a sensical way (MODERATE is the 10th
 * bit; ADVANCED is the 20th bit).
 * @since 1.5.2
 */
define('SM_DEBUG_MODE_OFF', 0);             // complete error suppression
define('SM_DEBUG_MODE_SIMPLE', 1);          // PHP E_ERROR
define('SM_DEBUG_MODE_MODERATE', 512);      // PHP E_ALL
define('SM_DEBUG_MODE_ADVANCED', 524288);   // PHP E_ALL plus log errors intentionally suppressed
define('SM_DEBUG_MODE_STRICT', 536870912);  // PHP E_STRICT

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

define('SMPREF_MAILBOX_SELECT_LONG', 0);
define('SMPREF_MAILBOX_SELECT_INDENTED', 1);
define('SMPREF_MAILBOX_SELECT_DELIMITED', 2);

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

/**
 * Address book field list
 * @since 1.4.16 and 1.5.2
 */
define('SM_ABOOK_FIELD_NICKNAME', 0);
define('SM_ABOOK_FIELD_FIRSTNAME', 1);
define('SM_ABOOK_FIELD_LASTNAME', 2);
define('SM_ABOOK_FIELD_EMAIL', 3);
define('SM_ABOOK_FIELD_LABEL', 4);

/**
 * Generic variable type constants
 * @since 1.5.2
 */
define('SQ_TYPE_INT', 'int');
define('SQ_TYPE_BIGINT', 'bigint');
define('SQ_TYPE_STRING', 'string');
define('SQ_TYPE_BOOL', 'bool');
define('SQ_TYPE_ARRAY', 'array');

/**
 * Template engines supported 
 * @since 1.5.2
 */
define('SQ_PHP_TEMPLATE', 'PHP_');
define('SQ_SMARTY_TEMPLATE', 'Smarty_');

/**
 * Used by plugins to indicate an incompatibility with a SM version
 * @since 1.5.2
 */
define('SQ_INCOMPATIBLE', 'INCOMPATIBLE');

/**
 * Define constants used in the options code
 */

// Define constants for the various option types
define('SMOPT_TYPE_STRING', 0);
define('SMOPT_TYPE_STRLIST', 1);
define('SMOPT_TYPE_TEXTAREA', 2);
define('SMOPT_TYPE_INTEGER', 3);
define('SMOPT_TYPE_FLOAT', 4);
define('SMOPT_TYPE_BOOLEAN', 5);
define('SMOPT_TYPE_HIDDEN', 6);
define('SMOPT_TYPE_COMMENT', 7);
define('SMOPT_TYPE_FLDRLIST', 8);
define('SMOPT_TYPE_FLDRLIST_MULTI', 9);
define('SMOPT_TYPE_EDIT_LIST', 10);
define('SMOPT_TYPE_EDIT_LIST_ASSOCIATIVE', 11);
define('SMOPT_TYPE_STRLIST_MULTI', 12);
define('SMOPT_TYPE_BOOLEAN_CHECKBOX', 13);
define('SMOPT_TYPE_BOOLEAN_RADIO', 14);
define('SMOPT_TYPE_STRLIST_RADIO', 15);
define('SMOPT_TYPE_SUBMIT', 16);
define('SMOPT_TYPE_INFO', 17);
define('SMOPT_TYPE_PASSWORD', 18);

// Define constants for the layout scheme for edit lists
define('SMOPT_EDIT_LIST_LAYOUT_LIST', 0);
define('SMOPT_EDIT_LIST_LAYOUT_SELECT', 1);

// Define constants for the options refresh levels
define('SMOPT_REFRESH_NONE', 0);
define('SMOPT_REFRESH_FOLDERLIST', 1);
define('SMOPT_REFRESH_ALL', 2);

// Define constants for the options size
define('SMOPT_SIZE_TINY', 0);
define('SMOPT_SIZE_SMALL', 1);
define('SMOPT_SIZE_MEDIUM', 2);
define('SMOPT_SIZE_LARGE', 3);
define('SMOPT_SIZE_HUGE', 4);
define('SMOPT_SIZE_NORMAL', 5);

// Define miscellaneous options constants 
define('SMOPT_SAVE_DEFAULT', 'save_option');
define('SMOPT_SAVE_NOOP', 'save_option_noop');

// Convenience array of values 'a' through 'z'
$a_to_z = array(
              'a' => 'a',
              'b' => 'b',
              'c' => 'c',
              'd' => 'd',
              'e' => 'e',
              'f' => 'f',
              'g' => 'g',
              'h' => 'h',
              'i' => 'i',
              'j' => 'j',
              'k' => 'k',
              'l' => 'l',
              'm' => 'm',
              'n' => 'n',
              'o' => 'o',
              'p' => 'p',
              'q' => 'q',
              'r' => 'r',
              's' => 's',
              't' => 't',
              'u' => 'u',
              'v' => 'v',
              'w' => 'w',
              'x' => 'x',
              'y' => 'y',
              'z' => 'z',
          );
