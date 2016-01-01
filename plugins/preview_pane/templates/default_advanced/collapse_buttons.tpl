<?php

/**
  * collapse_buttons.tpl
  *
  * Template for building (un)collapse (and clear) buttons for preview pane.
  *
  * The following variables are available in this template:
  *    + $orientation      - Either "cols" or "rows" depending on how the 
  *                          preview pane is oriented
  *    + $down_arrow       - The value to be displayed on the collapse button
  *    + $up_arrow         - The value to be displayed on the uncollapse button
  *    + $base_uri         - The SquirrelMail base URI
  *    + $previewPane_size - The user's configured size of the preview pane
  *
  * @copyright 1999-2016 The SquirrelMail Project Team
  * @author Paul Lesniewski <paul@squirrelmail.org>
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package plugins
  * @subpackage preview_pane
  *
  */


// retrieve the template vars
//
extract($t);


?><script type="text/javascript" language="JavaScript">
<!--

   function set_preview_pane_size(new_size)
   {
      if (document.all)
      {
         parent.document.all["fs2"].<?php echo $orientation; ?> = "*, " + new_size;
      }
      else if (this.document.getElementById)
      {
         parent.document.getElementById("fs2").<?php echo $orientation; ?> = "*, " + new_size;
      }
   }
// -->\n</script>
<form style="margin:0" action="">
   <input type="button" value="<?php echo $down_arrow; ?>" onclick="set_preview_pane_size(0)" />
   <input type="button" value="X" onclick="parent.bottom.document.location='<?php echo $base_uri; ?>plugins/preview_pane/empty_frame.php'" />
   <input type="button" value="<?php echo $up_arrow; ?>" onclick="set_preview_pane_size(<?php echo $previewPane_size; ?>)" />
</form>
