<?php

/**
 * options_identities.php
 *
 * Display Identities Options
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage prefs
 * @since 1.1.3
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'functions/global.php');
include_once(SM_PATH . 'functions/display_messages.php');
include_once(SM_PATH . 'functions/html.php');
include_once(SM_PATH . 'functions/identity.php');

if (!sqgetGlobalVar('identities', $identities, SQ_SESSION)) {
    $identities = get_identities();
}
sqgetGlobalVar('newidentities', $newidentities, SQ_POST);
sqgetGlobalVar('smaction', $smaction, SQ_POST);
sqgetGlobalVar('return', $return, SQ_POST);

// First lets see if there are any actions to perform //
if (!empty($smaction) && is_array($smaction)) {

    $doaction = '';
    $identid = 0;

    foreach($smaction as $action=>$row) {
        // we only need to extract the action and the identity we are
        // altering

        foreach($row as $iKey=>$data) {
            $identid = $iKey;
        }

        $doaction = $action;
    }

    $identities = sqfixidentities( $newidentities , $identid , $action );
    save_identities($identities);
}

if (!empty($return)) {
    header('Location: ' . get_location() . '/options_personal.php');
    exit;
}

displayPageHeader($color, 'None');

/* since 1.1.3 */
do_hook('options_identities_top');

$td_str = '';
$td_str .= '<form name="f" action="options_identities.php" method="post"><br />' . "\n";
$td_str .= '<table border="0" cellspacing="0" cellpadding="0" width="100%">' . "\n";
$cnt = count($identities);
foreach( $identities as $iKey=>$ident ) {

    if ($iKey == 0) {
        $hdr_str = _("Default Identity");
    } else {
        $hdr_str = sprintf( _("Alternate Identity %d"), $iKey);
    }

    $td_str .= ShowIdentityInfo( $hdr_str, $ident, $iKey );

}

$td_str .= ShowIdentityInfo( _("Add a New Identity"), array('full_name'=>'','email_address'=>'','reply_to'=>'','signature'=>''), $cnt);
$td_str .= '</table>' . "\n";
$td_str .= '</form>';

echo '<br /> ' . "\n" .
    html_tag('table', "\n" .
        html_tag('tr', "\n" .
            html_tag('td' , "\n" .
            '<b>' . _("Options") . ' - ' . _("Advanced Identities") . '</b><br />' .
            html_tag('table', "\n" .
                html_tag('tr', "\n" .
                    html_tag('td', "\n" .
                        html_tag('table' , "\n" .
                            html_tag('tr' , "\n" .
                                html_tag('td', "\n" .  $td_str ,'','', 'style="text-align:center;"')
                            ),
                        '', '', 'width="80%" cellpadding="2" cellspacing="0" border="0"' ) ,
                    'center', $color[4])
                ),
            '', '', 'width="100%" border="0" cellpadding="1" cellspacing="1"' )) ,
        'center', $color[0]),
    'center', '', 'width="95%" border="0" cellpadding="2" cellspacing="0"' ) . '</body></html>';

/**
 * Returns html formated identity form fields
 *
 * Contains options_identities_buttons and option_identities_table hooks.
 * Before 1.4.5/1.5.1 hooks were placed in ShowTableInfo() function.
 * In 1.1.3-1.4.1 they were called in do_hook function with two or
 * three arguments. Since 1.4.1 hooks are called in concat_hook_function.
 * Arguments are moved to array.
 *
 * options_identities_buttons hook uses array with two keys. First array key is
 * boolean variable used to indicate empty identity field. Second array key
 * is integer variable used to indicate identity number
 *
 * options_identities_table hook uses array with three keys. First array key is
 * a string containing background color style CSS (1.4.1-1.4.4/1.5.0 uses only
 * html color code). Second array key is boolean variable used to indicate empty
 * identity field. Third array key is integer variable used to indicate identity
 * number
 * @param string $title Name displayed in header row
 * @param array $identity Identity information
 * @param integer $id identity ID
 * @return string html formatted table rows with form fields for identity management
 * @since 1.5.1 and 1.4.5 (was called ShowTableInfo() in 1.1.3-1.4.4 and 1.5.0)
 */
function ShowIdentityInfo($title, $identity, $id ) {
    global $color;

    if (empty($identity['full_name']) && empty($identity['email_address']) && empty($identity['reply_to']) && empty($identity['signature'])) {
        $bg = '';
        $empty = true;
    } else {
        $bg = ' style="background-color:' . $color[0] . ';"';
        $empty = false;
    }

    $name = 'newidentities[%d][%s]';


    $return_str = '';

    $return_str .= '<tr>' . "\n";
    $return_str .= '  <th style="text-align:center;background-color:' . $color[9] . ';" colspan="2">' . $title . '</th> '. "\n";
    $return_str .= '</tr>' . "\n";
    $return_str .= sti_input( _("Full Name") , sprintf($name, $id, 'full_name'), $identity['full_name'], $bg);
    $return_str .= sti_input( _("E-Mail Address") , sprintf($name, $id, 'email_address'), $identity['email_address'], $bg);
    $return_str .= sti_input( _("Reply To"), sprintf($name, $id, 'reply_to'), $identity['reply_to'], $bg);
    $return_str .= sti_textarea( _("Signature"), sprintf($name, $id, 'signature'), $identity['signature'], $bg);
    $return_str .= concat_hook_function('options_identities_table', array($bg, $empty, $id));
    $return_str .= '<tr' . $bg . '> ' . "\n";
    $return_str .= '  <td> &nbsp; </td>' . "\n";
    $return_str .= '  <td>' . "\n";
    $return_str .= '    <input type="submit" name="smaction[save][' . $id . ']" value="' . _("Save / Update") . '" />' . "\n";

    if (!$empty && $id > 0) {
        $return_str .= '    <input type="submit" name="smaction[makedefault][' . $id . ']" value="' . _("Make Default") . '" />' . "\n";
        $return_str .= '    <input type="submit" name="smaction[delete]['.$id.']" value="' . _("Delete") . '" />' . "\n";

        if ($id > 1) {
            $return_str .= '    <input type="submit" name="smaction[move]['.$id.']" value="' . _("Move Up") . '" />' . "\n";
        }

    }

    $return_str .= concat_hook_function('options_identities_buttons', array($empty, $id));
    $return_str .= '  </td>' . "\n";
    $return_str .= '</tr>' . "\n";
    $return_str .= '<tr>' . "\n";
    $return_str .= '  <td colspan="2"> &nbsp; </td>' . "\n";
    $return_str .= '</tr>';

    return $return_str;

}

/**
 * Creates html formated table row with input field
 * @param string $title Name displayed next to input field
 * @param string $name Name of input field
 * @param string $data Default value of input field (data is sanitized with htmlspecialchars)
 * @param string $bgcolor html attributes added to row element (tr)
 * @return string html formated table row with text input field
 * @since 1.2.0 (arguments differ since 1.4.5/1.5.1)
 * @todo check right-to-left language issues
 * @access private
 */
function sti_input( $title, $name, $data, $bgcolor ) {
    $str = '';
    $str .= '<tr' . $bgcolor . ">\n";
    $str .= '  <td style="white-space: nowrap;text-align:right;">' . $title . ' </td>' . "\n";
    $str .= '  <td> <input type="text" name="' . $name . '" size="50" value="'. htmlspecialchars($data) . '"> </td>' . "\n";
    $str .= '</tr>';

    return $str;

}

/**
 * Creates html formated table row with textarea field
 * @param string $title Name displayed next to textarea field
 * @param string $name Name of textarea field
 * @param string $data Default value of textarea field  (data is sanitized with htmlspecialchars)
 * @param string $bgcolor html attributes added to row element (tr)
 * @return string html formated table row with textarea
 * @since 1.2.5 (arguments differ since 1.4.5/1.5.1)
 * @todo check right-to-left language issues
 * @access private
 */
function sti_textarea( $title, $name, $data, $bgcolor ) {
    $str = '';
    $str .= '<tr' . $bgcolor . ">\n";
    $str .= '  <td style="white-space: nowrap;text-align:right;">' . $title . ' </td>' . "\n";
    $str .= '  <td> <textarea name="' . $name . '" cols="50" rows="5">'. htmlspecialchars($data) . '</textarea> </td>' . "\n";
    $str .= '</tr>';

    return $str;

}

?>