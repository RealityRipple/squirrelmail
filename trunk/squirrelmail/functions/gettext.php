<?php

/**
 * SquirrelMail internal gettext functions
 *
 * Since 1.5.1 uses php-gettext classes.
 * Original implementation was done by Tyler Akins (fidian)
 *
 * @link http://www.php.net/gettext Original php gettext manual
 * @link http://savannah.nongnu.org/projects/php-gettext php-gettext classes
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @since 1.1.2
 * @package squirrelmail
 * @subpackage i18n
 */


/** Load classes and other functions */
include_once(SM_PATH . 'class/l10n.class.php');
include_once(SM_PATH . 'functions/ngettext.php');

/**
 * Alternative php gettext function (short form)
 *
 * @link http://www.php.net/function.gettext
 *
 * @param string $str English string
 * @return string translated string
 * @since 1.1.2
 */
function _($str) {
    global $l10n, $gettext_domain;
    if (! isset($l10n[$gettext_domain]) ||
        ! is_object($l10n[$gettext_domain]) ||
        $l10n[$gettext_domain]->error==1)
        return $str;
    return $l10n[$gettext_domain]->translate($str);
}

/**
 * Alternative php bindtextdomain function
 *
 * Sets path to directory containing domain translations
 *
 * @link http://www.php.net/function.bindtextdomain
 * @param string $domain gettext domain name
 * @param string $dir directory that contains all translations
 * @return string path to translation directory
 * @since 1.1.2
 */
function bindtextdomain($domain, $dir) {
    global $l10n, $sm_notAlias;
    if (substr($dir, -1) != '/') $dir .= '/';
    $mofile=$dir . $sm_notAlias . '/LC_MESSAGES/' . $domain . '.mo';

    $input = new FileReader($mofile);
    $l10n[$domain] = new gettext_reader($input);

    return $dir;
}

/**
 * Alternative php textdomain function
 *
 * Sets default domain name. Before 1.5.1 command required
 * bindtextdomain() call for each gettext domain change.
 *
 * @link http://www.php.net/function.textdomain
 * @param string $name gettext domain name
 * @return string gettext domain name
 * @since 1.1.2
 */
function textdomain($name = false) {
    global $gettext_domain;
    if ($name) $gettext_domain=$name;
    return $gettext_domain;
}

/**
 * Safety check.
 * Setup where three standard gettext functions don't exist and dgettext() exists.
 */
if (! function_exists('dgettext')) {
    /**
     * Alternative php dgettext function
     *
     * @link http://www.php.net/function.dgettext
     * @param string $domain Gettext domain
     * @param string $str English string
     * @return string translated string
     * @since 1.5.1
     */
    function dgettext($domain, $str) {
        global $l10n;
        if (! isset($l10n[$domain]) ||
            ! is_object($l10n[$domain]) ||
            $l10n[$domain]->error==1)
            return $str;
        return $l10n[$domain]->translate($str);
    }
}
