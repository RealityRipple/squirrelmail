<?php
    /**
     * options.php
     *
     * Copyright (c) 1999-2001 The Squirrelmail Development Team
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
define('SMOPT_TYPE_TEXTAREA', 2);
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

    /* The various 'values' for this options. */
    var $value;
    var $new_value;
    var $possible_values;

    /* This variable needs to be made private so it can not be messed with. */
    /* I just don't remember how to do it right now and think it would be   */
    /* better to keep coding. Someone can fix it, if they want. Or I will.  */
    var $changed;

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

        /* Check for a current value. */
        if (isset($GLOBALS[$name])) {
            $this->value = $GLOBALS[$name];
        } else {
            $this->value = '';
        }

        /* Check for a new value. */
        if (isset($GLOBALS["new_$name"])) {
            $this->new_value = $GLOBALS["new_$name"];
            $this->changed = ($this->value !== $this->new_value);
        } else {
            $this->new_value = '';
            $this->changed = false;
        }
    }

    /* Set the size for this option. */
    function setSize($size) {
        $this->size = $size;
    }

    /* Set the comment for this option. */
    function setComment($comment) {
        $this->comment = $comment;
    }

    function createHTMLWidget() {
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
            default:
               $result = '<FONT COLOR=RED>'
                       . sprintf(_("Option Type '%s' Not Found"), $this->type)
                       . '</FONT>';
        }

        /* Now, return the created widget. */
        return ($result);
    }

    function createWidget_String() {
        switch ($this->size) {
            case SMOPT_SIZE_TINY:  $width = 5; break;
            case SMOPT_SIZE_SMALL: $width = 12; break;
            case SMOPT_SIZE_LARGE: $width = 38; break;
            case SMOPT_SIZE_HUGE:  $width = 50; break;
            case SMOPT_SIZE_NORMAL:
            default: $width = 25;
        }

        $result = "<INPUT NAME=\"new_$this->name\" VALUE=\"$this->value\" SIZE=\"$width\">";
        return ($result);
    }

    function createWidget_StrList() {
        /* Begin the select tag. */
        $result = "<SELECT NAME=\"new_$this->name\">";

        /* Add each possible value to the select list. */
        foreach ($this->possible_values as $real_value => $disp_value) {
            /* Start the next new option string. */
            $new_option = "<OPTION VALUE=\"$real_value\"";

            /* If this value is the current value, select it. */
            if ($real_value == $this->value) {
               $new_option .= ' SELECTED';
            }

            /* Add the display value to our option string. */
            $new_option .= ">$disp_value</OPTION>";

            /* And add the new option string to our select tag. */
            $result .= $new_option;
        }

        /* Close the select tag and return our happy result. */
        $result .= '</SELECT>';
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
        $result = "<TEXTAREA NAME=\"new_$this->name\" ROWS=\"$rows\" "
                . "COLS=\"$cols\">$this->value</TEXTAREA>";
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
            $yes_chk = ' CHECKED';
            $no_chk = '';
        } else {
            $yes_chk = '';
            $no_chk = ' CHECKED';
        }

        /* Build the yes choice. */
        $yes_option = '<INPUT TYPE="RADIO" NAME="new_' . $this->name
                    . '" VALUE="' . SMPREF_YES . "\"$yes_chk>&nbsp;"
                    . _("Yes");

        /* Build the no choice. */
        $no_option = '<INPUT TYPE="RADIO" NAME="new_' . $this->name
                   . '" VALUE="' . SMPREF_NO . "\"$no_chk>&nbsp;"
                   . _("No");

        /* Build and return the combined "boolean widget". */
        $result = "$yes_option&nbsp;&nbsp;&nbsp;&nbsp;$no_option";
        return ($result);
    }

    function createWidget_Hidden() {
        $result = '<INPUT TYPE="HIDDEN" NAME="new_' . $this->name
                . '" VALUE="' . $this->value . '">';
        return ($result);
    }

    function createWidget_Comment() {
        $result = $this->comment;
        return ($result);
    }

    function hasChanged() {
        return ($this->changed);
    }
}

function createOptionGroups($optgrps, $optvals) {
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

            /* Add this option to the option array. */
            $result[$grpkey]['options'][] = $next_option;
        }
    }

    /* Return our resulting array. */
    return ($result);
}

function printOptionGroups($option_groups) {
    foreach ($option_groups as $next_optgrp) {
        echo '<TR><TD ALIGN="CENTER" VALIGN="MIDDLE" COLSPAN="2" NOWRAP><B>'
           . $next_optgrp['name'] . "</B></TD></TR>\n";
        foreach ($next_optgrp['options'] as $option) {
            if ($option->type != SMOPT_TYPE_HIDDEN) {
                echo "<TR>\n";
                echo '  <TD ALIGN="RIGHT" VALIGN="MIDDLE">'
                   . $option->caption . ":</TD>\n";
                echo '  <TD>' . $option->createHTMLWidget() . "</TD>\n";
                echo "</TR>\n";
            } else {
                echo $option->createHTMLWidget();
            }
        }
        echo "<TR><TD COLSPAN=\"2\">&nbsp;</TD></TR>\n";
    }
}

function OptionSelect( $title, $name, $data, $default, $show = '', $store = '' ) {

    echo "<tr><td align=right valign=middle nowrap>$title: </td><td>" .
         "<select name=\"$name\">";
    foreach( $data as $key => $opt ) {
        if ( $store == '' ) {
            $vl = $key;
        } else{
            $vl = $opt[$store];
        }
        if ( $show == '' ) {
            $nm = $opt;
        } else{
            $nm = $opt[$show];
        }
        if ( $nm <> '') {
            echo "<option value=\"$vl\"";
            if( $vl == $default ) {
                echo ' selected';
            }
            echo ">$nm</option>\n";
        }
    }
    echo "</select></td></tr>\n";
}

function OptionRadio( $title, $name, $data, $default, $show = '', $store = '', $sep = '&nbsp; &nbsp;'  ) {
    echo "<tr><td align=right valign=middle nowrap>$title: </td><td>";
    foreach( $data as $key => $opt ) {
        if ( $store == '' ) {
            $vl = $key;
        } else{
            $vl = $opt[$store];
        }
        if ( $show == '' ) {
            $nm = $opt;
        } else{
            $nm = $opt[$show];
        }
        if ( $nm <> '') {
            echo "<input type=\"radio\" name=\"$name\" value=\"$vl\"";
            if( $vl == $default ) {
                echo ' checked';
            }
            echo ">$nm $sep\n";
        }
    }
    echo "</td></tr>\n";
}

function OptionText( $title, $name, $value, $size ) {
    echo "<tr><td align=right valign=middle nowrap>$title: </td><td>" .
         "<input name=\"$name\" value=\"$value\" size=\"$size\">" .
         "</td></tr>\n";
}

function OptionHidden( $name, $value ) {
    echo "<INPUT TYPE=HIDDEN NAME=\"$name\" VALUE=\"$value\">\n";
}

function OptionCheck( $title, $name, $value, $comment ) {
    if ( $value )
        $chk = 'checked';
    echo "<tr><td align=right valign=middle nowrap>$title: </td><td>" .
         "<input type=\"checkbox\" name=\"$name\" $chk> $comment" .
         "</td></tr>\n";
}

function OptionTitle( $title ) {
    echo "<tr><td colspan=2 align=left valign=middle nowrap><b>$title</b></td></tr>\n";
}

function OptionSubmit( $name ) {
    echo '<tr><td>&nbsp;</td><td><input type="submit" value="' . _("Submit") . '" name="' . $name . '">' .
         '</td></tr>';
}

?>
