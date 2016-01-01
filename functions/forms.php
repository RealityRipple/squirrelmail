<?php

/**
 * forms.php - html form functions
 *
 * Functions to build forms in a safe and consistent manner.
 * All attribute values are sanitized with sm_encode_html_special_chars().
//FIXME: I think the Template class might be better place to sanitize inside assign() method
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
 * @copyright 2004-2016 The SquirrelMail Project Team
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

    global $oTemplate;

    $oTemplate->assign('type', $sType);
//FIXME: all the values in the $aAttribs list used to go thru sm_encode_html_special_chars()... I would propose that most everything that is assigned to the template should go thru that *in the template class* on its way between here and the actual template file.  Otherwise we have to do something like:  foreach ($aAttribs as $key => $value) $aAttribs[$key] = sm_encode_html_special_chars($value);
    $oTemplate->assign('aAttribs', $aAttribs);

    return $oTemplate->fetch('input.tpl');

}

/**
 * Password input field
 * @param string $sName field name
 * @param string $sValue initial password value
 * @param integer $iSize field size (number of characters)
 * @param integer $iMaxlength maximum number of characters the user may enter
 * @param array $aAttribs (since 1.5.1) extra attributes - should be given
 *                        in the form array('attribute_name' => 'attribute_value', ...)
 * @return string html formated password field 
 */
function addPwField($sName, $sValue = '', $iSize = 0, $iMaxlength = 0, $aAttribs=array()) {
    $aAttribs['name']  = $sName;
    $aAttribs['value'] = $sValue;
    if ($iSize) $aAttribs['size'] = (int)$iSize;
    if ($iMaxlength) $aAttribs['maxlength'] = (int)$iMaxlength;
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
 * @param string  $sName     Field name
 * @param array   $aValues   Field values array(key => value) results in:
 *                           <option value="key">value</option>, 
 *                           although if $bUsekeys is FALSE, then it changes to:
 *                           <option value="value">value</option>
 * @param mixed   $default   The key(s) that will be selected (it is OK to pass 
 *                           in an array here in the case of multiple select lists)
 * @param boolean $bUsekeys  Use the keys of the array as option value or not
 * @param array   $aAttribs  (since 1.5.1) Extra attributes
 * @param boolean $bMultiple When TRUE, a multiple select list will be shown
 *                           (OPTIONAL; default is FALSE (single select list))
 * @param int     $iSize     Desired height of multiple select boxes
 *                           (OPTIONAL; default is SMOPT_SIZE_NORMAL)
 *                           (only applicable when $bMultiple is TRUE)
 *
 * @return string html formated selection box
 * @todo add attributes argument for option tags and default css
 */
function addSelect($sName, $aValues, $default = null, $bUsekeys = false, $aAttribs = array(), $bMultiple = FALSE, $iSize = SMOPT_SIZE_NORMAL) {
    // only one element
    if (!$bMultiple && count($aValues) == 1) {
        $k = key($aValues); $v = array_pop($aValues);
        return addHidden($sName, ($bUsekeys ? $k : $v), $aAttribs)
             . sm_encode_html_special_chars($v);
    }

    if (! isset($aAttribs['id'])) $aAttribs['id'] = $sName;

    // make sure $default is an array, since multiple select lists
    // need the chance to have more than one default... 
    //
    if (!is_array($default))
       $default = array($default);


    global $oTemplate;

//FIXME: all the values in the $aAttribs list and $sName and both the keys and values in $aValues used to go thru sm_encode_html_special_chars()... I would propose that most everything that is assigned to the template should go thru that *in the template class* on its way between here and the actual template file.  Otherwise we have to do something like:  foreach ($aAttribs as $key => $value) $aAttribs[$key] = sm_encode_html_special_chars($value); $sName = sm_encode_html_special_chars($sName); $aNewValues = array(); foreach ($aValues as $key => $value) $aNewValues[sm_encode_html_special_chars($key)] = sm_encode_html_special_chars($value); $aValues = $aNewValues;   And probably this too because it has to be matched to a value that has already been sanitized: $default = sm_encode_html_special_chars($default);  (oops, watch out for when $default is an array! (multiple select lists))
    $oTemplate->assign('aAttribs', $aAttribs);
    $oTemplate->assign('aValues', $aValues);
    $oTemplate->assign('bUsekeys', $bUsekeys);
    $oTemplate->assign('default', $default);
    $oTemplate->assign('name', $sName);
    $oTemplate->assign('multiple', $bMultiple);
    $oTemplate->assign('size', $iSize);

    return $oTemplate->fetch('select.tpl');
}

/**
 * Normal button
 *
 * Note the switched value/name parameters!
 * Note also that regular buttons are not very useful unless
 * used with onclick handlers, thus are only really appropriate
 * if you use them after having checked if JavaScript is turned
 * on by doing this:  if (checkForJavascript()) ...
 *
 * @param string $sValue button name
 * @param string $sName key name
 * @param array $aAttribs extra attributes
 *
 * @return string html formated submit input field
 *
 * @since 1.5.2
 */
function addButton($sValue, $sName = null, $aAttribs=array()) {
    $aAttribs['value'] = $sValue;
    if (! is_null($sName)) $aAttribs['name'] = $sName;
    // add default css
    if (! isset($aAttribs['class'])) $aAttribs['class'] = 'sqmsubmitfield';
    return addInputField('button', $aAttribs);
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
 *
 * @param string  $sName    field name
 * @param string  $sText    initial field value (OPTIONAL; default empty)
 * @param integer $iCols    field width (number of chars) (OPTIONAL; default 40)
 * @param integer $iRows    field height (number of character rows) (OPTIONAL; default 10)
 * @param array   $aAttribs (since 1.5.1) extra attributes (OPTIONAL; default empty) 
 *
 * @return string html formated text area field
 *
 */
function addTextArea($sName, $sText = '', $iCols = 40, $iRows = 10, $aAttribs = array()) {

    // no longer accept string arguments for attribs; print 
    // backtrace to help people fix their code
    //FIXME: throw error instead?
    if (!is_array($aAttribs)) {
        echo '$aAttribs argument to addTextArea() must be an array<br /><pre>';
        debug_print_backtrace();
        echo '</pre><br />';
        exit;
    }

    // add default css
    else if (!isset($aAttribs['class'])) $aAttribs['class'] = 'sqmtextarea';
    
    if ( empty( $aAttribs['id'] ) ) {
        $aAttribs['id'] = strtr($sName,'[]','__');
    }

    global $oTemplate;

//FIXME: all the values in the $aAttribs list as well as $sName and $sText used to go thru sm_encode_html_special_chars()... I would propose that most everything that is assigned to the template should go thru that *in the template class* on its way between here and the actual template file.  Otherwise we have to do something like:  foreach ($aAttribs as $key => $value) $aAttribs[$key] = sm_encode_html_special_chars($value); $sName = sm_encode_html_special_chars($sName); $sText = sm_encode_html_special_chars($sText);
    $oTemplate->assign('aAttribs', $aAttribs);
    $oTemplate->assign('name', $sName);
    $oTemplate->assign('text', $sText);
    $oTemplate->assign('cols', (int)$iCols);
    $oTemplate->assign('rows', (int)$iRows);

    return $oTemplate->fetch('textarea.tpl');
}

/**
 * Make a <form> start-tag.
 *
 * @param string  $sAction   form handler URL
 * @param string  $sMethod   http method used to submit form data. 'get' or 'post'
 * @param string  $sName     form name used for identification (used for backward 
 *                           compatibility). Use of id is recommended instead.
 * @param string  $sEnctype  content type that is used to submit data. html 4.01 
 *                           defaults to 'application/x-www-form-urlencoded'. Form 
 *                           with file field needs 'multipart/form-data' encoding type.
 * @param string  $sCharset  charset that is used for submitted data
 * @param array   $aAttribs  (since 1.5.1) extra attributes
 * @param boolean $bAddToken (since 1.5.2) When given as a string or as boolean TRUE,
 *                           a hidden input is also added to the form containing a
 *                           security token.  When given as TRUE, the input name is
 *                           "smtoken"; otherwise the name is the string that is
 *                           given for this parameter.  When FALSE, no hidden token
 *                           input field is added.  (OPTIONAL; default not used)
 *
 * @return string html formated form start string
 *
 */
function addForm($sAction, $sMethod = 'post', $sName = '', $sEnctype = '', $sCharset = '', $aAttribs = array(), $bAddToken = FALSE) {

    global $oTemplate;

//FIXME: all the values in the $aAttribs list as well as $charset used to go thru sm_encode_html_special_chars()... I would propose that most everything that is assigned to the template should go thru that *in the template class* on its way between here and the actual template file.  Otherwise we have to do something like:  foreach ($aAttribs as $key => $value) $aAttribs[$key] = sm_encode_html_special_chars($value); $sCharset = sm_encode_html_special_chars($sCharset);
    $oTemplate->assign('aAttribs', $aAttribs);
    $oTemplate->assign('name', $sName);
    $oTemplate->assign('method', $sMethod);
    $oTemplate->assign('action', $sAction);
    $oTemplate->assign('enctype', $sEnctype);
    $oTemplate->assign('charset', $sCharset);

    $sForm = $oTemplate->fetch('form.tpl');

    if ($bAddToken) {
        $sForm .= addHidden((is_string($bAddToken) ? $bAddToken : 'smtoken'),
                            sm_generate_security_token());
    }

    return $sForm;
}

/**
  * Creates unique widget names
  *
  * Names are formatted as such: "send1", "send2", "send3", etc.,
  * where "send" in this example is what was given for $base_name
  *
  * @param string  $base_name    The name upon which to base the
  *                              returned widget name.
  * @param boolean $return_count When TRUE, this function will
  *                              return the last number used to
  *                              create a widget name for $base_name
  *                              (OPTIONAL; default = FALSE).
  *
  * @return mixed When $return_output is FALSE, a string containing
  *               the unique widget name; otherwise an integer with
  *               the last number used to create the last widget
  *               name for the given $base_name (where 0 (zero) means
  *               that no such widgets have been created yet).
  *
  * @since 1.5.2
  *
  */
function unique_widget_name($base_name, $return_count=FALSE)
{
   static $counts = array();

   if (!isset($counts[$base_name]))
      $counts[$base_name] = 0;

   if ($return_count)
      return $counts[$base_name];

   ++$counts[$base_name];
   return $base_name . $counts[$base_name];
}

