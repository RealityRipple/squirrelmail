<?php
/**
 * forms.php
 *
 * Copyright (c) 2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Functions to build HTML forms in a safe and consistent manner.
 * All name, value attributes are htmlentitied.
 *
 * $Id$
 * @package squirrelmail
 */

/**
 * Helper function to create form fields, not to be called directly,
 * only by other functions below.
 */
function addInputField($type, $name = null, $value = null, $attributes = '') {
    return '<input type="'.$type.'"'.
        ($name  !== null ? ' name="'.htmlentities($name).'"'   : '').
        ($value !== null ? ' value="'.htmlentities($value).'"' : '').
        $attributes . ">\n";
}

/**
 * Password input field
 */
function addPwField($name , $value = null) {
    return addInputField('password', $name , $value);
}


/**
 * Form checkbox
 */
function addCheckBox($name, $checked = false, $value='') {
    return addInputField('checkbox', $name, $value,
        ($checked ? ' checked' : ''));
}

/**
 * Form radio box
 */
function addRadioBox($name, $checked = false, $value='') {
    return addInputField('radio', $name, $value, 
        ($checked ? ' checked' : ''));
}

/**
 * A hidden form field.
 */
function addHidden($name, $value) {
    return addInputField('hidden', $name, $value);
}

/**
 * An input textbox.
 */
function addInput($name, $value = '', $size = 0, $maxlength = 0) {

    $attr = '';
    if ($size) {
        $attr.= ' size="'.(int)$size.'"';
    }
    if ($maxlength) {
        $attr.= ' maxlength="'.(int)$maxlength .'"';
    }

    return addInputField('text', $name, $value, $attr);
}


/**
 * Function to create a selectlist from an array.
 * Usage:
 * name: html name attribute
 * values: array ( key => value )  ->     <option value="key">value
 * default: the key that will be selected
 * usekeys: use the keys of the array as option value or not
 */
function addSelect($name, $values, $default = null, $usekeys = false)
{
    // only one element
    if(count($values) == 1) {
        $k = key($values); $v = array_pop($values);
        return addHidden($name, ($usekeys ? $k:$v)).
            htmlentities($v) . "\n";
    }

    $ret = '<select name="'.htmlentities($name) . "\">\n";
    foreach ($values as $k => $v) {
        if(!$usekeys) $k = $v;
        $ret .= '<option value="' .
            htmlentities( $k ) . '"' .
            (($default == $k) ? ' selected':'') .
            '>' . htmlentities($v) ."</option>\n";
    }
    $ret .= "</select>\n";

    return $ret;
}

/**
 * Form submission button
 * Note the switched value/name parameters!
 */
function addSubmit($value, $name = null) {
    return addInputField('submit', $name, $value);
}
/**
 * Form reset button, $value = caption
 */
function addReset($value) {
    return addInputField('reset', null, $value);
}

/**
 * Textarea form element.
 */
function addTextArea($name, $text = '', $cols = 40, $rows = 10, $attr = '') {
    return '<textarea name="'.htmlentities($name).'" '.
        'rows="'.(int)$rows .'" cols="'.(int)$cols.'"'.
        $attr . '">'.htmlentities($text) ."</textarea>\n";
}

/**
 * Make a <form> start-tag.
 */
function addForm($action, $method = 'POST', $name = '', $enctype = '', $charset = '')
{
    if($name) {
        $name = ' name="'.$name.'"';
    }
    if($enctype) {
        $enctype = ' enctype="'.$enctype.'"';
    }
    if($charset) {
        $charset = ' accept-charset="'.htmlspecialchars($charset).'"';
    }

    return '<form action="'. $action .'" method="'. $method .'"'.
        $enctype . $name . $charset . "\">\n";
}


