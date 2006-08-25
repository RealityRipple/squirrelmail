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
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/identity.php');

/* make sure that page is not available when $edit_identity is false */
if (!$edit_identity) {
    error_box(_("Editing identities is disabled."));
    $oTemplate->display('footer.tpl');
    die();
}

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

$i = array();
foreach ($identities as $key=>$ident) {
    $a = array();
    $a['Title'] = $key==0 ? _("Default Identity") : sprintf(_("Alternate Identity %d"), $key);
    $a['New'] = false;
    $a['Default'] = $key==0;
    $a['FullName'] = htmlspecialchars($ident['full_name']);
    $a['Email'] = htmlspecialchars($ident['email_address']);
    $a['ReplyTo'] = htmlspecialchars($ident['reply_to']);
    $a['Signature'] = htmlspecialchars($ident['signature']);
    $i[$key] = $a;
}

$a = array();
$a['Title'] = _("Add New Identity");
$a['New'] = true;
$a['Default'] = false;
$a['FullName'] = '';
$a['Email'] = '';
$a['ReplyTo'] = '';
$a['Signature'] = '';
$i[count($i)] = $a;

echo '<form name="f" action="options_identities.php" method="post">' . "\n";

$oTemplate->assign('identities', $i);
$oTemplate->display('options_advidentity_list.tpl');

echo "</form>\n";

$oTemplate->display('footer.tpl');

/**
 * The functions below should not be needed with the additions of templates,
 * however they will remain in case plugins use them.
 */

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