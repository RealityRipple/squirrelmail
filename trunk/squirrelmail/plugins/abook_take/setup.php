<?php

   require_once('../functions/url_parser.php');


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
    
  ?>
  <FORM ACTION="../plugins/abook_take/take.php" METHOD=POST>
  <table align="<?PHP 
      echo $abook_take_location;
  ?>" cellpadding=3 cellspacing=0 border=0 bgcolor="<?PHP 
      echo $color[10] 
  ?>">
    <tr>
      <td>
        <table cellpadding=2 cellspacing=1 border=0 bgcolor="<?PHP 
            echo $color[5] 
        ?>">
          <tr>
            <td>
            <?PHP
              abook_take_read_string($message->header->from);
              abook_take_read_array($message->header->cc);
              abook_take_read_array($message->header->reply_to);
              abook_take_read_array($message->header->to);


              $new_body = $body;
              $pos = strpos($new_body, 
                '">Download this as a file</A></CENTER><BR></SMALL>');
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
            ?>
              <INPUT TYPE="submit" VALUE="Take Address">
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
  
  ?><tr><td align=right nowrap valign="top">Address Book Take:</td>
    <td><select name="abook_take_abook_take_location">
    <option value="left"<?PHP
      if ($abook_take_location == 'left')
        echo ' SELECTED';
    ?>>Left aligned</option>
    <option value="center"<?PHP
      if ($abook_take_location == 'center')
        echo ' SELECTED';
    ?>>Centered</option>
    <option value="right"<?PHP
      if ($abook_take_location == 'right')
        echo ' SELECTED';
    ?>>Right aligned</option>
  </select> on the Read screen<br>
  <input type=checkbox name="abook_take_abook_take_hide"<?PHP
      if ($abook_take_hide)
        echo ' CHECKED';
    ?>> Hide the box<br>
  <input type=checkbox name="abook_take_abook_take_verify"<?PHP
      if ($abook_take_verify)
        echo ' CHECKED';
    ?>> Try to verify addresses
  </td></tr><?PHP
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
