#!/usr/bin/perl
# conf.pl
# Written March 26, 2000
# Luke Ehresman (lehresma@css.tayloru.edu)
#
# A simple configure script to configure squirrelmail
############################################################              
$WHT = "\x1B[37;1m";
$NRM = "\x1B[0m";

print "\n\n--------------------------------------------------------\n";
print "SquirrelMail version 0.4 -- Configure script\n";
print "by SquirrelMail Development Team\n";
print "http://squirrelmail.sourceforge.net\n";
print "--------------------------------------------------------\n";
print "\n";
 
############################################################              
# First, lets read in the data already in there...
############################################################              
if ( -e "config.php") {
   print "The file \"config.php\" exists.  Using it for defaults.\n\n";
   open (FILE, "config.php");
} else {
   print "No config file found.  Reading from config_defaults.php.\n\n";
   open (FILE, "config_default.php");
}

#  Reads and parses the current configuration file (either
#  config.php or config_default.php).

while ($line = <FILE>) {
   if ($line =~ /^\s+\$/) {
      $line =~ s/^\s+\$//;
      $var = $line;
      
      if ($var =~ /^([a-z]|[A-Z])/) {
         @options = split(/\s=\s/, $var);
         $options[1] =~ s/[\n|\r]//g;
         $options[1] =~ s/^"//g;
         $options[1] =~ s/;.*$//g;
         $options[1] =~ s/"$//g;

         if ($options[0] =~ /^special_folders/) {
            if ($options[0] =~ /\[.*\]$/) {
               $sub = $options[0];
               $sub =~ s/\]$//;
               $sub = substr ($sub, @sub-1, 1);

               $special_folders[$sub] = $options[1];
            }   
         } elsif ($options[0] =~ /^theme\[[0-9]+\]\["PATH"\]/) {
            $sub = $options[0];
            $sub =~ s/\]\["PATH"\]//;
            $sub = substr ($sub, @sub-1, 1);
            $theme_path[$sub] = $options[1];
         } elsif ($options[0] =~ /^theme\[[0-9]+\]\["NAME"\]/) {
            $sub = $options[0];
            $sub =~ s/\]\["NAME"\]//;
            $sub = substr ($sub, @sub-1, 1);
            $theme_name[$sub] = $options[1];
         } else {
            ${$options[0]} = $options[1];
         }   
      }   
   }
}

open (FILE, ">cf.php");
print FILE "<?\n\t/** SquirrelMail configure script\n";
print FILE "	 ** Created using the configure script, config.pl.\n\t **/\n\n";
        
print "\n-------------------------------------------------.\n";
print "              General Information                 )\n";
print "-------------------------------------------------'\n";
############################################################              
# Organization Name
############################################################              
print $WHT."What is the name of your organization [$org_name]: $NRM";
$new_org_name = <STDIN>;
if ($new_org_name eq "\n") {
   $new_org_name = $org_name;
} else {
   $new_org_name =~ s/[\r|\n]//g;
}
print FILE "	".'$org_name = "' . $new_org_name . "\";\n";
            
             
              
############################################################              
# Organization Logo 
############################################################              
print $WHT."Where is the logo for your organization [$org_logo]: $NRM";
$new_org_logo = <STDIN>;
if ($new_org_logo eq "\n") {
   $new_org_logo = $org_logo;
} else {
   $new_org_logo =~ s/[\r|\n]//g;
}
print FILE "	".'$org_logo = "' . $new_org_logo . "\";\n";
            



############################################################              
# Organization Title 
############################################################              
print "The title of the web page [$org_title]: ";
$new_org_title = <STDIN>;
if ($new_org_title eq "\n") {
   $new_org_title = $org_title;
} else {
   $new_org_title =~ s/[\r|\n]//g;
}
print FILE "	".'$org_title = "' . $new_org_title . "\";\n";
            


print "\n-------------------------------------------------.\n";
print "              Server Information                  )\n";
print "-------------------------------------------------'\n";
############################################################              
# IMAP Server 
############################################################              
print "Where is the IMAP server [$imapServerAddress]: ";
$new_imapServerAddress = <STDIN>;
if ($new_imapServerAddress eq "\n") {
   $new_imapServerAddress = $imapServerAddress;
} else {
   $new_imapServerAddress =~ s/[\r|\n]//g;
}
print FILE "	".'$imapServerAddress = "' . $new_imapServerAddress . "\";\n";
            


############################################################              
# IMAP Port 
############################################################              
print "The port for your IMAP server [$imapPort]: ";
$new_imapPort = <STDIN>;
if ($new_imapPort eq "\n") {
   $new_imapPort = $imapPort;
} else {
   $new_imapPort =~ s/[\r|\n]//g;
}
print FILE "	".'$imapPort = ' . $new_imapPort . ";\n";
            


############################################################              
# DOMAIN
############################################################              
print "What is your domain name (ex: usa.om.org) [$domain]: ";
$new_domain = <STDIN>;
if ($new_domain eq "\n") {
   $new_domain = $domain;
} else {
   $new_domain =~ s/[\r|\n]//g;
}
print FILE "	".'$domain = "' . $new_domain . "\";\n";
            

############################################################              
# USE SMTP OR SENDMAIL?
############################################################              
print "\nYou now need to choose the method that you will use for sending\n";
print "messages in SquirrelMail.  You can either connect to an SMTP server\n";
print "or use sendmail directly.\n";
if ($useSendmail eq "true") {
   $default_value = "n";
} else {
   $default_value = "y";
}
print "Use SMTP (y/n) [$default_value]: ";
$use_smtp = <STDIN>;
if (($use_smtp =~ /^y\n/i) || (($use_smtp =~ /^\n/) && ($default_value eq "y"))) {
   ############################################################              
   # SMTP Server 
   ############################################################              
   print "   What is the SMTP server [$smtpServerAddress]: ";
   $new_smtpServerAddress = <STDIN>;
   if ($new_smtpServerAddress eq "\n") {
      $new_smtpServerAddress = $smtpServerAddress;
   } else {
      $new_smtpServerAddress =~ s/[\r|\n]//g;
   }
   print FILE "	".'$smtpServerAddress = "' . $new_smtpServerAddress . "\";\n";
               
   
   
   ############################################################              
   # SMTP Port 
   ############################################################              
   print "   The port for your SMTP server [$smtpPort]: ";
   $new_smtpPort = <STDIN>;
   if ($new_smtpPort eq "\n") {
      $new_smtpPort = $smtpPort;
   } else {
      $new_smtpPort =~ s/[\r|\n]//g;
   }
   print FILE "	".'$smtpPort = ' . $new_smtpPort . ";\n";
} else {
   ############################################################              
   # Sendmail Path
   ############################################################              
   if ($sendmail_path[0] !~ /./) {
      $sendmail_path = "/usr/sbin/sendmail";
   }
   print "   Where is sendmail located [$sendmail_path]: ";
   $new_sendmail_path = <STDIN>;
   if ($new_sendmail_path eq "\n") {
      $new_sendmail_path = $sendmail_path;
   } else {
      $new_sendmail_path =~ s/[\r|\n]//g;
   }
   print FILE "	".'$useSendmail'." = true;\n";
   print FILE "	".'$sendmail_path = "' . $new_sendmail_path . "\";\n";
}



print "\n-------------------------------------------------.\n";
print "                General Options                   )\n";
print "-------------------------------------------------'\n";
###############################################################
# MOTD
###############################################################
print "\nYou can now create the welcome message that is displayed\n";
print "every time a user logs on.  You can use HTML or just plain\n";
print "text.\n\n(Type @ on a blank line to exit)\n";
do {
   print "] ";
   $line = <STDIN>;
   $line =~ s/[\r|\n]//g;
   $line =~ s/  /\&nbsp;\&nbsp;/g;
   if ($line ne "@") {
      $new_motd = $new_motd . $line;
   }   
} while ($line ne "@");
print FILE "	".'$motd = "'.$new_motd."\";\n";


############################################################              
# AUTO EXPUNGE 
############################################################              
if ($auto_expunge eq "false") {
   $default_value = "n";
} else {
   $default_value = "y";
}
print "Should we automatically expunge deleted messages (y/n) [$default_value]: ";
$autoe = <STDIN>;
if (($autoe =~ /^y\n/i) || (($autoe =~ /^\n/) && ($default_value eq "y"))) {
   print FILE "	\$auto_expunge = true;\n";
} else {
   print FILE "	\$auto_expunge = false;\n";
}
            

############################################################              
# default_sub_of_inbox 
############################################################              
if ($default_sub_of_inbox eq "false") {
   $default_value = "n";
} else {
   $default_value = "y";
}
print "By default, are new folders subfolders of INBOX (y/n) [$default_value]: ";
$autoe = <STDIN>;
if (($autoe =~ /^y\n/i) || (($autoe =~ /^\n/) && ($default_value eq "y"))) {
   print FILE "	\$default_sub_of_inbox = true;\n";
} else {
   print FILE "	\$default_sub_of_inbox = false;\n";
}




############################################################              
# show_contain_subfolders_option
############################################################              
print "\nSome IMAP daemons (UW) handle folders weird. They only allow a\n";
print "folder to contain either messages or subfolders.  Not both at the\n";
print "same time.  This option controls whether or not to display an \n";
print "option during folder creation letting them choose if this folder\n";
print "contains messages or folders.\n";
if ($show_contain_subfolders_option eq "false") {
   $default_value = "n";
} else {
   $default_value = "y";
}
print "Show the option to contain subfolders (y/n) [$default_value]: ";
$autoe = <STDIN>;
if (($autoe =~ /^y\n/i) || (($autoe =~ /^\n/) && ($default_value eq "y"))) {
   print FILE "	\$show_contain_subfolders_option = true;\n";
} else {
   print FILE "	\$show_contain_subfolders_option = false;\n";
}





############################################################              
# auto_forward 
############################################################              
print "\nThis option decides whether or not to use META tags to forward\n";
print "users past some of the notification screens\n";
if ($auto_forward eq "false") {
   $default_value = "n";
} else {
   $default_value = "y";
}
print "Automatically forward where possible (y/n) [$default_value]: ";
$autoe = <STDIN>;
if (($autoe =~ /^y\n/i) || (($autoe =~ /^\n/) && ($default_value eq "y"))) {
   print FILE "	\$auto_forward = true;\n";
} else {
   print FILE "	\$auto_forward = false;\n";
}




############################################################              
# DEFAULT CHARSET 
############################################################              
print "\nThis option controls what character set is used when sending mail\n";
print "and when sending HTMl to the browser. Do not set this to US-ASCII,\n";
print "use ISO-8859-1 instead. For cyrillic it is best to use KOI8-R,\n";
print "since this implementation is faster than the alternatives.\n";
print "Default charset [$default_charset]: ";
$new_default_charset = <STDIN>;
if ($new_default_charset eq "\n") {
   $new_default_charset = $default_charset;
} else {
   $new_default_charset =~ s/[\r|\n]//g;
}
print FILE "	".'$default_charset = "' . $new_default_charset . "\";\n";
            



############################################################              
# DATA DIRECTORY
############################################################              
print "\nIt is a possible security hole to have a writable directory\n";
print "under the web server's root directory (ex: /home/httpd/html).\n";
print "For this reason, it is possible to put the data directory\n";
print "anywhere you would like. The path name can be absolute or\n";
print "relative (to the config directory). It doesn't matter. Here are\n";
print "two examples:\n";
print "       Absolute:\n";
print "           /usr/local/squirrelmail/data/;\n";
print "       Relative (to the config directory):\n";
print "           ../data/;\n";
print "Where is the data directory  [$data_dir]: ";
$new_data_dir = <STDIN>;
if ($new_data_dir eq "\n") {
   $new_data_dir = $data_dir;
} else {
   $new_data_dir =~ s/[\r|\n]//g;
}
print FILE "	".'$data_dir = "' . $new_data_dir . "\";\n";
            



############################################################              
# ATTACHMENT DIRECTORY
############################################################              
print "\nPath to directory used for storing attachments while a mail is\n";
print "being sent. There are a few security considerations regarding this\n";
print "directory:\n";
print "  - It should have the permission 733 (rwx-wx-wx) to make it\n";
print "    impossible for a random person with access to the webserver to\n";
print "    list files in this directory. Confidential data might be laying\n";
print "    around there\n";
print "  - Since the webserver is not able to list the files in the content\n";
print "    is also impossible for the webserver to delete files lying around\n";
print "    there for too long.\n";
print "  - It should probably be another directory than data_dir.\n";
print "Where is the attachment directory  [$attachment_dir]: ";
$new_attachment_dir = <STDIN>;
if ($new_attachment_dir eq "\n") {
   $new_attachment_dir = $attachment_dir;
} else {
   $new_attachment_dir =~ s/[\r|\n]//g;
}
print FILE "	".'$attachment_dir = "' . $new_attachment_dir . "\";\n";
            



############################################################              
# DEFAULT LEFT SIZE
############################################################              
print "What is the default left folder list size (pixels)  [$default_left_size]: ";
$new_default_left_size = <STDIN>;
if ($new_default_left_size eq "\n") {
   $new_default_left_size = $default_left_size;
} else {
   $new_default_left_size =~ s/[\r|\n]//g;
}
print FILE "	".'$default_left_size = "' . $new_default_left_size . "\";\n";
            






print "\n-------------------------------------------------.\n";
print "                Special Folders                   )\n";
print "-------------------------------------------------'\n";

############################################################              
# Trash folder
############################################################              
print "What is the default trash folder [$trash_folder]: ";
$new_trash_folder = <STDIN>;
if ($new_trash_folder eq "\n") {
   $new_trash_folder = $trash_folder;
} else {
   $new_trash_folder =~ s/[\r|\n]//g;
}
print FILE "	".'$trash_folder = "' . $new_trash_folder . "\";\n";
            


############################################################              
# Default move to trash
############################################################              
if ($default_move_to_trash eq "true") {
   $default_value = "y";
} else {
   $default_value = "n";
}
print "By default, should deleted messages be moved to $new_trash_folder (y/n) [$default_value]: ";
$move_trash = <STDIN>;
if (($move_trash =~ /^y\n/i) || (($move_trash =~ /^\n/) && ($default_value eq "y"))) {
   print FILE "	\$default_move_to_trash = true;\n";
} else {
   print FILE "	\$default_move_to_trash = false;\n";
}
            


############################################################              
# Sent folder
############################################################              
print "What is the default sent folder [$sent_folder]: ";
$new_sent_folder = <STDIN>;
if ($new_sent_folder eq "\n") {
   $new_sent_folder = $sent_folder;
} else {
   $new_sent_folder =~ s/[\r|\n]//g;
}
print FILE "	".'$sent_folder = "' . $new_sent_folder . "\";\n";
            



############################################################              
# Special Folders
############################################################              
print "\nSpecial folders are folders that can't be manipulated like normal\n";
print "user-created folders.  A couple of examples of these would be the\n";
print "trash folder, the sent folder, etc.\n";
print "Special Folders:\n";
$count = 0;
print "\n";
while ($count < @special_folders) {   
   print "   $count) " . $special_folders[$count] . "\n";
   $count++;
}
print "[folders] command (?=help) > ";
$input = <STDIN>;
$input =~ s/[\r|\n]//g;
while ($input ne "d") {
   ## ADD
   if ($input =~ /^\s*\+\s*.*/) {
      $input =~ s/^\s*\+\s*//;
      $special_folders[$#special_folders+1] = $input;
   }

   elsif ($input =~ /^\s*-\s*[0-9]?/) {
      if ($input =~ /[0-9]+\s*$/) {
         $rem_num = $input;
         $rem_num =~ s/^\s*-\s*//g;
         $rem_num =~ s/\s*$//;
      } else {
         $rem_num = $#special_folders;
      }

      if ($rem_num == 0) {
         print "You cannot remove INBOX.  It is a very special folder.\n";
      } else {
         $count = 0;
         @new_special_folders = ();
         while ($count <= $#special_folders) {
            if ($count != $rem_num) {
               @new_special_folders = (@new_special_folders, $special_folders[$count]);
            }
            $count++;
         }
         @special_folders = @new_special_folders;
      }   
   }

   elsif ($input =~ /^\s*l\s*/) {
      $count = 0;
      print "\n";
      while ($count < @special_folders) {   
         print "   $count) " . $special_folders[$count] . "\n";
         $count++;
      }
   } elsif ($input =~ /^\s*\?\s*/) {
      print ".-------------------------.\n";
      print "| + Folder   (add folder) |\n";
      print "| - N     (remove folder) |\n";
      print "| l        (list folders) |\n";
      print "| d                (done) |\n";
      print "`-------------------------'\n";
   }

   else {
      print "Unrecognized command.\n";
   }

   print "[folders] command (?=help) > ";
   $input = <STDIN>;
   $input =~ s/[\r|\n]//g;
}

$count = 0;
while ($count <= $#special_folders) {
   print FILE "	\$special_folders[$count] = \"$special_folders[$count]\";\n";
   $count++;
}


############################################################              
# Use special folder color
############################################################              
if ($use_special_folder_color eq "true") {
   $default_value = "y";
} else {
   $default_value = "n";
}
print "\nHighlight special folders in a different color (y/n) [$default_value]: ";
$use_spec_folder = <STDIN>;
if (($use_spec_folder =~ /^y\n/i) || (($use_spec_folder =~ /^\n/) && ($default_value eq "y"))) {
   print FILE "	\$use_special_folder_color = true;\n";
} else {
   print FILE "	\$use_special_folder_color = false;\n";
}
            



############################################################              
# list_special_folders_first
############################################################              
if ($list_special_folders_first eq "false") {
   $default_value = "n";
} else {
   $default_value = "y";
}
print "Should special folders be listed first (y/n) [$default_value]: ";
$autoe = <STDIN>;
if (($autoe =~ /^y\n/i) || (($autoe =~ /^\n/) && ($default_value eq "y"))) {
   print FILE "	\$list_special_folders_first = true;\n";
} else {
   print FILE "	\$list_special_folders_first = false;\n";
}
            



############################################################              
# Themes
############################################################              
print "\nNow we will define the themes that you wish to use.  If you have added\n";
print "a theme of your own, just follow the instructions (?) about how to add\n";
print "them.  You can also change the default theme.\n";
print "[theme] command (?=help) > ";
$input = <STDIN>;
$input =~ s/[\r|\n]//g;
$theme_default = 0;
while ($input ne "d") {
   if ($input =~ /^\s*l\s*/i) {
      $count = 0;
      while ($count <= $#theme_name) {
         if ($count == $theme_default) {
            print " *";
         } else {
            print "  ";
         }
         $name = $theme_name[$count];
         $num_spaces = 25 - length($name);
         for ($i = 0; $i < $num_spaces;$i++) {
            $name = $name . " ";
         }
         
         print " $count.  $name";
         print "($theme_path[$count])\n";
         
         $count++;
      }
   } elsif ($input =~ /^\s*m\s*[0-9]+/i) {
      $old_def = $theme_default;
      $theme_default = $input;
      $theme_default =~ s/^\s*m\s*//;
      if (($theme_default > $#theme_name) || ($theme_default < 0)) {
         print "Cannot set default theme to $theme_default.  That theme does not exist.\n";
         $theme_default = $old_def;
      }
   } elsif ($input =~ /^\s*\+/) {
      print "What is the name of this theme: ";
      $name = <STDIN>;
      $name =~ s/[\r|\n]//g;
      $theme_name[$#theme_name+1] = $name;
      print "Be sure to put ../config/ before the filename.\n";
      print "What file is this stored in (ex: ../config/default_theme.php): ";
      $name = <STDIN>;
      $name =~ s/[\r|\n]//g;
      $theme_path[$#theme_path+1] = $name;
   } elsif ($input =~ /^\s*-\s*[0-9]?/) {
      if ($input =~ /[0-9]+\s*$/) {
         $rem_num = $input;
         $rem_num =~ s/^\s*-\s*//g;
         $rem_num =~ s/\s*$//;
      } else {
         $rem_num = $#theme_name;
      }
      if ($rem_num == $theme_default) {
         print "You cannot remove the default theme!\n";
      } else {
         $count = 0;
         @new_theme_name = ();
         @new_theme_path = ();
         while ($count <= $#theme_name) {
            if ($count != $rem_num) {
               @new_theme_name = (@new_theme_name, $theme_name[$count]);
               @new_theme_path = (@new_theme_path, $theme_path[$count]);
            }
            $count++;
         }
         @theme_name = @new_theme_name;
         @theme_path = @new_theme_path;
         if ($theme_default > $rem_num) {
            $theme_default--;
         }   
      }
   } elsif ($input =~ /^\s*\?\s*/) {
      print ".-------------------------.\n";
      print "| +           (add theme) |\n";
      print "| - N      (remove theme) |\n";
      print "| m N      (mark default) |\n";
      print "| l         (list themes) |\n";
      print "| d                (done) |\n";
      print "`-------------------------'\n";
   }
   print "[theme] command (?=help) > ";
	$input = <STDIN>;
	$input =~ s/[\r|\n]//g;
}

$count = 0;
print FILE "	\$theme[0][\"NAME\"] = \"$theme_name[$theme_default]\";\n";
print FILE "	\$theme[0][\"PATH\"] = \"$theme_path[$theme_default]\";\n";
$index = 1;
while ($count < $#theme_name) {
   if ($count != $theme_default) {
      print FILE "	\$theme[$index][\"NAME\"] = \"$theme_name[$count]\";\n";
      print FILE "	\$theme[$index][\"PATH\"] = \"$theme_path[$count]\";\n";
      $index++;
   }
   $count++;
}

print FILE "\n?>\n";
close FILE;


print "\n\nFINISHED!\n";
print "All changes have been written to cf.php.  If you would like, I can write\n";
print "the changes to config.php.\n";
print "Overwrite config.php (y/n) [y]: ";
$autoe = <STDIN>;
if (($autoe =~ /^y\n/i) || ($autoe =~ /^\n/)) {
   system "mv -f cf.php config.php";
}

            
