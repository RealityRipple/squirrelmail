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
if ( $shortBoxName != '' ) {
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
