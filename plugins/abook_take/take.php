<?php
  /**
   **  take.php
   **	
   **	Adds a "taken" address to the address book.  Takes addresses from
   **   incoming mail -- the body, To, From, Cc, or Reply-To.
   **/
   
   chdir('..');
   require_once('../src/validate.php');
   require_once("../functions/strings.php");
   require_once("../config/config.php");
   require_once("../functions/i18n.php");
   require_once("../functions/page_header.php");
   require_once("../functions/addressbook.php");
   require_once("../src/load_prefs.php");
   require_once('../functions/html.php');
   
   displayPageHeader($color, "None");
   
   $abook_take_verify = getPref($data_dir, $username, 'abook_take_verify');


$abook = addressbook_init(false, true);
$name = 'addaddr';
echo '<form action="../../src/addressbook.php" name="f_add" method="post">' ."\n" .
    html_tag( 'table',
        html_tag( 'tr',
            html_tag( 'th', sprintf(_("Add to %s"), $abook->localbackendname), 'center', $color[0] )
        ) ,
    'center', '', 'width="100%" cols="1"' ) .

    html_tag( 'table', '', 'center', '', 'border="0" cellpadding="1" cols="2" width="90%"' ) . "\n" .
            html_tag( 'tr', "\n" .
                html_tag( 'td', _("Nickname") . ':', 'right', $color[4], 'width="50"' ) . "\n" .
                html_tag( 'td', '<input name="' . $name . '[nickname]" size="15" value="">' .
                    '&nbsp;<small>' . _("Must be unique") . '</small>',
                'left', $color[4] )
            ) . "\n" .
            html_tag( 'tr' ) . "\n" .
            html_tag( 'td', _("E-mail address") . ':', 'right', $color[4], 'width="50"' ) . "\n" .
            html_tag( 'td', '', 'left', $color[4] ) .
                '<select name="' . $name . "[email]\">\n";
  foreach ($email as $Val)
  {
      if (valid_email($Val, $abook_take_verify))
      {
          echo '<option value="' . htmlspecialchars($Val) .
              '">' . htmlspecialchars($Val) . "</option>\n";
      }
      else
      {
          echo '<option value="' . htmlspecialchars($Val) .
	      '">FAIL - ' . htmlspecialchars($Val) . "</option>\n";
      }
  }
  echo '</select></td></tr>' . "\n" . 

  html_tag( 'tr', "\n" .
      html_tag( 'td', _("First name") . ':', 'right', $color[4], 'width="50"' ) .
      html_tag( 'td', '<input name="' . $name . '[firstname]" size="45" value="">', 'left', $color[4] )
  ) . "\n" .
  html_tag( 'tr', "\n" .
      html_tag( 'td', _("Last name") . ':', 'right', $color[4], 'width="50"' ) .
      html_tag( 'td', '<input name="' . $name . '[lastname]" size="45" value="">', 'left', $color[4] )
  ) . "\n" .
  html_tag( 'tr', "\n" .
      html_tag( 'td', _("Additional info") . ':', 'right', $color[4], 'width="50"' ) .
      html_tag( 'td', '<input name="' . $name . '[label]" size="45" value="">', 'left', $color[4] )
  ) . "\n" .
  html_tag( 'tr', "\n" .
      html_tag( 'td',
          '<input type="submit" name="' . $name . '[SUBMIT]" size="45" value="'. _("Add address") .'">' ,
      'center', $color[4], 'colspan="2"' )
  ) . "\n" .
  '</table>';
?>
</form></body>
</html>
