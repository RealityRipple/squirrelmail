<?php
/**
 * SquirrelMail internal ngettext functions
 *
 * Uses php-gettext classes
 *
 * Copyright (c) 2004-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @copyright (c) 2004-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public Licens
 * @link http://www.php.net/gettext Original php gettext manual
 * @link http://savannah.nongnu.org/projects/php-gettext php-gettext classes
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
    if ($l10n[$gettext_domain]->error==1) return $single;
    return $l10n[$gettext_domain]->ngettext($single, $plural, $number);
}
?>