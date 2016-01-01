<?php

/**
 * SquirrelMail internal ngettext functions
 *
 * Uses php-gettext classes
 *
 * @link http://www.php.net/gettext Original php gettext manual
 * @link http://savannah.nongnu.org/projects/php-gettext php-gettext classes
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage i18n
 * @since 1.5.1
 */

/**
 * internal ngettext wrapper.
 *
 * provides ngettext support
 * @since 1.5.1
 * @link http://www.php.net/function.ngettext
 * @param string $single English string, singular form
 * @param string $plural English string, plural form
 * @param integer $number number that shows used quantity
 * @return string translated string
 */
function ngettext($single, $plural, $number) {
    global $l10n, $gettext_domain;
    if (! isset($l10n[$gettext_domain]) ||
        ! is_object($l10n[$gettext_domain]) ||
        $l10n[$gettext_domain]->error==1)
        return ($number==1 ? $single : $plural);
    return $l10n[$gettext_domain]->ngettext($single, $plural, $number);
}

/**
 * safety check. 
 * freaky setup where ngettext is not available and dngettext is available.
 */
if (! function_exists('dngettext')) {
    /**
     * internal dngettext wrapper.
     *
     * provides dngettext support
     * @since 1.5.1
     * @link http://www.php.net/function.dngettext
     * @param string $domain Gettext domain
     * @param string $single English string, singular form
     * @param string $plural English string, plural form
     * @param integer $number number that shows used quantity
     * @return string translated string
     */
    function dngettext($domain, $single, $plural, $number) {
        global $l10n;
        // Make sure that $number is integer
        $number = (int) $number;
        
        // Make sure that domain is initialized
        if (! isset($l10n[$domain]) || 
            ! is_object($l10n[$domain]) || 
            $l10n[$domain]->error==1)
            return ($number==1 ? $single : $plural);

        // use ngettext class function
        return $l10n[$domain]->ngettext($single, $plural, $number);
    }
}
