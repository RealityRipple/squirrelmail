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

    /******************************************************/
    /* Set values for constants used in the options code. */
    /******************************************************/

    /* Define constants for the various option types. */
    define('SMOPT_TYPE_STRING', 0);
    define('SMOPT_TYPE_STRLIST', 1);
    define('SMOPT_TYPE_TEXTAREA', 2);
    define('SMOPT_TYPE_INTEGER', 3);
    define('SMOPT_TYPE_FLOAT', 4);
    define('SMOPT_TYPE_BOOLEAN', 5);

    /* Define constants for the options refresh levels. */
    define('SMOPT_REFRESH_NONE', 0);
    define('SMOPT_REFRESH_FOLDERLIST', 1);
    define('SMOPT_REFRESH_ALL', 2);

    /**************************************************************/
    /* Set values for constants used by Squirrelmail preferences. */
    /**************************************************************/

    /* Define constants for javascript settings. */
    define('SMPREF_JS_ON', 1);
    define('SMPREF_JS_OFF', 2);
    define('SMPREF_JS_AUTODETECT', 3);

    do_hook("loading_constants");
?>
