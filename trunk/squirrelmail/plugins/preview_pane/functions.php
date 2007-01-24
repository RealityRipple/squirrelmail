<?php

/**
  * SquirrelMail Preview Pane Plugin
  *
  * @copyright &copy; 1999-2007 The SquirrelMail Project Team
  * @author Paul Lesneiwski <paul@squirrelmail.org>
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package plugins
  * @subpackage preview_pane
  *
  */


/**
  * Build user options for display on "Display Preferences" page
  *
  */
function preview_pane_show_options_do() 
{

   if (!checkForJavascript()) return;

   global $data_dir, $username;
   $use_previewPane = getPref($data_dir, $username, 'use_previewPane', 0);
   $previewPane_vertical_split = getPref($data_dir, $username, 'previewPane_vertical_split', 0);
   $previewPane_size = getPref($data_dir, $username, 'previewPane_size', 300);
   $pp_refresh_message_list = getPref($data_dir, $username, 'pp_refresh_message_list', 1);


   global $optpage_data;
   $optpage_data['vals'][1][] = array(
      'name'          => 'use_previewPane',
      'caption'       => _("Show Message Preview Pane"),
      'type'          => SMOPT_TYPE_BOOLEAN,
      'initial_value' => $use_previewPane,
      'refresh'       => SMOPT_REFRESH_ALL,
   );
   $optpage_data['vals'][1][] = array(
      'name'          => 'previewPane_vertical_split',
      'caption'       => _("Split Preview Pane Vertically"),
      'type'          => SMOPT_TYPE_BOOLEAN,
      'initial_value' => $previewPane_vertical_split,
      'refresh'       => SMOPT_REFRESH_ALL,
   );
   $optpage_data['vals'][1][] = array(
      'name'          => 'previewPane_size',
      'caption'       => _("Message Preview Pane Size"),
      'type'          => SMOPT_TYPE_INTEGER,
      'initial_value' => $previewPane_size,
      'refresh'       => SMOPT_REFRESH_ALL,
      'size'          => SMOPT_SIZE_TINY,
   );
   $optpage_data['vals'][1][] = array(
      'name'          => 'pp_refresh_message_list',
      'caption'       => _("Always Refresh Message List<br />When Using Preview Pane"),
      'type'          => SMOPT_TYPE_BOOLEAN,
      'initial_value' => $pp_refresh_message_list,
      'refresh'       => SMOPT_REFRESH_ALL,
   );

}


/**
  * This function determines if the preview pane is in use 
  * (and JavaScript is available)
  *
  * @return boolean TRUE if the preview pane should be showing currently.
  *
  */
function show_preview_pane() 
{
   $use_previewPane = getPref($data_dir, $username, 'use_previewPane', 0);
   return (checkForJavascript() && $use_previewPane);
}


/**
  * Construct button that clears out any preview pane
  * contents and inserts JavaScript function used by 
  * message subject link onclick handler.  Also disallows 
  * the message list to be loaded into the bottom frame.
  *
  */
function preview_pane_message_list_do()
{

   if (!checkForJavascript()) return;

   global $plugins, $archive_mail_button_has_been_printed,
          $username, $data_dir, $PHP_SELF, $base_uri;


   $output = '';
   $use_previewPane = getPref($data_dir, $username, 'use_previewPane', 0);


   // add refresh function called from code built in function
   // preview_pane_change_message_target_do()
   //
   if ($use_previewPane == 1
    && getPref($data_dir, $username, 'pp_refresh_message_list', 1) == 1)
   {
//      sqgetGlobalVar('REQUEST_URI', $request_uri, SQ_SERVER);
      $request_uri = $PHP_SELF;
      $output .= "<script type=\"text/javascript\">\n<!--\n function pp_refresh() { document.location = '$request_uri'; }\n// -->\n</script>\n";
   }


   if ($use_previewPane == 1)
   {
      $output .= '<input type="button" value="' . _("Clear Preview") 
         . '" onclick="parent.bottom.document.location=\'' . SM_PATH 
         . 'plugins/preview_pane/empty_frame.php\'" />'


      // don't let message list load into preview pane at all
      //
         . "\n<script language='javascript' type='text/javascript'>\n"
         . "<!--\n"
         . "\n"
         . "   if (self.name == 'bottom')\n"
         . "   {\n"
         . "      document.location = '" . $base_uri . "plugins/preview_pane/empty_frame.php'\n"
         . "   }\n"
         . "//-->\n"
         . "</script>\n";
   }

   return array('mailbox_index_after' => $output);

}


/**
  * Points message targets to open in the preview pane
  * (and possibly refresh message list as well)
  *
  */
function preview_pane_change_message_target_do()
{

   if (!checkForJavascript()) return;

   global $data_dir, $username, $target, $onclick, $PHP_SELF;
//   sqgetGlobalVar('REQUEST_URI', $request_uri, SQ_SERVER);
   $request_uri = $PHP_SELF;


   if (getPref($data_dir, $username, 'use_previewPane', 0) == 1)
   {
      $pp_refresh_message_list = getPref($data_dir, $username, 'pp_refresh_message_list', 1);

      $target = 'bottom';
      if ($pp_refresh_message_list)
// introduce a delay so read messages actually refresh after they are read
//         $onclick .= ' onclick="document.location=\'' . $request_uri . '\'; " ';
         $onclick .= ' setTimeout(\'pp_refresh()\', 500); ';
   }

}



