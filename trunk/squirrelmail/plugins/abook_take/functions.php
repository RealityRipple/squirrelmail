<?php

/**
 * functions.php
 *
 * Functions for the Address Take plugin
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage abook_take
 */

/** SquirrelMail required files. */
require_once(SM_PATH . 'functions/url_parser.php');
require_once(SM_PATH . 'functions/forms.php');

function valid_email ($email, $verify)
{
    global $Email_RegExp_Match;

    if (! eregi('^' . $Email_RegExp_Match . '$', $email))
        return false;

    if (! $verify)
        return true;

    return checkdnsrr(substr(strstr($email, '@'), 1), 'ANY') ;
}

function abook_take_read_string($str)
{
    global $abook_found_email, $Email_RegExp_Match;

    while (eregi('(' . $Email_RegExp_Match . ')', $str, $hits))
    {
        $str = substr(strstr($str, $hits[0]), strlen($hits[0]));
        if (! isset($abook_found_email[$hits[0]]))
        {
            echo addHidden('email[]', $hits[0]);
            $abook_found_email[$hits[0]] = 1;
        }
    }

    return;
}

function abook_take_read_array($array)
{
    foreach ($array as $item)
        abook_take_read_string($item->getAddress());
}

function abook_take_read()
{
    global $message;

    echo '<br />' . addForm(SM_PATH . 'plugins/abook_take/take.php') .
         '<center>' . "\n";

    if (isset($message->rfc822_header->reply_to))
        abook_take_read_array($message->rfc822_header->reply_to);
    if (isset($message->rfc822_header->from))
        abook_take_read_array($message->rfc822_header->from);
    if (isset($message->rfc822_header->cc))
        abook_take_read_array($message->rfc822_header->cc);
    if (isset($message->rfc822_header->to))
        abook_take_read_array($message->rfc822_header->to);

    echo addSubmit(_("Take Address")) .
         '</center></form>';
}

function abook_take_pref()
{
    global $username, $data_dir, $abook_take_verify;

    $abook_take_verify = getPref($data_dir, $username, 'abook_take_verify', false);
}

function abook_take_options()
{
    global $abook_take_verify;

    echo '<tr>' . html_tag('td',_("Address Book Take:"),'right','','style="white-space: nowrap;"') . "\n" .  '<td>' .
         addCheckbox('abook_take_abook_take_verify', $abook_take_verify) .
         _("Try to verify addresses") . "</td></tr>\n";
}

function abook_take_save()
{
    global $username, $data_dir;

    if (sqgetGlobalVar('abook_take_abook_take_verify', $abook_take_abook_take_verify, SQ_POST))
        setPref($data_dir, $username, 'abook_take_verify', '1');
    else
        setPref($data_dir, $username, 'abook_take_verify', '');
}

?>