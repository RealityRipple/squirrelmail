<?php

/**
 * page_header.tpl
 *
 * Template to create the header for each page.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/* retrieve the template vars */
extract($t);


$current_folder_str = '';
if ( $shortBoxName <> '' && strtolower( $shortBoxName ) <> 'none' ) {
    $current_folder_str .= _("Current Folder") . ": <em>$shortBoxName&nbsp;</em>\n";
} else {
    $current_folder_str .= '&nbsp;';
}

// Define our default link text.
$signout_link_default = _("Sign Out");
$compose_link_default = _("Compose");
$address_link_default = _("Addresses");
$folders_link_default = _("Folders");
$options_link_default = _("Options");
$search_link_default = _("Search");
$help_link_default = _("Help");

/*
 * Create strings to use for links.  If tempalte authors
 * wish to use images instead, they may change the values
 * below to img tags.

 * Example w/ image:
 * $compose_str = '<img src="compose.png" border="0" ' .
 *				  'alt="'.$compose_link_default.'" ' .
 *				  'title="'.$compose_link_default.'" />';
 */

$signout_str = $signout_link_default;
$compose_str = $compose_link_default;
$address_str = $address_link_default;
$folders_str = $folders_link_default;
$options_str = $options_link_default;
$search_str = $search_link_default;
$help_str = $help_link_default;

$compose_link = makeComposeLink('src/compose.php?mailbox=' . $urlMailbox
                                . '&amp;startMessage=' . $startMessage,
                                $compose_str, '', $accesskey_menubar_compose);
$signout_link = makeInternalLink('src/signout.php', $signout_str, $frame_top,
                                 $accesskey_menubar_signout);
$address_link = makeInternalLink('src/addressbook.php', $address_str, '',
                                 $accesskey_menubar_addresses);
$folders_link = makeInternalLink('src/folders.php', $folders_str, '',
                                 $accesskey_menubar_folders);
$search_link  = makeInternalLink('src/search.php?mailbox='.$urlMailbox,
                                 $search_str, '', $accesskey_menubar_search);
$options_link = makeInternalLink('src/options.php', $options_str, '',
                                 $accesskey_menubar_options);
$help_link    = makeInternalLink('src/help.php', $help_str, '',
                                 $accesskey_menubar_help);

?>
<body <?php if (!empty($onload)) echo ' onload="' . $onload . '"'; ?>>
<?php

   /** if preview pane turned on, do not show menubar above message */
   global $data_dir, $username, $pp_skip_menubar;
   $use_previewPane = getPref($data_dir, $username, 'use_previewPane', 0);
   $show_preview_pane = checkForJavascript() && $use_previewPane;
   $current_page_is_read_body = (defined('PAGE_NAME') && 'PAGE_NAME' == 'read_body');
   if (!$pp_skip_menubar && (!$current_page_is_read_body || !$show_preview_pane)) {
// Note: If we want to hide menubar on compose screen, we could do the following, 
// but the compose screen when in the message list frame should not have the menubar
// hidden... which would require some javascript to detect the current frame name
// and then use document.write() to output the menubar if needed... and we'll
// leave that for some other time
//-   if (!$pp_skip_menubar && (!$current_page_is_read_body || !$show_preview_pane)) {
//+   $current_page_is_compose = (defined('PAGE_NAME') && 'PAGE_NAME' == 'compose');
//+   if (!$pp_skip_menubar 
//+    && ((!$current_page_is_read_body && !$current_page_is_compose) 
//+        || !$show_preview_pane)) {

?>
<div id="page_header">
<a name="pagetop"></a>
<?php if (!empty($plugin_output['page_header_top'])) echo $plugin_output['page_header_top']; ?>
<!-- Begin Header Navigation Table -->
<table class="table_empty" cellspacing="0">
 <tr>
  <td class="sqm_currentFolder">
   <?php echo $current_folder_str; ?>
  </td>
  <td class="sqm_headerSignout">
   <?php echo $signout_link; ?>
  </td>
 </tr>
 <tr>
  <td class="sqm_topNavigation">
   <?php echo $compose_link; ?>&nbsp;&nbsp;
   <?php echo $address_link; ?>&nbsp;&nbsp;
   <?php echo $folders_link; ?>&nbsp;&nbsp;
   <?php echo $options_link; ?>&nbsp;&nbsp;
   <?php echo $search_link; ?>&nbsp;&nbsp;
   <?php echo $help_link; ?>&nbsp;&nbsp;
   <?php if (!empty($plugin_output['menuline'])) echo $plugin_output['menuline']; ?>
  </td>
  <td class="sqm_providerInfo">
    <?php 
        if (!empty($plugin_output['provider_link_before'])) 
            echo $plugin_output['provider_link_before'];
        if (!empty($provider_link)) 
            echo $provider_link; 
        if (!empty($plugin_output['provider_link_after'])) 
            echo $plugin_output['provider_link_after'];
    ?>
  </td>
 </tr>
</table>
</div>
<br />
<!-- End Header Navigation Table -->
<?php 
   } 

   // now, some generic javascript for use with preview pane

?>
<script type="text/javascript" language="JavaScript">
<!--
   var delayed_page_load_uri = '';
   function delayed_page_load(page_uri)
   { delayed_page_load_uri = page_uri; setTimeout('page_load()', 500); }
   function page_load()
   { document.location = delayed_page_load_uri; }
   function refresh_message_list()
   {
<?php if (!empty($message_list_href)) { ?>
      if (typeof(parent.right.delayed_page_load) != 'undefined')
         parent.right.delayed_page_load("<?php echo $message_list_href; ?>");
<?php } ?>
   }
<?php 

   // autohide the preview pane if desired when not viewing messages,
   // also maximize any frame with a compose screen in it
   if ($show_preview_pane 
    && getPref($data_dir, $username, 'previewPane_autohide', 0) == 1) 
   {
      $previewPane_vertical_split = getPref($data_dir, $username, 'previewPane_vertical_split', 0);
      if ($previewPane_vertical_split)
         $orientation = 'cols';
      else
         $orientation = 'rows';
      
      // spit out javascript to maximize compose frame
      if (defined('PAGE_NAME') && PAGE_NAME=='compose')
      {
?>
   var first_frame = 0;
   var second_frame = 0;
   if (self.name == 'right')
   {
      first_frame = '100%';
      second_frame = '*';
   }
   else if (self.name == 'bottom')
   {
      first_frame = '*';
      second_frame = '100%';
   }
   if (first_frame != 0)
   {
      if (document.all)
      {
         parent.document.all["fs2"].<?php echo $orientation; ?> = first_frame + ", " + second_frame;
      }
      else if (this.document.getElementById)
      {
         parent.document.getElementById("fs2").<?php echo $orientation; ?> = first_frame + ", " + second_frame;
      }
   }
<?php 

    // not on the compose screen, either hide or restore preview pane
    } else {

        $previewPane_size = getPref($data_dir, $username, 'previewPane_size', 300);
        if (defined('PAGE_NAME') && (PAGE_NAME=='right_main'||PAGE_NAME=='search')) {
            $new_size = $previewPane_size;
        } else {
            $new_size = 0;
        }
?>
   if (self.name == 'right')
   {
      if (document.all)
      {
         parent.document.all["fs2"].<?php echo $orientation; ?> = "*, " + <?php echo $new_size; ?>;
      }
      else if (this.document.getElementById)
      {
         parent.document.getElementById("fs2").<?php echo $orientation; ?> = "*, " + <?php echo $new_size; ?>;
      }
   }

   // restores the preview pane if it sucked up the whole page for composing a message
   else if (self.name == 'bottom')
   {
      if (document.all)
      {
         if (parent.document.all["fs2"].<?php echo $orientation; ?> == "*, 100%")
            parent.document.all["fs2"].<?php echo $orientation; ?> = "*, " + <?php echo $previewPane_size; ?>;
      }
      else if (this.document.getElementById)
      {
         if (parent.document.getElementById("fs2").<?php echo $orientation; ?> == "*, 100%")
            parent.document.getElementById("fs2").<?php echo $orientation; ?> = "*, " + <?php echo $previewPane_size; ?>;
      }
   }
<?php } } ?>
// -->
</script>
