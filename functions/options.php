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
    var $refresh_level;
    var $type;

    /* The various 'values' for this options. */
    var $value;
    var $new_value;
    var $possible_vals;

    /* This variable needs to be made private so it can not be messed with. */
    /* I just don't remember how to do it right now and think it would be   */
    /* better to keep coding. Someone can fix it, if they want. Or I will.  */
    var $changed;

    function SquirrelOption
    ($name, $caption, $value, $refresh_level = SMOPT_REFRESH_NONE,
     $type = SMOPT_TYPE_STRING, $possible_values = '') {
        /* Set the basic stuff. */
        $this->name = $name;
        $this->caption = $caption;
        $this->value = $value;

        /* Set the optional parameters. */
        $this->refresh_level = $refresh_level;
        $this->type = $type;
        $this->value = $value;
        $this->possible_values = $possible_value;

        /* Lastly, check for a new value. */
        if (isset($GLOBALS["new_$name"])) {
            $this->new_value = $GLOBALS["new_$name"];
            $this->changed = ($this->value !== $this->new_value);
        }
    }

    function hasChanged() {
        return ($this->changed);
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
