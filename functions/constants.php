<?php

/**
 * constants.php
 *
 * Copyright (c) 1999-2001 The SquirrelMail Development Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Loads constants used by the rest of the Squirrelmail source.
 * This file is include by src/login.php, src/redirect.php and
 * src/load_prefs.php.
 *
 * $Id$
 */

/*****************************************************************/
/*** THIS FILE NEEDS TO HAVE ITS FORMATTING FIXED!!!           ***/
/*** PLEASE DO SO AND REMOVE THIS COMMENT SECTION.             ***/
/***    + Base level indent should begin at left margin, as    ***/
/***      the require_once below.                              ***/
/***    + All identation should consist of four space blocks   ***/
/***    + Tab characters are evil.                             ***/
/***    + all comments should use "slash-star ... star-slash"  ***/
/***      style -- no pound characters, no slash-slash style   ***/
/***    + FLOW CONTROL STATEMENTS (if, while, etc) SHOULD      ***/
/***      ALWAYS USE { AND } CHARACTERS!!!                     ***/
/***    + Please use ' instead of ", when possible. Note "     ***/
/***      should always be used in _( ) function calls.        ***/
/*** Thank you for your help making the SM code more readable. ***/
/*****************************************************************/

require_once( '../functions/plugin.php' );      // Required for the hook

/**************************************************************/
/* Set values for constants used by Squirrelmail preferences. */
/**************************************************************/

    /* Define basic, general purpose preference constants. */
    define('SMPREF_NO', 0);
    define('SMPREF_OFF', 0);
    define('SMPREF_YES', 1);
    define('SMPREF_ON', 1);
    define('SMPREF_NONE', 'none');

    /* Define constants for location based preferences. */
    define('SMPREF_LOC_TOP', 'top');
    define('SMPREF_LOC_BETWEEN', 'between');
    define('SMPREF_LOC_BOTTOM', 'bottom');
    define('SMPREF_LOC_LEFT', '');
    define('SMPREF_LOC_RIGHT', 'right');

    /* Define preferences for folder settings. */
    define('SMPREF_UNSEEN_NONE', 1);
    define('SMPREF_UNSEEN_INBOX', 2);
    define('SMPREF_UNSEEN_ALL', 3);
    define('SMPREF_UNSEEN_ONLY', 1);
    define('SMPREF_UNSEEN_TOTAL', 2);

    /* Define constants for time/date display preferences. */
    define('SMPREF_TIME_24HR', 1);
    define('SMPREF_TIME_12HR', 2);

    /* Define constants for javascript preferences. */
    define('SMPREF_JS_OFF', 0);
    define('SMPREF_JS_ON', 1);
    define('SMPREF_JS_AUTODETECT', 2);

    do_hook('loading_constants');
?>
