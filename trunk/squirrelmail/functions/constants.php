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

    /**************************************************************/
    /* Set values for constants used by Squirrelmail preferences. */
    /**************************************************************/

    /* Define constants for javascript settings. */
    define('SMPREF_JS_ON', 1);
    define('SMPREF_JS_OFF', 2);
    define('SMPREF_JS_AUTODETECT', 3);

    define('SMPREF_LOC_TOP', 'top');
    define('SMPREF_LOC_BETWEEN', 'between');
    define('SMPREF_LOC_BOTTOM', 'bottom');
    define('SMPREF_LOC_LEFT', '');
    define('SMPREF_LOC_RIGHT', 'right');

    define('SMPREF_NONE', 'none');

    do_hook("loading_constants");
?>
