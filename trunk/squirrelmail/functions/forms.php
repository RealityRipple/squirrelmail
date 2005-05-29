<?php
/**
 * forms.php - html form functions
 *
 * Copyright (c) 2004-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Functions to build HTML forms in a safe and consistent manner.
 * All name, value attributes are sanitized with htmlspecialchars().
 *
 * Currently functions don't provide simple wrappers for file and 
 * image input fields, support only submit and reset buttons and use 
 * input fields for buttons.
 *
 * Since 1.5.1 all form functions should support id tags. Original 
 * idea by dugan <at> passwall.com. Tags can be used for Section 508 
 * or WAI compliance.
 *
 * @link http://www.section508.gov/ Section 508
 * @link http://www.w3.org/WAI/ Web Accessibility Initiative (WAI)
 * @link http://www.w3.org/TR/html4/ W3.org HTML 4.01 form specs
 * @version $Id$
 * @package squirrelmail
 * @subpackage forms
 * @since 1.4.3 and 1.5.1
 */

/**
 * Helper function to create form fields, not to be called directly,
 * only by other functions below.
 * @param string $type type of input field. Possible values (html 4.01 
 *  specs.): text, password, checkbox, radio, submit, reset, file, 
 *  hidden, image, button.
 * @param string $name form field name
 * @param string $value initial field value
 * @param string $attributes extra attributes
 * @param string $id (since 1.5.1) assigns unique identifier to an element
 * @return string html formated input field
 * @deprecated use other functions that provide simple wrappers to this function
 */
function addInputField($type, $name = null, $value = null, $attributes = '', $id = null) {
    return '<input type="'.$type.'"'.
        ($name  !== null ? ' name="'.htmlspecialchars($name).'"'   : '').
        ($id  !== null ? ' id="'.htmlspecialchars($id).'"'
            : ($name  !== null ? ' id="'.htmlspecialchars($name).'"'   : '')).
        ($value !== null ? ' value="'.htmlspecialchars($value).'"' : '').
        $attributes . " />\n";
}

/**
 * Password input field
 * @param string $name field name
 * @param string $value initial password value
 * @param string $id (since 1.5.1) assigns unique identifier to an element
 * @return string html formated password field
 */
function addPwField($name , $value = null, $id = null) {
    return addInputField('password', $name , $value, '', $id);
}

/**
 * Form checkbox
 * @param string $name field name
 * @param boolean $checked controls if field is checked
 * @param string $value
 * @param string $xtra (since 1.5.1) extra field attributes
 * @param string $id (since 1.5.1) assigns unique identifier to an element
 * @return string html formated checkbox field
 */
function addCheckBox($name, $checked = false, $value = null, $xtra = '', $id = null) {
    return addInputField('checkbox', $name, $value,
        ($checked ? ' checked="checked"' : '') . ' ' . $xtra, $id);
}

/**
 * Form radio box
 * @param string $name field name
 * @param boolean $checked controls if field is selected
 * @param string $value
 * @param string $id (since 1.5.1) assigns unique identifier to an element. 
 *  Defaults to combined $name and $value string
 * @return string html formated radio box
 */
function addRadioBox($name, $checked = false, $value = null, $id = '') {
    if (empty($id)) {
        $id = $name . $value;
    }
    return addInputField('radio', $name, $value,
        ($checked ? ' checked="checked"' : ''), $id);
}

/**
 * A hidden form field.
 * @param string $name field name
 * @param string $value field value
 * @param string $id (since 1.5.1) assigns unique identifier to an element
 * @return html formated hidden form field
 */
function addHidden($name, $value, $id = null) {
    return addInputField('hidden', $name, $value, '', $id);
}

/**
 * An input textbox.
 * @param string $name field name
 * @param string $value initial field value
 * @param integer $size field size (number of characters)
 * @param integer $maxlength maximum number of characters the user may enter
 * @param string $id (since 1.5.1) assigns unique identifier to an element
 * @return string html formated text input field
 */
function addInput($name, $value = '', $size = 0, $maxlength = 0, $id = null) {

    $attr = '';
    if ($size) {
        $attr.= ' size="'.(int)$size.'"';
    }
    if ($maxlength) {
        $attr.= ' maxlength="'.(int)$maxlength .'"';
    }

    return addInputField('text', $name, $value, $attr, $id);
}

/**
 * Function to create a selectlist from an array.
 * @param string $name field name
 * @param array $values field values array ( key => value )  ->     <option value="key">value</option>
 * @param mixed $default the key that will be selected
 * @param boolean $usekeys use the keys of the array as option value or not
 * @param string $id (since 1.5.1) assigns unique identifier to an element
 * @return string html formated selection box
 */
function addSelect($name, $values, $default = null, $usekeys = false, $id = null) {
    // only one element
    if(count($values) == 1) {
        $k = key($values); $v = array_pop($values);
        return addHidden($name, ($usekeys ? $k:$v), $id).
            htmlspecialchars($v) . "\n";
    }

    if (! is_null($id)) {
        $id = ' id="'.htmlspecialchars($id).'"';
        $label_open = '<label for="'.htmlspecialchars($id).'">';
        $label_close = '</label>';
    } else {
        $id = '';
        $label_open = '';
        $label_close = '';
    }

    $ret = '<select name="'.htmlspecialchars($name) . '"' . $id . ">\n";
    foreach ($values as $k => $v) {
        if(!$usekeys) $k = $v;
        $ret .= '<option value="' .
            htmlspecialchars( $k ) . '"' .
            (($default == $k) ? ' selected="selected"' : '') .
            '>' . $label_open . htmlspecialchars($v) . $label_close  ."</option>\n";
    }
    $ret .= "</select>\n";

    return $ret;
}

/**
 * Form submission button
 * Note the switched value/name parameters!
 * @param string $value button name
 * @param string $name submitted key name
 * @param string $id (since 1.5.1) assigns unique identifier to an element
 * @return string html formated submit input field
 */
function addSubmit($value, $name = null, $id = null) {
    return addInputField('submit', $name, $value, '', $id);
}
/**
 * Form reset button
 * @param string $value button name
 * @param string $id (since 1.5.1) assigns unique identifier to an element
 * @return string html formated reset input field
 */
function addReset($value, $id = null) {
    return addInputField('reset', null, $value, '', $id);
}

/**
 * Textarea form element.
 * @param string $name field name
 * @param string $text initial field value
 * @param integer $cols field width (number of chars)
 * @param integer $rows field height (number of character rows)
 * @param string $attr extra attributes
 * @param string $id (since 1.5.1) assigns unique identifier to an element
 * @return string html formated text area field
 */
function addTextArea($name, $text = '', $cols = 40, $rows = 10, $attr = '', $id = '') {
    if (!empty($id)) {
        $id = ' id="'. htmlspecialchars($id) . '"';
        $label_open = '<label for="'.htmlspecialchars($id).'">';
        $label_close = '</label>';
    } else {
        $label_open = '';
        $label_close = '';
    }
    return '<textarea name="'.htmlspecialchars($name).'" '.
        'rows="'.(int)$rows .'" cols="'.(int)$cols.'" '.
        $attr . $id . '>'. $label_open . htmlspecialchars($text) . $label_close ."</textarea>\n";
}

/**
 * Make a <form> start-tag.
 * @param string $action form handler URL
 * @param string $method http method used to submit form data. 'get' or 'post'
 * @param string $name form name used for identification (used for backward 
 *  compatibility). Use of id is recommended.
 * @param string $enctype content type that is used to submit data. html 4.01 
 *  defaults to 'application/x-www-form-urlencoded'. Form with file field needs 
 *  'multipart/form-data' encoding type.
 * @param string $charset charset that is used for submitted data
 * @param string $id (since 1.5.1) assigns unique identifier to an element
 * @return string html formated form start string
 */
function addForm($action, $method = 'post', $name = '', $enctype = '', $charset = '', $id = '') {
    if($name) {
        $name = ' name="'.$name.'"';
    }
    if($enctype) {
        $enctype = ' enctype="'.$enctype.'"';
    }
    if($charset) {
        $charset = ' accept-charset="'.htmlspecialchars($charset).'"';
    }
    if (!empty($id)) {
        $id = ' id="'.htmlspecialchars($id).'"';
    }

    return '<form action="'. $action .'" method="'. $method .'"'.
        $enctype . $name . $charset . $id . ">\n";
}

?>