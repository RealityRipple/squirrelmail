<?php

/**
 * options.php
 *
 * Functions needed to display the options pages.
 *
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage prefs
 */

/**********************************************/
/* Define constants used in the options code. */
/**********************************************/

/* Define constants for the various option types. */
define('SMOPT_TYPE_STRING', 0);
define('SMOPT_TYPE_STRLIST', 1);
define('SMOPT_TYPE_TEXTAREA', 2);
define('SMOPT_TYPE_INTEGER', 3);
define('SMOPT_TYPE_FLOAT', 4);
define('SMOPT_TYPE_BOOLEAN', 5);
define('SMOPT_TYPE_HIDDEN', 6);
define('SMOPT_TYPE_COMMENT', 7);
define('SMOPT_TYPE_FLDRLIST', 8);
define('SMOPT_TYPE_FLDRLIST_MULTI', 9);
define('SMOPT_TYPE_EDIT_LIST', 10);
define('SMOPT_TYPE_STRLIST_MULTI', 11);
define('SMOPT_TYPE_BOOLEAN_CHECKBOX', 12);
define('SMOPT_TYPE_BOOLEAN_RADIO', 13);

/* Define constants for the options refresh levels. */
define('SMOPT_REFRESH_NONE', 0);
define('SMOPT_REFRESH_FOLDERLIST', 1);
define('SMOPT_REFRESH_ALL', 2);

/* Define constants for the options size. */
define('SMOPT_SIZE_TINY', 0);
define('SMOPT_SIZE_SMALL', 1);
define('SMOPT_SIZE_MEDIUM', 2);
define('SMOPT_SIZE_LARGE', 3);
define('SMOPT_SIZE_HUGE', 4);
define('SMOPT_SIZE_NORMAL', 5);

define('SMOPT_SAVE_DEFAULT', 'save_option');
define('SMOPT_SAVE_NOOP', 'save_option_noop');

/**
 * SquirrelOption: An option for SquirrelMail.
 *
 * @package squirrelmail
 * @subpackage prefs
 */
class SquirrelOption {
    /**
     * The name of this setting
     * @var string
     */
    var $name;
    /**
     * The text that prefaces setting on the preferences page
     * @var string
     */
    var $caption;
    /**
     * The type of INPUT element
     *
     * See SMOPT_TYPE_* defines
     * @var integer
     */
    var $type;
    /**
     * Indicates if a link should be shown to refresh part
     * or all of the window
     *
     * See SMOPT_REFRESH_* defines
     * @var integer
     */
    var $refresh_level;
    /**
     * Specifies the size of certain input items
     *
     * See SMOPT_SIZE_* defines
     * @var integer
     */
    var $size;
    /**
     * Text that follows a text input or
     * select list input on the preferences page
     *
     * useful for indicating units, meanings of special values, etc.
     * @var string
     */
    var $trailing_text;
    /**
     * Text that overrides the "Yes" label for boolean 
     * radio option widgets
     *
     * @var string
     */
    var $yes_text;
    /**
     * Text that overrides the "No" label for boolean 
     * radio option widgets
     *
     * @var string
     */
    var $no_text;
    /**
     * text displayed to the user
     *
     * Used with SMOPT_TYPE_COMMENT options
     * @var string
     */
    var $comment;
    /**
     * additional javascript or other widget attributes added to the 
     * user input; must be an array where keys are attribute names
     * ("onclick", etc) and values are the attribute values.
     * @var array
     */
    var $aExtraAttribs;
    /**
     * script (usually Javascript) that will be placed after (outside of)
     * the INPUT tag
     * @var string
     */
    var $post_script;

    /**
     * The name of the Save Function for this option.
     * @var string
     */
    var $save_function;

    /* The various 'values' for this options. */
    /**
     * default/preselected value for this option
     * @var mixed
     */
    var $value;
    /**
     * new option value
     * @var mixed
     */
    var $new_value;
    /**
     * associative array, where each key is an actual input value
     * and the corresponding value is what is displayed to the user
     * for that list item in the drop-down list
     * @var array
     */
    var $possible_values;
    /**
     * disables html sanitizing.
     *
     * WARNING - don't use it, if user input is possible in option
     * or use own sanitizing functions. Currently only works for SMOPT_TYPE_STRLIST.
     * @var bool
     */
    var $htmlencoded=false;
    /**
     * Controls folder list limits in SMOPT_TYPE_FLDRLIST and
     * SMOPT_TYPE_FLDRLIST_MULTI widgets.
     * See $flag argument in sqimap_mailbox_option_list() function.
     * @var string
     * @since 1.5.1
     */
    var $folder_filter='noselect';

    /**
     * Constructor function
     * @param string $name
     * @param string $caption
     * @param integer $type
     * @param integer $refresh_level
     * @param mixed $initial_value
     * @param array $possible_values
     * @param bool $htmlencoded
     */
    function SquirrelOption
    ($name, $caption, $type, $refresh_level, $initial_value = '', $possible_values = '', $htmlencoded = false) {
        /* Set the basic stuff. */
        $this->name = $name;
        $this->caption = $caption;
        $this->type = $type;
        $this->refresh_level = $refresh_level;
        $this->possible_values = $possible_values;
        $this->htmlencoded = $htmlencoded;
        $this->size = SMOPT_SIZE_MEDIUM;
        $this->trailing_text = '';
        $this->yes_text = '';
        $this->no_text = '';
        $this->comment = '';
        $this->aExtraAttribs = array();
        $this->post_script = '';

        //Check for a current value.  
        if (isset($GLOBALS[$name])) {
            $this->value = $GLOBALS[$name];
        } else if (!empty($initial_value)) {
            $this->value = $initial_value;
        } else {
            $this->value = '';
        }

        /* Check for a new value. */
        if ( !sqgetGlobalVar("new_$name", $this->new_value, SQ_POST ) ) {
            $this->new_value = '';
        }

        /* Set the default save function. */
        if (($type != SMOPT_TYPE_HIDDEN) && ($type != SMOPT_TYPE_COMMENT)) {
            $this->save_function = SMOPT_SAVE_DEFAULT;
        } else {
            $this->save_function = SMOPT_SAVE_NOOP;
        }
    }

    /** Convenience function that identifies which types of
        widgets are stored as (serialized) array values. */
    function is_multiple_valued() {
        return ($this->type == SMOPT_TYPE_FLDRLIST_MULTI
             || $this->type == SMOPT_TYPE_STRLIST_MULTI
             || $this->type == SMOPT_TYPE_EDIT_LIST);
    }

    /**
     * Set the value for this option.
     * @param mixed $value
     */
    function setValue($value) {
        $this->value = $value;
    }

    /**
     * Set the new value for this option.
     * @param mixed $new_value
     */
    function setNewValue($new_value) {
        $this->new_value = $new_value;
    }

    /**
     * Set the size for this option.
     * @param integer $size
     */
    function setSize($size) {
        $this->size = $size;
    }

    /**
     * Set the trailing_text for this option.
     * @param string $trailing_text
     */
    function setTrailingText($trailing_text) {
        $this->trailing_text = $trailing_text;
    }

    /**
     * Set the yes_text for this option.
     * @param string $yes_text
     */
    function setYesText($yes_text) {
        $this->yes_text = $yes_text;
    }

    /**
     * Set the no_text for this option.
     * @param string $no_text
     */
    function setNoText($no_text) {
        $this->no_text = $no_text;
    }

    /**
     * Set the comment for this option.
     * @param string $comment
     */
    function setComment($comment) {
        $this->comment = $comment;
    }

    /**
     * Set the extra attributes for this option.
     * @param array $aExtraAttribs
     */
    function setExtraAttributes($aExtraAttribs) {
        $this->aExtraAttribs = $aExtraAttribs;
    }

    /**
     * Set the "post script" for this option.
     * @param string $post_script
     */
    function setPostScript($post_script) {
        $this->post_script = $post_script;
    }

    /**
     * Set the save function for this option.
     * @param string $save_function
     */
    function setSaveFunction($save_function) {
        $this->save_function = $save_function;
    }

    /**
     * Set the folder_filter for this option.
     * @param string $folder_filter
     * @since 1.5.1
     */
    function setFolderFilter($folder_filter) {
        $this->folder_filter = $folder_filter;
    }

    /**
     * Creates fields on option pages according to option type
     *
     * This is the function that calls all other createWidget* functions.
     *
     * @return string The formated option field
     *
     */
    function createWidget() {
        global $color;

        // Use new value if available
        if (!empty($this->new_value)) {
            $tempValue = $this->value;
            $this->value = $this->new_value;
        }

        /* Get the widget for this option type. */
        switch ($this->type) {
            case SMOPT_TYPE_STRING:
                $result = $this->createWidget_String();
                break;
            case SMOPT_TYPE_STRLIST:
                $result = $this->createWidget_StrList();
                break;
            case SMOPT_TYPE_TEXTAREA:
                $result = $this->createWidget_TextArea();
                break;
            case SMOPT_TYPE_INTEGER:
                $result = $this->createWidget_Integer();
                break;
            case SMOPT_TYPE_FLOAT:
                $result = $this->createWidget_Float();
                break;
            case SMOPT_TYPE_BOOLEAN:
                $result = $this->createWidget_Boolean();
                break;
            case SMOPT_TYPE_BOOLEAN_CHECKBOX:
                $result = $this->createWidget_Boolean(TRUE);
                break;
            case SMOPT_TYPE_BOOLEAN_RADIO:
                $result = $this->createWidget_Boolean(FALSE);
                break;
            case SMOPT_TYPE_HIDDEN:
                $result = $this->createWidget_Hidden();
                break;
            case SMOPT_TYPE_COMMENT:
                $result = $this->createWidget_Comment();
                break;
            case SMOPT_TYPE_FLDRLIST:
                $result = $this->createWidget_FolderList();
                break;
            case SMOPT_TYPE_FLDRLIST_MULTI:
                $result = $this->createWidget_FolderList(TRUE);
                break;
            case SMOPT_TYPE_EDIT_LIST:
                $result = $this->createWidget_EditList();
                break;
            case SMOPT_TYPE_STRLIST_MULTI:
                $result = $this->createWidget_StrList(TRUE);
                break;
            default:
                error_box ( 
                    sprintf(_("Option Type '%s' Not Found"), $this->type)
                    );
        }

        /* Add the "post script" for this option. */
        $result .= $this->post_script;

        // put correct value back if need be
        if (!empty($this->new_value)) {
            $this->value = $tempValue;
        }

        /* Now, return the created widget. */
        return $result;
    }

    /**
     * Create string field
     * @return string html formated option field
     */
    function createWidget_String() {
        switch ($this->size) {
            case SMOPT_SIZE_TINY:
                $width = 5;
                break;
            case SMOPT_SIZE_SMALL:
                $width = 12;
                break;
            case SMOPT_SIZE_LARGE:
                $width = 38;
                break;
            case SMOPT_SIZE_HUGE:
                $width = 50;
                break;
            case SMOPT_SIZE_NORMAL:
            default:
                $width = 25;
        }

        return addInput('new_' . $this->name, $this->value, $width, 0, $this->aExtraAttribs) . htmlspecialchars($this->trailing_text);
    }

    /**
     * Create selection box
     *
     * When $this->htmlencoded is TRUE, the keys and values in 
     * $this->possible_values are assumed to be display-safe.  
     * Use with care!
     *
     * @param boolean $multiple_select When TRUE, the select widget 
     *                                 will allow multiple selections
     *                                 (OPTIONAL; default is FALSE 
     *                                 (single select list))
     *
     * @return string html formated selection box
     *
     */
    function createWidget_StrList($multiple_select=FALSE) {
//FIXME: Currently, $this->htmlencoded is ignored here -- was removed when changing to template-based output; a fix is available as part of proposed centralized sanitizing patch

        switch ($this->size) {
//FIXME: not sure about these sizes... seems like we could add another on the "large" side...
            case SMOPT_SIZE_TINY:
                $height = 3;
                break;
            case SMOPT_SIZE_SMALL:
                $height = 8;
                break;
            case SMOPT_SIZE_LARGE:
                $height = 15;
                break;
            case SMOPT_SIZE_HUGE:
                $height = 25;
                break;
            case SMOPT_SIZE_NORMAL:
            default:
                $height = 5;
        }

        return addSelect('new_' . $this->name, $this->possible_values, $this->value, TRUE, $this->aExtraAttribs, $multiple_select, $height, !$this->htmlencoded) . htmlspecialchars($this->trailing_text);

    }

    /**
     * Create folder selection box
     *
     * @param boolean $multiple_select When TRUE, the select widget 
     *                                 will allow multiple selections
     *                                 (OPTIONAL; default is FALSE 
     *                                 (single select list))
     *
     * @return string html formated selection box
     *
     */
    function createWidget_FolderList($multiple_select=FALSE) {

        switch ($this->size) {
//FIXME: not sure about these sizes... seems like we could add another on the "large" side...
            case SMOPT_SIZE_TINY:
                $height = 3;
                break;
            case SMOPT_SIZE_SMALL:
                $height = 8;
                break;
            case SMOPT_SIZE_LARGE:
                $height = 15;
                break;
            case SMOPT_SIZE_HUGE:
                $height = 25;
                break;
            case SMOPT_SIZE_NORMAL:
            default:
                $height = 5;
        }

        // possible values might include a nested array of 
        // possible values (list of folders)
        //
        $option_list = array();
        foreach ($this->possible_values as $value => $text) {

            // list of folders (boxes array)
            //
            if (is_array($text)) {
              $option_list = array_merge($option_list, sqimap_mailbox_option_array(0, 0, $text, $this->folder_filter));

            // just one option here
            //
            } else {
              $option_list = array_merge($option_list, array($value => $text));
            }

        }
        if (empty($option_list))
            $option_list = array('ignore' => _("unavailable"));


        return addSelect('new_' . $this->name, $option_list, $this->value, TRUE, $this->aExtraAttribs, $multiple_select, $height) . htmlspecialchars($this->trailing_text);

    }

    /**
     * Creates textarea
     * @return string html formated textarea field
     */
    function createWidget_TextArea() {
        switch ($this->size) {
            case SMOPT_SIZE_TINY:  $rows = 3; $cols =  10; break;
            case SMOPT_SIZE_SMALL: $rows = 4; $cols =  30; break;
            case SMOPT_SIZE_LARGE: $rows = 10; $cols =  60; break;
            case SMOPT_SIZE_HUGE:  $rows = 20; $cols =  80; break;
            case SMOPT_SIZE_NORMAL:
            default: $rows = 5; $cols =  50;
        }
        return addTextArea('new_' . $this->name, $this->value, $cols, $rows, $this->aExtraAttribs);
    }

    /**
     * Creates field for integer
     *
     * Difference from createWidget_String is visible only when javascript is enabled
     * @return string html formated option field
     */
    function createWidget_Integer() {

        // add onChange javascript handler to a regular string widget
        // which will strip out all non-numeric chars
        if (checkForJavascript())
           $this->aExtraAttribs['onchange'] = 'origVal=this.value; newVal=\'\'; '
                    . 'for (i=0;i<origVal.length;i++) { if (origVal.charAt(i)>=\'0\' '
                    . '&& origVal.charAt(i)<=\'9\') newVal += origVal.charAt(i); } '
                    . 'this.value=newVal;';

        return $this->createWidget_String();
    }

    /**
     * Creates field for floating number
     * Difference from createWidget_String is visible only when javascript is enabled
     * @return string html formated option field
     */
    function createWidget_Float() {

        // add onChange javascript handler to a regular string widget
        // which will strip out all non-numeric (period also OK) chars
        if (checkForJavascript())
           $this->aExtraAttribs['onchange'] = 'origVal=this.value; newVal=\'\'; '
                    . 'for (i=0;i<origVal.length;i++) { if ((origVal.charAt(i)>=\'0\' '
                    . '&& origVal.charAt(i)<=\'9\') || origVal.charAt(i)==\'.\') '
                    . 'newVal += origVal.charAt(i); } this.value=newVal;';

        return $this->createWidget_String();
    }

    /**
     * Create boolean widget
     *
     * When creating Yes/No radio buttons, the "yes_text"
     * and "no_text" option attributes are used to override
     * the typical "Yes" and "No" text.
     *
     * @param boolean $checkbox When TRUE, the widget will be
     *                          constructed as a checkbox,
     *                          otherwise it will be a set of
     *                          Yes/No radio buttons (OPTIONAL;
     *                          default is TRUE (checkbox)).
     *
     * @return string html formated boolean widget
     *
     */
    function createWidget_Boolean($checkbox=TRUE) {

        global $oTemplate, $nbsp;


        // checkbox...
        //
        if ($checkbox) {
            $result = addCheckbox('new_' . $this->name, ($this->value != SMPREF_NO), SMPREF_YES, array_merge(array('id' => 'new_' . $this->name), $this->aExtraAttribs)) . $nbsp . create_label($this->trailing_text, 'new_' . $this->name);
        }

        // radio buttons...
        //
        else {

            /* Build the yes choice. */
            $yes_option = addRadioBox('new_' . $this->name, ($this->value != SMPREF_NO), SMPREF_YES, array_merge(array('id' => 'new_' . $this->name . '_yes'), $this->aExtraAttribs)) . $nbsp . create_label((!empty($this->yes_text) ? $this->yes_text : _("Yes")), 'new_' . $this->name . '_yes');

            /* Build the no choice. */
            $no_option = addRadioBox('new_' . $this->name, ($this->value == SMPREF_NO), SMPREF_NO, array_merge(array('id' => 'new_' . $this->name . '_no'), $this->aExtraAttribs)) . $nbsp . create_label((!empty($this->no_text) ? $this->no_text : _("No")), 'new_' . $this->name . '_no');

            /* Build the combined "boolean widget". */
            $result = "$yes_option$nbsp$nbsp$nbsp$nbsp$no_option";

        }

        return ($result);
    }

    /**
     * Creates hidden field
     * @return string html formated hidden input field
     */
    function createWidget_Hidden() {
        return addHidden('new_' . $this->name, $this->value, $this->aExtraAttribs);
    }

    /**
     * Creates comment
     * @return string comment
     */
    function createWidget_Comment() {
        $result = $this->comment;
        return ($result);
    }

    /**
     * Creates an edit list
     * @return string html formated list of edit fields and
     *                their associated controls
     */
    function createWidget_EditList() {

        global $oTemplate;

        switch ($this->size) {
//FIXME: not sure about these sizes... seems like we could add another on the "large" side...
            case SMOPT_SIZE_TINY:
                $height = 3;
                break;
            case SMOPT_SIZE_SMALL:
                $height = 8;
                break;
            case SMOPT_SIZE_LARGE:
                $height = 15;
                break;
            case SMOPT_SIZE_HUGE:
                $height = 25;
                break;
            case SMOPT_SIZE_NORMAL:
            default:
                $height = 5;
        }

        if (empty($this->possible_values)) $this->possible_values = array();
        if (!is_array($this->possible_values)) $this->possible_values = array($this->possible_values);

//FIXME: $this->aExtraAttribs probably should only be used in one place
        $oTemplate->assign('input_widget', addInput('add_' . $this->name, '', 38, 0, $this->aExtraAttribs));
        $oTemplate->assign('trailing_text', $this->trailing_text);
        $oTemplate->assign('select_widget', addSelect('new_' . $this->name, $this->possible_values, $this->value, FALSE, !checkForJavascript() ? $this->aExtraAttribs : array_merge(array('onchange' => 'if (typeof(window.addinput) == \'undefined\') { var f = document.forms.length; var i = 0; var pos = -1; while( pos == -1 && i < f ) { var e = document.forms[i].elements.length; var j = 0; while( pos == -1 && j < e ) { if ( document.forms[i].elements[j].type == \'text\' && document.forms[i].elements[j].name == \'add_' . $this->name . '\' ) { pos = j; } j++; } i++; } if( pos >= 0 ) { window.addinput = document.forms[i-1].elements[pos]; } } for (x = 0; x < this.length; x++) { if (this.options[x].selected) { window.addinput.value = this.options[x].value; break; } }'), $this->aExtraAttribs), TRUE, $height));
        $oTemplate->assign('checkbox_widget', addCheckBox('delete_' . $this->name, FALSE, SMPREF_YES, array_merge(array('id' => 'delete_' . $this->name), $this->aExtraAttribs)));
        $oTemplate->assign('name', $this->name);
        return $oTemplate->fetch('edit_list_widget.tpl');

    }

    /**
     *
     */
    function save() {
        $function = $this->save_function;
        $function($this);
    }

    /**
     *
     */
    function changed() {

        // edit lists have a lot going on, so we'll always process them
        //
        if ($this->type == SMOPT_TYPE_EDIT_LIST) return TRUE;

        return ($this->value != $this->new_value);
    }
} /* End of SquirrelOption class*/

/**
 * Saves the option value (this is the default save function
 * unless overridden by the user)
 *
 * @param object $option object that holds option name and new_value
 */
function save_option($option) {

    // Can't save the pref if we don't have the username
    //
    if ( !sqgetGlobalVar('username', $username, SQ_SESSION ) ) {
        return;
    }

    global $data_dir;

    // edit lists: first add new elements to list, then
    // remove any selected ones (note that we must add
    // before deleting because the javascript that populates
    // the "add" textbox when selecting items in the list
    // (for deletion))
    //
    if ($option->type == SMOPT_TYPE_EDIT_LIST) {

        if (empty($option->possible_values)) $option->possible_values = array();
        if (!is_array($option->possible_values)) $option->possible_values = array($option->possible_values);

        // add element if given
        //
        if (sqGetGlobalVar('add_' . $option->name, $new_element, SQ_POST)) {
            $new_element = trim($new_element);
            if (!empty($new_element)
             && !in_array($new_element, $option->possible_values))
                $option->possible_values[] = $new_element;
        }
        
        // delete selected elements if needed
        //
        if (is_array($option->new_value)
         && sqGetGlobalVar('delete_' . $option->name, $ignore, SQ_POST))
            $option->possible_values = array_diff($option->possible_values, $option->new_value);

        // save full list (stored in "possible_values")
        //
        setPref($data_dir, $username, $option->name, serialize($option->possible_values));

    // Certain option types need to be serialized because
    // they are not scalar
    //
    } else if ($option->is_multiple_valued())
        setPref($data_dir, $username, $option->name, serialize($option->new_value));

    else
        setPref($data_dir, $username, $option->name, $option->new_value);

}

/**
 * save function that does not save
 * @param object $option
 */
function save_option_noop($option) {
    /* Do nothing here... */
}

/**
 * Create hidden 'optpage' input field with value set by argument
 * @param string $optpage identification of option page
 * @return string html formated hidden input field
 */
function create_optpage_element($optpage) {
    return addHidden('optpage', $optpage);
}

/**
 * Create hidden 'optmode' input field with value set by argument
 * @param string $optmode
 * @return string html formated hidden input field
 */
function create_optmode_element($optmode) {
    return addHidden('optmode', $optmode);
}

/**
 * @param array $optgrps
 * @param array $optvals
 * @return array
 */
function create_option_groups($optgrps, $optvals) {
    /* Build a simple array with which to start. */
    $result = array();

    /* Create option group for each option group name. */
    foreach ($optgrps as $grpkey => $grpname) {
        $result[$grpkey] = array();
        $result[$grpkey]['name'] = $grpname;
        $result[$grpkey]['options'] = array();
    }

     /* Create a new SquirrelOption for each set of option values. */
    foreach ($optvals as $grpkey => $grpopts) {
        foreach ($grpopts as $optset) {
            /* Create a new option with all values given. */
            $next_option = new SquirrelOption(
                    $optset['name'],
                    $optset['caption'],
                    $optset['type'],
                    (isset($optset['refresh']) ? $optset['refresh'] : SMOPT_REFRESH_NONE),
                    (isset($optset['initial_value']) ? $optset['initial_value'] : ''),
                    (isset($optset['posvals']) ? $optset['posvals'] : ''),
                    (isset($optset['htmlencoded']) ? $optset['htmlencoded'] : false)
                    );

            /* If provided, set the size for this option. */
            if (isset($optset['size'])) {
                $next_option->setSize($optset['size']);
            }

            /* If provided, set the trailing_text for this option. */
            if (isset($optset['trailing_text'])) {
                $next_option->setTrailingText($optset['trailing_text']);
            }

            /* If provided, set the yes_text for this option. */
            if (isset($optset['yes_text'])) {
                $next_option->setYesText($optset['yes_text']);
            }

            /* If provided, set the no_text for this option. */
            if (isset($optset['no_text'])) {
                $next_option->setNoText($optset['no_text']);
            }

            /* If provided, set the comment for this option. */
            if (isset($optset['comment'])) {
                $next_option->setComment($optset['comment']);
            }

            /* If provided, set the save function for this option. */
            if (isset($optset['save'])) {
                $next_option->setSaveFunction($optset['save']);
            }

            /* If provided, set the extra attributes for this option. */
            if (isset($optset['extra_attributes'])) {
                $next_option->setExtraAttributes($optset['extra_attributes']);
            }

            /* If provided, set the "post script" for this option. */
            if (isset($optset['post_script'])) {
                $next_option->setPostScript($optset['post_script']);
            }

            /* If provided, set the folder_filter for this option. */
            if (isset($optset['folder_filter'])) {
                $next_option->setFolderFilter($optset['folder_filter']);
            }

            /* Add this option to the option array. */
            $result[$grpkey]['options'][] = $next_option;
        }
    }

    /* Return our resulting array. */
    return ($result);
}

