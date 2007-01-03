<?php

/**
 * options.php
 *
 * Functions needed to display the options pages.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
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
     * text displayed to the user
     *
     * Used with SMOPT_TYPE_COMMENT options
     * @var string
     */
    var $comment;
    /**
     * additional javascript or other code added to the user input
     * @var string
     */
    var $script;
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
     * or use own sanitizing functions. Currently works only with
     * SMOPT_TYPE_STRLIST.
     * @var bool
     */
    var $htmlencoded=false;
    /**
     * Controls folder list limits in SMOPT_TYPE_FLDRLIST widget.
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
        $this->comment = '';
        $this->script = '';
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
     * Set the comment for this option.
     * @param string $comment
     */
    function setComment($comment) {
        $this->comment = $comment;
    }

    /**
     * Set the script for this option.
     * @param string $script
     */
    function setScript($script) {
        $this->script = $script;
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
     * Set the trailing_text for this option.
     * @param string $folder_filter
     * @since 1.5.1
     */
    function setFolderFilter($folder_filter) {
        $this->folder_filter = $folder_filter;
    }

    /**
     * Creates fields on option pages according to option type
     *
     * Function that calls other createWidget* functions.
     * @return string html formated option field
     */
    function createHTMLWidget() {
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
            case SMOPT_TYPE_HIDDEN:
                $result = $this->createWidget_Hidden();
                break;
            case SMOPT_TYPE_COMMENT:
                $result = $this->createWidget_Comment();
                break;
            case SMOPT_TYPE_FLDRLIST:
                $result = $this->createWidget_FolderList();
                break;
            default:
               $result = '<font color="' . $color[2] . '">'
                       . sprintf(_("Option Type '%s' Not Found"), $this->type)
                       . '</font>';
        }

        /* Add the "post script" for this option. */
        $result .= $this->post_script;

        // put correct value back if need be
        if (!empty($this->new_value)) {
            $this->value = $tempValue;
        }

        /* Now, return the created widget. */
        return ($result);
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

        $result = "<input type=\"text\" name=\"new_$this->name\" value=\"" .
            htmlspecialchars($this->value) .
            "\" size=\"$width\" $this->script />$this->trailing_text\n";
        return ($result);
    }

    /**
     * Create selection box
     * @return string html formated selection box
     */
    function createWidget_StrList() {
        /* Begin the select tag. */
        $result = "<select name=\"new_$this->name\" $this->script>\n";

        /* Add each possible value to the select list. */
        foreach ($this->possible_values as $real_value => $disp_value) {
            /* Start the next new option string. */
            $new_option = '<option value="' .
                ($this->htmlencoded ? $real_value : htmlspecialchars($real_value)) . '"';

            /* If this value is the current value, select it. */
            if ($real_value == $this->value) {
               $new_option .= ' selected="selected"';
            }

            /* Add the display value to our option string. */
            $new_option .= '>' . ($this->htmlencoded ? $disp_value : htmlspecialchars($disp_value)) . "</option>\n";

            /* And add the new option string to our select tag. */
            $result .= $new_option;
        }

        /* Close the select tag and return our happy result. */
        $result .= "</select>$this->trailing_text\n";
        return ($result);
    }

    /**
     * Create folder selection box
     * @return string html formated selection box
     */
    function createWidget_FolderList() {
        $selected = array(strtolower($this->value));

        /* set initial value */
        $result = '';

        /* Add each possible value to the select list. */
        foreach ($this->possible_values as $real_value => $disp_value) {
            if ( is_array($disp_value) ) {
              /* For folder list, we passed in the array of boxes.. */
              $new_option = sqimap_mailbox_option_list(0, $selected, 0, $disp_value, $this->folder_filter);
            } else {
              /* Start the next new option string. */
              $new_option = '<option value="' . htmlspecialchars($real_value) . '"';

              /* If this value is the current value, select it. */
              if ($real_value == $this->value) {
                 $new_option .= ' selected="selected"';
              }

              /* Add the display value to our option string. */
              $new_option .= '>' . htmlspecialchars($disp_value) . "</option>\n";
            }
            /* And add the new option string to our select tag. */
            $result .= $new_option;
        }


        if (empty($result)) {
            // string is displayed when interface can't build folder selection box
            return _("unavailable");
        } else {
            /* Begin the select tag. */
            $ret = "<select name=\"new_$this->name\" $this->script>\n";
            $ret.= $result;
            /* Close the select tag and return our happy result. */
            $ret.= "</select>\n";
            return ($ret);
        }
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
        $result = "<textarea name=\"new_$this->name\" rows=\"$rows\" "
                . "cols=\"$cols\" $this->script>"
                . htmlspecialchars($this->value) . "</textarea>\n";
        return ($result);
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
           return preg_replace('/\/>/', ' onChange="origVal=this.value; newVal=\'\'; '
                    . 'for (i=0;i<origVal.length;i++) { if (origVal.charAt(i)>=\'0\' '
                    . '&& origVal.charAt(i)<=\'9\') newVal += origVal.charAt(i); } '
                    . 'this.value=newVal;" />', $this->createWidget_String());
        else
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
           return preg_replace('/\/>/', ' onChange="origVal=this.value; newVal=\'\'; '
                    . 'for (i=0;i<origVal.length;i++) { if ((origVal.charAt(i)>=\'0\' '
                    . '&& origVal.charAt(i)<=\'9\') || origVal.charAt(i)==\'.\') '
                    . 'newVal += origVal.charAt(i); } this.value=newVal;" />'
                , $this->createWidget_String());
        else
           return $this->createWidget_String();
    }

    /**
     * Creates radio field (yes/no)
     * @return string html formated radio field
     */
    function createWidget_Boolean() {
        /* Do the whole current value thing. */
        if ($this->value != SMPREF_NO) {
            $yes_chk = ' checked="checked"';
            $no_chk = '';
        } else {
            $yes_chk = '';
            $no_chk = ' checked="checked"';
        }

        /* Build the yes choice. */
        $yes_option = '<input type="radio" id="new_' . $this->name . '_yes" '
                    . 'name="new_' . $this->name . '" value="' . SMPREF_YES . '"'
                    . $yes_chk . ' ' . $this->script . ' />&nbsp;'
                    . '<label for="new_'.$this->name.'_yes">' . _("Yes") . '</label>';

        /* Build the no choice. */
        $no_option = '<input type="radio" id="new_' . $this->name . '_no" '
                   . 'name="new_' . $this->name . '" value="' . SMPREF_NO . '"'
                   . $no_chk . ' ' . $this->script . ' />&nbsp;'
                    . '<label for="new_'.$this->name.'_no">' . _("No") . '</label>';

        /* Build and return the combined "boolean widget". */
        $result = "$yes_option&nbsp;&nbsp;&nbsp;&nbsp;$no_option";
        return ($result);
    }

    /**
     * Creates hidden field
     * @return string html formated hidden input field
     */
    function createWidget_Hidden() {
        $result = '<input type="hidden" name="new_' . $this->name
                . '" value="' . htmlspecialchars($this->value)
                . '" ' . $this->script . ' />';
        return ($result);
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
        return ($this->value != $this->new_value);
    }
} /* End of SquirrelOption class*/

/**
 * Saves option
 * @param object $option object that holds option name and new_value
 */
function save_option($option) {
    if ( !sqgetGlobalVar('username', $username, SQ_SESSION ) ) {
        /* Can't save the pref if we don't have the username */
        return;
    }
    global $data_dir;
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
    return create_hidden_element('optpage', $optpage);
}

/**
 * Create hidden 'optmode' input field with value set by argument
 * @param string $optmode
 * @return string html formated hidden input field
 */
function create_optmode_element($optmode) {
    return create_hidden_element('optmode', $optmode);
}

/**
 * Create hidden field.
 * @param string $name field name
 * @param string $value field value
 * @return string html formated hidden input field
 */
function create_hidden_element($name, $value) {
    $result = '<input type="hidden" '
            . 'name="' . $name . '" '
            . 'value="' . htmlspecialchars($value) . '" />';
    return ($result);
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

            /* If provided, set the comment for this option. */
            if (isset($optset['comment'])) {
                $next_option->setComment($optset['comment']);
            }

            /* If provided, set the save function for this option. */
            if (isset($optset['save'])) {
                $next_option->setSaveFunction($optset['save']);
            }

            /* If provided, set the script for this option. */
            if (isset($optset['script'])) {
                $next_option->setScript($optset['script']);
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

// vim: et ts=4
