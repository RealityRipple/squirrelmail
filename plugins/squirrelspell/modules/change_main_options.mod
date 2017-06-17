<?php
/**
 * change_main_options.mod
 * -----------------------
 * Squirrelspell module
 *
 * Copyright (c) 1999-2017 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This module changes the international dictionaries selection
 * for the user. Called after LANG_SETUP module.
 *
 * @author Paul Lesniewski <paul@squirrelmail.org>
 * @version $Id: lang_change.mod 14642 2017-01-27 20:31:33Z pdontthink $
 * @package plugins
 * @subpackage squirrelspell
 */

if (!sqgetGlobalVar('smtoken',$submitted_token, SQ_POST)) {
    $submitted_token = '';
}
sm_validate_security_token($submitted_token, -1, TRUE);

$main_options_changed_message = '<p><strong>';

if (sqgetGlobalVar('sqspell_show_button', $sqspell_show_button, SQ_POST)
 && !empty($sqspell_show_button))
{
   $sqspell_show_button = 1;
   $main_options_changed_message .= sprintf(_("Settings changed: Set to show \"%s\" button"), _("Check Spelling"));
}
else
{
   $sqspell_show_button = 0;
   $main_options_changed_message .= sprintf(_("Settings changed: Set to hide \"%s\" button"), _("Check Spelling"));
}

$main_options_changed_message .= '</strong></p>';

setPref($data_dir, $username, 'sqspell_show_button', $sqspell_show_button);

// so far the only thing this file does is change a checkbox,
// so for now we can skip the confirmation page and just reload
// the changed main options page (with a simple confirmation message)
//
require(SM_PATH . 'plugins/squirrelspell/modules/options_main.mod');

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 * vim: syntax=php
 */

