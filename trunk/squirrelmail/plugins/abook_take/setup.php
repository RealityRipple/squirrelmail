<?php

/* Path for SquirrelMail required files. */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/url_parser.php');

/* Address Take -- steals addresses from incoming email messages.  Searches
   the To, Cc, From and Reply-To headers, also searches the body of the
   message.  */

function squirrelmail_plugin_init_abook_take()
{
  global $squirrelmail_plugin_hooks;
  
  $squirrelmail_plugin_hooks['read_body_bottom']['abook_take'] = 'abook_take_read';
  $squirrelmail_plugin_hooks['loading_prefs']['abook_take'] = 'abook_take_pref';
  $squirrelmail_plugin_hooks['options_display_inside']['abook_take'] = 'abook_take_options';
  $squirrelmail_plugin_hooks['options_display_save']['abook_take'] = 'abook_take_save';
}


function valid_email ($email, $verify)
{
  global $abook_take_verify, $Email_RegExp_Match;
  
  if (! eregi('^' . $Email_RegExp_Match . '$', $email))
    return false;
    
  if (! $verify)
    return true;

  if (! checkdnsrr(substr(strstr($email, '@'), 1), 'ANY'))
    return false;

  return true;
}


function abook_take_read_string($str)
{
  global $abook_found_email, $Email_RegExp_Match;
  

  while (eregi('(' . $Email_RegExp_Match . ')', $str, $hits))
  {
      $str = substr(strstr($str, $hits[0]), strlen($hits[0]));
      if (! isset($abook_found_email[$hits[0]]))
      {
          echo "<input type=\"hidden\" name=\"email[]\" value=\"$hits[0]\">\n";
	  $abook_found_email[$hits[0]] = 1;
      }
  }
  return;
}


function abook_take_read_array($array)
{
  $i = 0;
  while ($i < count($array))
  {
    abook_take_read_string($array[$i]);
    $i ++;
  }
}


function abook_take_read()
{
  global $color, $abook_take_location;
  global $body, $abook_take_hide, $message, $imapConnection;

  if ($abook_take_hide)
    return;
    
  echo '<form action="../plugins/abook_take/take.php" method="post">' . "\n" .
  html_tag( 'table', '', $abook_take_location, $color[10], 'cellpadding="3" cellspacing="0" border="0"' ) .
      html_tag( 'tr' ) .
          html_tag( 'td', '', 'left' ) .
              html_tag( 'table', '', '', $color[5], 'cellpadding="2" cellspacing="1" border="0"' ) .
                  html_tag( 'tr' ) .
                      html_tag( 'td' );

              abook_take_read_string($message->header->from);
              abook_take_read_array($message->header->cc);
              abook_take_read_array($message->header->reply_to);
              abook_take_read_array($message->header->to);


              $new_body = $body;
              $pos = strpos($new_body, 
                '">' . _("Download this as a file") . '</a></center><br></small>');
              if (is_int($pos))
              {
                $new_body = substr($new_body, 0, $pos);
              }
                     
              $trans = get_html_translation_table(HTML_ENTITIES);
              $trans[' '] = '&nbsp;';
              $trans = array_flip($trans);
              $new_body = strtr($new_body, $trans);

              $new_body = urldecode($new_body);
              $new_body = strip_tags($new_body);
              
              $new_body = strtr($new_body, "\n", ' ');
    
              abook_take_read_string($body);
            
              echo '<input type="submit" value="' . _("Take Address") . '">';
            ?>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  </form>
  <?PHP
}

function abook_take_pref()
{ 
  global $username, $data_dir;
  global $abook_take_hide, $abook_take_location, $abook_take_verify;

  $abook_take_location = getPref($data_dir, $username, 'abook_take_location');
  if ($abook_take_location == '')
    $abook_take_location = 'center';
    
  $abook_take_hide = getPref($data_dir, $username, 'abook_take_hide');
  $abook_take_verify = getPref($data_dir, $username, 'abook_take_verify');
}


function abook_take_options()
{
  global $abook_take_location, $abook_take_hide, $abook_take_verify;
  
  echo html_tag( 'tr' ) .
      html_tag( 'td', _("Address Book Take") . ':', 'right', '', 'nowrap valign="top"' ) .
      html_tag( 'td', '', 'left' ) .
          '<select name="abook_take_abook_take_location">' .
          '<option value="left"';
          if ($abook_take_location == 'left')
            echo ' selected';
          echo '>' . _("Left aligned") . '</option>' .
          '<option value="center"';
          if ($abook_take_location == 'center')
            echo ' selected';
          echo '>' . _("Centered") . '</option>' .
          '<option value="right"';
          if ($abook_take_location == 'right')
            echo ' selected';
          echo '>' . _("Right aligned") . '</option>' .
          '</select> ' . _("on the Read screen") .'<br>' .
          '<input type="checkbox" name="abook_take_abook_take_hide"';
          if ($abook_take_hide)
            echo ' checked';
          echo '>&nbsp;' . _("Hide the box") . '<br>' .
          '<input type=checkbox name="abook_take_abook_take_verify"';
          if ($abook_take_verify)
            echo ' checked';
          echo '>&nbsp;' . _("Try to verify addresses") . '</td></tr>';
}


function abook_take_save()
{
  global $username, $data_dir;
  global $abook_take_abook_take_location;
  global $abook_take_abook_take_hide;
  global $abook_take_abook_take_verify;
  
  
  if (isset($abook_take_abook_take_location)) 
  {
    setPref($data_dir, $username, 'abook_take_location', $abook_take_abook_take_location);
  } 
  else 
  {
    setPref($data_dir, $username, 'abook_take_location', 'center');
  }

  if (isset($abook_take_abook_take_hide)) 
  {
    setPref($data_dir, $username, 'abook_take_hide', '1');
  } 
  else 
  {
    setPref($data_dir, $username, 'abook_take_hide', '');
  }

  if (isset($abook_take_abook_take_verify)) 
  {
    setPref($data_dir, $username, 'abook_take_verify', '1');
  } 
  else 
  {
    setPref($data_dir, $username, 'abook_take_verify', '');
  }
}

?>
