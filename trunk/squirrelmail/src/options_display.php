<?php
   /**
    **  options_display.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays all optinos about display preferences
    **
    **  $Id$
    **/

   require_once('../src/validate.php');
   require_once('../functions/display_messages.php');
   require_once('../functions/imap.php');
   require_once('../functions/array.php');
   require_once('../functions/plugin.php');
   require_once('../functions/options.php');

   displayPageHeader($color, 'None');
   $chosen_language = getPref($data_dir, $username, 'language');
?>
   <br>
<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">
<tr><td bgcolor="<?php echo $color[0] ?>" align="center">

   <b><?php echo _("Options") . ' - ' . _("Display Preferences"); ?></b><br>

   <table width="100%" border="0" cellpadding="1" cellspacing="1">
   <tr><td bgcolor="<?php echo $color[4] ?>" align="center">

   <form name="f" action="options.php" method="post"><br>
      <table width="100%" cellpadding="2" cellspacing="0" border="0">
<?php
    OptionSelect( _("Theme"), 'chosentheme', $theme, $chosen_theme, 'NAME', 'PATH' );
    OptionSelect( _("Language"), 'language', $languages, $chosen_language, 'NAME' );
    OptionRadio( _("Use Javascript or HTML addressbook?"),
                 'javascript_abook',
                 array( '1' => _("JavaScript"),
                        '0' => _("HTML") ),
                 $use_javascript_addr_book );

    /*** BEGIN OPTIONS CLASS EXPERMINENTATION ***/

    /* Build a simple array with which to start. */
    $optvals = array();
 

    /* Set values for the "use javascript" option. */
    $optvals[] = array(
        'name'    => 'javascript_setting',
        'caption' =>_("Use Javascript"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => array(SMPREF_JS_AUTODETECT => _("Autodetect"),
                           SMPREF_JS_ON         => _("Always"),
                           SMPREF_JS_OFF        => _("Never"))
    );

    $js_autodetect_results = SMPREF_JS_OFF;
    $optvals[] = array(
        'name'    => 'js_autodetect_results',
        'caption' => '',
        'type'    => SMOPT_TYPE_HIDDEN,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[] = array(
        'name'    => 'show_num',
        'caption' => _("Number of Messages to Index"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[] = array(
        'name'    => 'wrap_at',
        'caption' => _("Wrap Incoming Text At"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE
    );

    $optvals[] = array(
        'name'    => 'editor_size',
        'caption' => _("Size of Editor Window"),
        'type'    => SMOPT_TYPE_INTEGER,
        'refresh' => SMOPT_REFRESH_NONE
    );

    OptionSelect( _("Location of buttons when composing"),
                  'button_new_location',
                  array( 'top' => _("Before headers"),
                         'between' => _("Between headers and message body"),
                         'bottom' => _("After message body") ),
                  $location_of_buttons );

    $optvals[] = array(
        'name'    => 'location_of_buttons',
        'caption' =>_("Location of Buttons when Composing"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_NONE,
        'posvals' => array(SMPREF_LOC_TOP      => _("Before headers"),
                           SMPREF_LOC_BETWEEN  => _("Between headers and message body"),
                           SMPREF_LOC_BOTTOM   => _("After message body"))
    );

    $optvals[] = array(
        'name'    => 'location_of_bar',
        'caption' =>_("Location of Folder List"),
        'type'    => SMOPT_TYPE_STRLIST,
        'refresh' => SMOPT_REFRESH_ALL,
        'posvals' => array(SMPREF_LOC_LEFT   => _("Left"),
                           SMPREF_LOC_RIGHT  => _("Right"))
    );

    /* Now, build the complete options array. */
    $options = createOptionArray($optvals);

    /* Print the row for each option. */
    foreach ($options as $option) {
        if ($option->type != SMOPT_TYPE_HIDDEN) {
            echo "<TR>\n";
            echo '  <TD ALIGN="RIGHT" VALIGN="MIDDLE" NOWRAP><font color=red><b>'
               . $option->caption . "</b></font>:</TD>\n";
            echo '  <TD>' . $option->createHTMLWidget() . "</TD>\n";
            echo "</TR>\n";
        } else {
            echo $option->createHTMLWidget();
        }
    }

    /*** END OPTIONS CLASS EXPERMINENTATION ***/

   for ($i = 100; $i <= 300; $i += 10) {
        $res[$i] = $i . _("pixels");
   }
   OptionSelect( _("Width of folder list"),
                 'leftsize',
                 $res,
                 $left_size );
   $minutes_str = _("Minutes");
   OptionSelect( _("Auto refresh folder list"),
                 'leftrefresh',
                 array( 'None' => _("Never"),
                        30 => '30 '. _("Seconds"),
                        60 => '1 ' . _("Minute"),
                        120 => "2 $minutes_str",
                        180 => "3 $minutes_str",
                        300 => "5 $minutes_str",
                        600 => "10 $minutes_str" ),
                 $left_refresh );
   OptionRadio( _("Use alternating row colors?"),
                'altIndexColors',
                array( 1 => _("Yes"),
                       0 => _("No") ),
                $alt_index_colors );
   OptionCheck( _("Show HTML version by default"),
                'showhtmldefault',
                $show_html_default,
                _("Yes, show me the HTML version of a mail message, if it is available.") );
   OptionCheck( _("Include Self"),
                'includeselfreplyall',
                getPref($data_dir, $username, 'include_self_reply_all', FALSE ),
                _("Don't remove me from the CC addresses when I use \"Reply All\"") );
   $psw = getPref($data_dir, $username, 'page_selector_max', 10 );
   OptionCheck( _("Page Selector"),
                'pageselector',
                !getPref($data_dir, $username, 'page_selector', FALSE ),
                _("Show page selector") .
                " <input name=pageselectormax size=3  value=\"$psw\"> &nbsp;" .
                _("pages max") );

   echo '<tr><td colspan=2><hr noshade></td></tr>';
   do_hook('options_display_inside');
   OptionSubmit( 'submit_display' );
?>

      </table>
   </form>

   <?php do_hook('options_display_bottom'); ?>

    </td></tr>
    </table>

<SCRIPT LANGUAGE="JavaScript"><!--
  document.forms[0].new_js_autodetect_results.value = '<?php echo SMPREF_JS_ON; ?>';
// --></SCRIPT>

</td></tr>
</table>
</body></html>
