<?php

/**
 * options.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Functions needed to display the options pages.
 *
 * $Id$
 */

/**********************************************/
/* Define constants used in the options code. */
/**********************************************/

/* Define constants for the various option types. */
define('SMOPT_TYPE_STRING', 0);
define('SMOPT_TYPE_STRLIST', 1);
define('SMOPT_TYPE_textarea', 2);
define('SMOPT_TYPE_INTEGER', 3);
define('SMOPT_TYPE_FLOAT', 4);
define('SMOPT_TYPE_BOOLEAN', 5);
define('SMOPT_TYPE_HIDDEN', 6);
define('SMOPT_TYPE_COMMENT', 7);

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
 * SquirrelOption: An option for Squirrelmail.
 *
 * This class is a work in progress. When complete, it will handle
 * presentation and saving of Squirrelmail user options in a simple,
 * streamline manner. Stay tuned for more stuff.
 *
 * Also, I'd like to ask that people leave this alone (mostly :) until
 * I get it a little further along. That should only be a day or two or
 * three. I will remove this message when it is ready for primetime usage.
 */
class SquirrelOption {
    /* The basic stuff. */
    var $name;
    var $caption;
    var $type;
    var $refresh_level;
    var $size;
    var $comment;
    var $script;

    /* The name of the Save Function for this option. */
    var $save_function;

    /* The various 'values' for this options. */
    var $value;
    var $new_value;
    var $possible_values;

    function SquirrelOption
    ($name, $caption, $type, $refresh_level, $possible_values = '') {
        /* Set the basic stuff. */
        $this->name = $name;
        $this->caption = $caption;
        $this->type = $type;
        $this->refresh_level = $refresh_level;
        $this->possible_values = $possible_values;
        $this->size = SMOPT_SIZE_MEDIUM;
        $this->comment = '';
        $this->script = '';

        /* Check for a current value. */
        if (isset($GLOBALS[$name])) {
            $this->value = $GLOBALS[$name];
        } else {
            $this->value = '';
        }

        /* Check for a new value. */
        if (isset($GLOBALS["new_$name"])) {
            $this->new_value = $GLOBALS["new_$name"];
        } else {
            $this->new_value = '';
        }

        /* Set the default save function. */
        if (($type != SMOPT_TYPE_HIDDEN) && ($type != SMOPT_TYPE_COMMENT)) {
            $this->save_function = SMOPT_SAVE_DEFAULT;
        } else {
            $this->save_function = SMOPT_SAVE_NOOP;
        }
    }

    /* Set the value for this option. */
    function setValue($value) {
        $this->value = $value;
    }

    /* Set the new value for this option. */
    function setNewValue($new_value) {
        $this->new_value = $new_value;
    }

    /* Set the size for this option. */
    function setSize($size) {
        $this->size = $size;
    }

    /* Set the comment for this option. */
    function setComment($comment) {
        $this->comment = $comment;
    }

    /* Set the script for this option. */
    function setScript($script) {
        $this->script = $script;
    }

    /* Set the save function for this option. */
    function setSaveFunction($save_function) {
        $this->save_function = $save_function;
    }

    function createHTMLWidget() {
        global $javascript_on;

        /* Get the widget for this option type. */
        switch ($this->type) {
            case SMOPT_TYPE_STRING:
                $result = $this->createWidget_String();
                break;
            case SMOPT_TYPE_STRLIST:
                $result = $this->createWidget_StrList();
                break;
            case SMOPT_TYPE_textarea:
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
            default:
               $result = '<font color="' . $color[2] . '">'
                       . sprintf(_("Option Type '%s' Not Found"), $this->type)
                       . '</font>';
        }

        /* Add the script for this option. */
        $result .= $this->script;

        /* Now, return the created widget. */
        return ($result);
    }

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

        $result = "<input name=\"new_$this->name\" value=\"$this->value\" size=\"$width\">";
        return ($result);
    }

    function createWidget_StrList() {
        /* Begin the select tag. */
        $result = "<select name=\"new_$this->name\">";

        /* Add each possible value to the select list. */
        foreach ($this->possible_values as $real_value => $disp_value) {
            /* Start the next new option string. */
            $new_option = "<option value=\"$real_value\"";

            /* If this value is the current value, select it. */
            if ($real_value == $this->value) {
               $new_option .= ' selected';
            }

            /* Add the display value to our option string. */
            $new_option .= ">$disp_value</option>";

            /* And add the new option string to our select tag. */
            $result .= $new_option;
        }

        /* Close the select tag and return our happy result. */
        $result .= '</select>';
        return ($result);
    }

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
                . "cols=\"$cols\">$this->value</textarea>";
        return ($result);
    }

    function createWidget_Integer() {
        return ($this->createWidget_String());
    }

    function createWidget_Float() {
        return ($this->createWidget_String());
    }

    function createWidget_Boolean() {
        /* Do the whole current value thing. */
        if ($this->value != SMPREF_NO) {
            $yes_chk = ' checked';
            $no_chk = '';
        } else {
            $yes_chk = '';
            $no_chk = ' checked';
        }

        /* Build the yes choice. */
        $yes_option = '<input type="radio" name="new_' . $this->name
                    . '" value="' . SMPREF_YES . "\"$yes_chk>&nbsp;"
                    . _("Yes");

        /* Build the no choice. */
        $no_option = '<input type="radio" name="new_' . $this->name
                   . '" value="' . SMPREF_NO . "\"$no_chk>&nbsp;"
                   . _("No");

        /* Build and return the combined "boolean widget". */
        $result = "$yes_option&nbsp;&nbsp;&nbsp;&nbsp;$no_option";
        return ($result);
    }

    function createWidget_Hidden() {
        $result = '<input type="hidden" name="new_' . $this->name
                . '" value="' . $this->value . '">';
        return ($result);
    }

    function createWidget_Comment() {
        $result = $this->comment;
        return ($result);
    }

    function save() {
        $function = $this->save_function;
        $function($this);
    }

    function changed() {
        return ($this->value !== $this->new_value);
    }
}

function save_option($option) {
    global $data_dir, $username;
    setPref($data_dir, $username, $option->name, $option->new_value);

    /* I do not know if this next line does any good. */
    $GLOBALS[$option->name] = $option->new_value;
}

function save_option_noop($option) {
    /* Do nothing here... */
}

function create_optpage_element($optpage) {
    return create_hidden_element('optpage', $optpage);
}

function create_optmode_element($optmode) {
    return create_hidden_element('optmode', $optmode);
}

function create_hidden_element($name, $value) {
    $result = '<input type="hidden" '
            . 'name="' . $name . '" '
            . 'value="' . $value . '">';
    return ($result);
}

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
            if (isset($optset['posvals'])) {
                /* Create a new option with all values given. */
                $next_option = new SquirrelOption(
                    $optset['name'],
                    $optset['caption'],
                    $optset['type'],
                    $optset['refresh'],
                    $optset['posvals']
                );
            } else {
                /* Create a new option with all but possible values given. */
                $next_option = new SquirrelOption(
                    $optset['name'],
                    $optset['caption'],
                    $optset['type'],
                    $optset['refresh']
                );
            }

            /* If provided, set the size for this option. */
            if (isset($optset['size'])) {
                $next_option->setSize($optset['size']);
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

            /* Add this option to the option array. */
            $result[$grpkey]['options'][] = $next_option;
        }
    }

    /* Return our resulting array. */
    return ($result);
}

function print_option_groups($option_groups) {
    /* Print each option group. */
    foreach ($option_groups as $next_optgrp) {
        /* If it is not blank, print the name for this option group. */
        if ($next_optgrp['name'] != '') {
            echo html_tag( 'tr', "\n".
                        html_tag( 'td',
                            '<b>' . $next_optgrp['name'] . '</b>' ,
                        'center' ,'', 'valign="middle" colspan="2" nowrap' )
                    ) ."\n";
        }

        /* Print each option in this option group. */
        foreach ($next_optgrp['options'] as $option) {
            if ($option->type != SMOPT_TYPE_HIDDEN) {
                echo html_tag( 'tr', "\n".
                           html_tag( 'td', $option->caption . ':', 'right' ,'', 'valign="middle"' ) .
                           html_tag( 'td', $option->createHTMLWidget(), 'left' )
                       ) ."\n";
            } else {
                echo $option->createHTMLWidget();
            }
        }

        /* Print an empty row after this option group. */
        echo html_tag( 'tr',
                   html_tag( 'td', '&nbsp;', 'left', '', 'colspan="2"' )
                ) . "\n";
    }
}

function OptionSubmit( $name ) {
        echo html_tag( 'tr',
                   html_tag( 'td', '&nbsp;', 'left', '', 'colspan="2"' ) .
                   html_tag( 'td', '<input type="submit" value="' . _("Submit") . '" name="' . $name . '">', 'left', '', 'colspan="2"' )
                ) . "\n";
}

?>
