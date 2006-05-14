<?php

/**
 * forms.php - html form functions
 *
 * Functions to build HTML forms in a safe and consistent manner.
 * All attribute values are sanitized with htmlspecialchars().
 *
 * Currently functions don't provide simple wrappers for file and 
 * image input fields, support only submit and reset buttons and use 
 * html input tags for buttons.
 *
 * Since 1.5.1:
 *
 *  * all form functions should support id tags. Original 
 *  idea by dugan <at> passwall.com. Tags can be used for Section 508 
 *  or WAI compliance.
 *
 *  * input tag functions accept extra html attributes that can be submitted 
 *  in $aAttribs array.
 *
 *  * default css class attributes are added.
 *
 * @link http://www.section508.gov/ Section 508
 * @link http://www.w3.org/WAI/ Web Accessibility Initiative (WAI)
 * @link http://www.w3.org/TR/html4/ W3.org HTML 4.01 form specs
 * @copyright &copy; 2004-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage forms
 * @since 1.4.3 and 1.5.1
 */

/**
 * Helper function to create form fields, not to be called directly,
 * only by other functions below.
 * 
 * Function used different syntax before 1.5.1
 * @param string $sType type of input field. Possible values (html 4.01 
 *  specs.): text, password, checkbox, radio, submit, reset, file, 
 *  hidden, image, button.
 * @param array $aAttribs (since 1.5.1) extra attributes. Array key is 
 *  attribute name, array value is attribute value. Array keys must use  
 *  lowercase.
 * @return string html formated input field
 * @deprecated use other functions that provide simple wrappers to this function
 */
function addInputField($sType, $aAttribs=array()) {
    $sAttribs = '';
    // define unique identifier
    if (! isset($aAttribs['id']) && isset($aAttribs['name']) && ! is_null($aAttribs['name'])) {
        /**
         * if 'id' is not set, set it to 'name' and replace brackets 
         * with underscores. 'name' might contain field name with squire
         * brackets (array). Brackets are not allowed in id (validator.w3.org
         * fails to validate document). According to html 4.01 manual cdata 
         * type description, 'name' attribute uses same type, but validator.w3.org 
         * does not barf on brackets in 'name' attributes.
         */
        $aAttribs['id'] = strtr($aAttribs['name'],'[]','__');
    }
    // create attribute string (do we have to sanitize keys?)
    foreach ($aAttribs as $key => $value) {
        $sAttribs.= ' ' . $key . (! is_null($value) ? '="'.htmlspecialchars($value).'"':'');
    }
    return '<input type="'.$sType.'"'.$sAttribs." />\n";
}

/**
 * Password input field
 * @param string $sName field name
 * @param string $sValue initial password value
 * @param array $aAttribs (since 1.5.1) extra attributes
 * @return string html formated password field
 */
function addPwField($sName, $sValue = null, $aAttribs=array()) {
    $aAttribs['name']  = $sName;
    $aAttribs['value'] = (! is_null($sValue) ? $sValue : '');
    // add default css
    if (! isset($aAttribs['class'])) $aAttribs['class'] = 'sqmpwfield';
    return addInputField('password',$aAttribs);
}

/**
 * Form checkbox
 * @param string $sName field name
 * @param boolean $bChecked controls if field is checked
 * @param string $sValue
 * @param array $aAttribs (since 1.5.1) extra attributes
 * @return string html formated checkbox field
 */
function addCheckBox($sName, $bChecked = false, $sValue = null, $aAttribs=array()) {
    $aAttribs['name'] = $sName;
    if ($bChecked) $aAttribs['checked'] = 'checked';
    if (! is_null($sValue)) $aAttribs['value'] = $sValue;
    // add default css
    if (! isset($aAttribs['class'])) $aAttribs['class'] = 'sqmcheckbox';
    return addInputField('checkbox',$aAttribs);
}

/**
 * Form radio box
 * @param string $sName field name
 * @param boolean $bChecked controls if field is selected
 * @param string $sValue
 * @param array $aAttribs (since 1.5.1) extra attributes.
 * @return string html formated radio box
 */
function addRadioBox($sName, $bChecked = false, $sValue = null, $aAttribs=array()) {
    $aAttribs['name'] = $sName;
    if ($bChecked) $aAttribs['checked'] = 'checked';
    if (! is_null($sValue)) $aAttribs['value'] = $sValue;
    if (! isset($aAttribs['id'])) $aAttribs['id'] = $sName . $sValue;
    // add default css
    if (! isset($aAttribs['class'])) $aAttribs['class'] = 'sqmradiobox';
    return addInputField('radio', $aAttribs);
}

/**
 * A hidden form field.
 * @param string $sName field name
 * @param string $sValue field value
 * @param array $aAttribs (since 1.5.1) extra attributes
 * @return html formated hidden form field
 */
function addHidden($sName, $sValue, $aAttribs=array()) {
    $aAttribs['name'] = $sName;
    $aAttribs['value'] = $sValue;
    // add default css
    if (! isset($aAttribs['class'])) $aAttribs['class'] = 'sqmhiddenfield';
    return addInputField('hidden', $aAttribs);
}

/**
 * An input textbox.
 * @param string $sName field name
 * @param string $sValue initial field value
 * @param integer $iSize field size (number of characters)
 * @param integer $iMaxlength maximum number of characters the user may enter
 * @param array $aAttribs (since 1.5.1) extra attributes - should be given
 *                        in the form array('attribute_name' => 'attribute_value', ...)
 * @return string html formated text input field
 */
function addInput($sName, $sValue = '', $iSize = 0, $iMaxlength = 0, $aAttribs=array()) {
    $aAttribs['name'] = $sName;
    $aAttribs['value'] = $sValue;
    if ($iSize) $aAttribs['size'] = (int)$iSize;
    if ($iMaxlength) $aAttribs['maxlength'] = (int)$iMaxlength;
    // add default css
    if (! isset($aAttribs['class'])) $aAttribs['class'] = 'sqmtextfield';
    return addInputField('text', $aAttribs);
}

/**
 * Function to create a selectlist from an array.
 * @param string $sName field name
 * @param array $aValues field values array ( key => value )  ->     <option value="key">value</option>
 * @param mixed $default the key that will be selected
 * @param boolean $bUsekeys use the keys of the array as option value or not
 * @param array $aAttribs (since 1.5.1) extra attributes
 * @return string html formated selection box
 * @todo add attributes argument for option tags and default css
 */
function addSelect($sName, $aValues, $default = null, $bUsekeys = false, $aAttribs = array()) {
    // only one element
    if(count($aValues) == 1) {
        $k = key($aValues); $v = array_pop($aValues);
        return addHidden($sName, ($bUsekeys ? $k:$v), $aAttribs).
            htmlspecialchars($v) . "\n";
    }

    if (isset($aAttribs['id'])) {
        $label_open = '<label for="'.htmlspecialchars($aAttribs['id']).'">';
        $label_close = '</label>';
    } else {
        $label_open = '';
        $label_close = '';
    }

    // create attribute string for select tag
    $sAttribs = '';
    foreach ($aAttribs as $key => $value) {
        $sAttribs.= ' ' . $key . (! is_null($value) ? '="'.htmlspecialchars($value).'"':'');
    }

    $ret = '<select name="'.htmlspecialchars($sName) . '"' . $sAttribs . ">\n";
    foreach ($aValues as $k => $v) {
        if(!$bUsekeys) $k = $v;
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
 * @param string $sValue button name
 * @param string $sName submitted key name
 * @param array $aAttribs (since 1.5.1) extra attributes
 * @return string html formated submit input field
 */
function addSubmit($sValue, $sName = null, $aAttribs=array()) {
    $aAttribs['value'] = $sValue;
    if (! is_null($sName)) $aAttribs['name'] = $sName;
    // add default css
    if (! isset($aAttribs['class'])) $aAttribs['class'] = 'sqmsubmitfield';
    return addInputField('submit', $aAttribs);
}
/**
 * Form reset button
 * @param string $sValue button name
 * @param array $aAttribs (since 1.5.1) extra attributes
 * @return string html formated reset input field
 */
function addReset($sValue, $aAttribs=array()) {
    $aAttribs['value'] = $sValue;
    // add default css
    if (! isset($aAttribs['class'])) $aAttribs['class'] = 'sqmresetfield';
    return addInputField('reset', $aAttribs);
}

/**
 * Textarea form element.
 * @param string $sName field name
 * @param string $sText initial field value
 * @param integer $iCols field width (number of chars)
 * @param integer $iRows field height (number of character rows)
 * @param array $aAttribs (since 1.5.1) extra attributes. function accepts string argument 
 * for backward compatibility.
 * @return string html formated text area field
 */
function addTextArea($sName, $sText = '', $iCols = 40, $iRows = 10, $aAttribs = array()) {
    $label_open = '';
    $label_close = '';
    if (is_array($aAttribs)) {
        // maybe id can default to name?
        if (isset($aAttribs['id'])) {
            $label_open = '<label for="'.htmlspecialchars($aAttribs['id']).'">';
            $label_close = '</label>';
        }
        // add default css
        if (! isset($aAttribs['class'])) $aAttribs['class'] = 'sqmtextarea';
        // create attribute string (do we have to sanitize keys?)
        $sAttribs = '';
        foreach ($aAttribs as $key => $value) {
            $sAttribs.= ' ' . $key . (! is_null($value) ? '="'.htmlspecialchars($value).'"':'');
        }
    } elseif (is_string($aAttribs)) {
        // backward compatibility mode. deprecated.
        $sAttribs = ' ' . $aAttribs;
    } else {
        $sAttribs = '';
    }
    return '<textarea name="'.htmlspecialchars($sName).'" '.
        'rows="'.(int)$iRows .'" cols="'.(int)$iCols.'"'.
        $sAttribs . '>'. $label_open . htmlspecialchars($sText) . $label_close ."</textarea>\n";
}

/**
 * Make a <form> start-tag.
 * @param string $sAction form handler URL
 * @param string $sMethod http method used to submit form data. 'get' or 'post'
 * @param string $sName form name used for identification (used for backward 
 *  compatibility). Use of id is recommended.
 * @param string $sEnctype content type that is used to submit data. html 4.01 
 *  defaults to 'application/x-www-form-urlencoded'. Form with file field needs 
 *  'multipart/form-data' encoding type.
 * @param string $sCharset charset that is used for submitted data
 * @param array $aAttribs (since 1.5.1) extra attributes
 * @return string html formated form start string
 */
function addForm($sAction, $sMethod = 'post', $sName = '', $sEnctype = '', $sCharset = '', $aAttribs = array()) {
    // id tags
    if (! isset($aAttribs['id']) && ! empty($sName))
        $aAttribs['id'] = $sName;

    if($sName) {
        $sName = ' name="'.$sName.'"';
    }
    if($sEnctype) {
        $sEnctype = ' enctype="'.$sEnctype.'"';
    }
    if($sCharset) {
        $sCharset = ' accept-charset="'.htmlspecialchars($sCharset).'"';
    }

    // create attribute string (do we have to sanitize keys?)
    $sAttribs = '';
    foreach ($aAttribs as $key => $value) {
        $sAttribs.= ' ' . $key . (! is_null($value) ? '="'.htmlspecialchars($value).'"':'');
    }

    return '<form action="'. $sAction .'" method="'. $sMethod .'"'.
        $sEnctype . $sName . $sCharset . $sAttribs . ">\n";
}

?>