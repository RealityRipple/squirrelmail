<?php

/**
 * page_header.tpl
 *
 * Template to create the header for each page.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: page_header.tpl 12135 2007-01-15 08:27:10Z pdontthink $
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

$compose_link	= makeComposeLink ('src/compose.php?mailbox='.$urlMailbox.'&amp;startMessage='.$startMessage, $compose_str);
$signout_link	= makeInternalLink ('src/signout.php', $signout_str, $frame_top);
$address_link	= makeInternalLink ('src/addressbook.php', $address_str);
$folders_link	= makeInternalLink ('src/folders.php', $folders_str);
$search_link	= makeInternalLink ('src/search.php?mailbox='.$urlMailbox, $search_str);
$options_link	= makeInternalLink ('src/options.php', $options_str);
$help_link		= makeInternalLink ('src/help.php', $help_str);

?>
<body <?php echo $body_tag_js; ?>>
<?php

   /** if preview pane turned on, do not show menubar above message or compose view */
   global $data_dir, $username, $PHP_SELF, $pp_skip_menubar;
   $use_previewPane = getPref($data_dir, $username, 'use_previewPane', 0);
   $show_preview_pane = checkForJavascript() && $use_previewPane;
   $current_page_is_read_body = (strpos($PHP_SELF, '/read_body.php') !== FALSE);
   if (!$pp_skip_menubar && (!$current_page_is_read_body || !$show_preview_pane)) {

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
  <td class="sqm_topNavigation"<?php echo ($hide_sm_attributions ? ' colspan="2"' : ''); ?>>
   <?php echo $compose_link; ?>&nbsp;&nbsp;
   <?php echo $address_link; ?>&nbsp;&nbsp;
   <?php echo $folders_link; ?>&nbsp;&nbsp;
   <?php echo $options_link; ?>&nbsp;&nbsp;
   <?php echo $search_link; ?>&nbsp;&nbsp;
   <?php echo $help_link; ?>&nbsp;&nbsp;
   <?php /* FIXME: no hooks in templates!! */ global $null; do_hook('menuline', $null); ?>
  </td>
  <?php if (!empty($sm_attribute_str))
            echo '<td class="sqm_providerInfo">'
               . $sm_attribute_str
               . "</td>\n"; ?>
 </tr>
</table>
</div>
<br />
<!-- End Header Navigation Table -->
<script type="text/javascript">
<!--
   var delayed_page_load_uri = '';
   function delayed_page_load(page_uri)
   { page_load_uri = page_uri; setTimeout('page_load()', 500); }
   function page_load()
   { document.location = page_load_uri; }
// -->
</script>
<?php }
