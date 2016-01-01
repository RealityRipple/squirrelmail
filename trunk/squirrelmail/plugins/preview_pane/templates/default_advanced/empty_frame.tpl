<?php

/**
  * empty_frame.tpl
  *
  * Template for showing a blank frame.
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


?><body>
<?php

    global $data_dir, $username;
    $use_previewPane = getPref($data_dir, $username, 'use_previewPane', 0);
    $show_preview_pane = checkForJavascript() && $use_previewPane;

// do a conditional refresh of message list if needed
// "pp_rr" = "preview pane read refresh" (this itself is irrelevant here)
// "pp_rr_force" = force pp_rr even if this is not the first time the message has been read
    if ($show_preview_pane && sqGetGlobalVar('pp_rr_force', $pp_rr_force, SQ_FORM))
        echo "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\nif (self.name == 'bottom' && typeof(parent.right.pp_refresh) != 'undefined') { parent.right.pp_refresh(); }\n// -->\n</script>\n";

