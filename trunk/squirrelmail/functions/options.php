<?php
   /**
    **  options.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Functions needed to display the options pages.
    **
    **  $Id$
    **/

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
