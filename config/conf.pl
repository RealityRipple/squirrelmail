#!/usr/bin/perl
# conf.pl
# Written March 26, 2000
# Luke Ehresman (lehresma@css.tayloru.edu)
#
# A simple configure script to configure squirrelmail
############################################################              
$WHT = "\x1B[37;1m";
$NRM = "\x1B[0m";

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
         @options = split(/\s*=\s*/, $var);
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
if ($useSendmail ne "true") {
   $useSendmail = "false";
}
if (!$sendmail_path) {
   $sendmail_path = "/usr/sbin/sendmail";
}


#####################################################################################

while (($command ne "q") && ($command ne "Q")) {
   system "clear";
   if ($menu == 0) {
      print $WHT."SquirrelMail Configuration Utility\n".$NRM;
      print $WHT."Main Menu --\n".$NRM;
      print "1.  Organization Preferences\n";
      print "2.  Server Settings\n";
      print "3.  Folder Defaults\n";
      print "4.  General Options\n";
      print "5.  Themes\n";
      print "6.  Address Books (LDAP)\n";
      print "7.  Message of the Day (MOTD)\n";
      print "\n";
   } elsif ($menu == 1) {
      print $WHT."Organization Preferences\n".$NRM;
      print "1.  Organization Name    : $WHT$org_name$NRM\n";
      print "2.  Organization Logo    : $WHT$org_logo$NRM\n";
      print "3.  Organization Title   : $WHT$org_title$NRM\n";
      print "\n";
      print "R   Return to Main Menu\n";
   } elsif ($menu == 2) {
      print $WHT."Server Settings\n".$NRM;
      print "1.  Domain               : $WHT$domain$NRM\n";
      print "2.  IMAP Server          : $WHT$imapServerAddress$NRM\n";
      print "3.  IMAP Port            : $WHT$imapPort$NRM\n";
      print "4.  Use Sendmail         : $WHT$useSendmail$NRM\n";
      if ($useSendmail eq "true") {
         print "5.    Sendmail Path      : $WHT$sendmail_path$NRM\n";
      } else {
         print "6.    SMTP Server        : $WHT$smtpServerAddress$NRM\n";
         print "7.    SMTP Port          : $WHT$smtpPort$NRM\n";
      }
      print "\n";
      print "R   Return to Main Menu\n";
   } elsif ($menu == 3) {
      print $WHT."Folder Defaults\n".$NRM;
      print "1.  Default Folder Prefix      : $WHT$default_folder_prefix$NRM\n";
      print "2.  Show Folder Prefix Option  : $WHT$show_prefix_option$NRM\n";
      print "3.  Trash Folder               : $WHT$trash_folder$NRM\n";
      print "4.  Sent Folder                : $WHT$sent_folder$NRM\n";
      print "5.  List Special Folders First : $WHT$list_special_folders_first$NRM\n";
      print "6.  Show Special Folders Color : $WHT$use_special_folder_color$NRM\n";
      print "7.  By default, move to trash  : $WHT$default_move_to_trash$NRM\n";
      print "8.  Auto Expunge               : $WHT$auto_expunge$NRM\n";
      print "9.  Default Sub. of INBOX      : $WHT$default_sub_of_inbox$NRM\n";
      print "10. Show 'Contain Sub.' Option : $WHT$show_contain_subfolders_option$NRM\n";
      print "11. Special Folders            > $WHT$special_folders[0]$NRM\n";
      for ($count = 1; $count <= $#special_folders; $count++) {
         print "                               > $WHT$special_folders[$count]$NRM\n";
      }
      print "\n";
      print "R   Return to Main Menu\n";
   } elsif ($menu == 4) {
      print $WHT."General Options\n".$NRM;
      print "1.  Default Charset      : $WHT$default_charset$NRM\n";
      print "2.  Auto Forward         : $WHT$auto_forward$NRM\n";
      print "3.  Data Directory       : $WHT$data_dir$NRM\n";
      print "4.  Attachment Directory : $WHT$attachment_dir$NRM\n";
      print "5.  Default Left Size    : $WHT$default_left_size$NRM\n";
      print "\n";
      print "R   Return to Main Menu\n";
   } elsif ($menu == 5) {
      print $WHT."Themes\n".$NRM;
      print "1.  Change Themes\n";
      for ($count = 0; $count <= $#theme_name; $count++) {
         print "    |  $theme_name[$count]\n";
      }
      print "\n";
      print "R   Return to Main Menu\n";
   } elsif ($menu == 6) {
      print $WHT."Address Books (LDAP)\n".$NRM;
      print "\n";
      print "R   Return to Main Menu\n";
   } elsif ($menu == 7) {
      print $WHT."Message of the Day (MOTD)\n".$NRM;
      print "\n$motd\n";
      print "\n";
      print "1   Edit the MOTD\n";
      print "\n";
      print "R   Return to Main Menu\n";
   }
   print "S   Save data\n";
   print "Q   Quit\n";

   print "\n";
   print "Command >> ".$WHT;
   $command = <STDIN>;
   $command =~ s/[\n|\r]//g; 
   print "$NRM\n";

   # Read the commands they entered.
   if (($command eq "R") || ($command eq "r")) {
      $menu = 0;
   } elsif (($command eq "s") || ($command eq "S")) {
      save_data ();
      print "Data saved in cf.php\n";
      print "Press any key to continue...";
      $tmp = <STDIN>;
      $saved = 1;
   } elsif ((($command eq "q") || ($command eq "Q")) && ($saved == 0)) {
      print "You have not saved your data.\n";
      print "Save?  (y/n) [".$WHT."y".$NRM."]: ";
      $save = <STDIN>;
      if (($save =~ /^y/i) || ($save =~ /^\s*$/)) {
         save_data ();
       }
   } else {
      $saved = 0;
      if ($menu == 0) {
         if (($command > 0) && ($command < 8)) {
            $menu = $command;
         }
      } elsif ($menu == 1) {
         if    ($command == 1) { $org_name   = command1 (); }
         elsif ($command == 2) { $org_logo   = command2 (); }
         elsif ($command == 3) { $org_title  = command3 (); }
      } elsif ($menu == 2) {
         if    ($command == 1) { $domain             = command11 (); }
         elsif ($command == 2) { $imapServerAddress  = command12 (); }
         elsif ($command == 3) { $imapPort           = command13 (); }
         elsif ($command == 4) { $useSendmail        = command14 (); }
         elsif ($command == 5) { $sendmail_path      = command15 (); }
         elsif ($command == 6) { $smtpServerAddress  = command16 (); }
         elsif ($command == 7) { $smtpPort           = command17 (); }
      } elsif ($menu == 3) {
         if    ($command == 1) { $default_folder_prefix          = command21 (); }
         elsif ($command == 2) { $show_prefix_option             = command22 (); }
         elsif ($command == 3) { $trash_folder                   = command23 (); }
         elsif ($command == 4) { $sent_folder                    = command24 (); }
         elsif ($command == 5) { $list_special_folders_first     = command25 (); }
         elsif ($command == 6) { $use_special_folder_color       = command26 (); }
         elsif ($command == 7) { $default_move_to_trash          = command27 (); }
         elsif ($command == 8) { $auto_expunge                   = command28 (); }
         elsif ($command == 9) { $default_sub_of_inbox           = command29 (); }
         elsif ($command == 10){ $show_contain_subfolders_option = command210(); }
         elsif ($command == 11){ $special_folders                = command211(); }
      } elsif ($menu == 4) {
         if    ($command == 1) { $default_charset    = command31 (); }
         elsif ($command == 2) { $auto_forward       = command32 (); }
         elsif ($command == 3) { $data_dir           = command33 (); }
         elsif ($command == 4) { $attachment_dir     = command34 (); }
         elsif ($command == 5) { $default_left_size  = command35 (); }
      } elsif ($menu == 5) {
         if ($command == 1) { 
            command41 (); 
         }
      } elsif ($menu == 6) {
      } elsif ($menu == 7) {
         if    ($command == 1) { $motd   = command71 (); }
      }
   }   
}

####################################################################################

# org_name
sub command1 {
   print "We have tried to make the name SquirrelMail as transparent as\n";
   print "possible.  If you set up an organization name, most places where\n";
   print "SquirrelMail would take credit will be credited to your organization.\n";
   print "\n";
   print "[$WHT$org_name$NRM]: $WHT";
   $new_org_name = <STDIN>;
   if ($new_org_name eq "\n") {
      $new_org_name = $org_name;
   } else {
      $new_org_name =~ s/[\r|\n]//g;
   }
   return $new_org_name;
}


# org_logo
sub command2 {
   print "Your organization's logo is an image that will be displayed at\n";
   print "different times throughout SquirrelMail.  This is asking for the\n";
   print "literal (/usr/local/squirrelmail/images/logo.jpg) or relative\n";
   print "(../images/logo.jpg) path to your logo.\n";
   print "\n";
   print "[$WHT$org_logo$NRM]: $WHT";
   $new_org_logo = <STDIN>;
   if ($new_org_logo eq "\n") {
      $new_org_logo = $org_logo;
   } else {
      $new_org_logo =~ s/[\r|\n]//g;
   }
   return $new_org_logo;
}

# org_title
sub command3 {
   print "A title is what is displayed at the top of the browser window in\n";
   print "the titlebar.  Usually this will end up looking something like:\n";
   print "\"Netscape: $org_title\"\n";
   print "\n";
   print "[$WHT$org_title$NRM]: $WHT";
   $new_org_title = <STDIN>;
   if ($new_org_title eq "\n") {
      $new_org_title = $org_title;
   } else {
      $new_org_title =~ s/[\r|\n]//g;
   }
   return $new_org_title;
}

####################################################################################

# domain
sub command11 {
   print "The domain name is the suffix at the end of all email messages.  If\n";
   print "for example, your email address is jdoe\@myorg.com, then your domain\n";
   print "would be myorg.com.\n";
   print "\n";
   print "[$WHT$domain$NRM]: $WHT";
   $new_domain = <STDIN>;
   if ($new_domain eq "\n") {
      $new_domain = $domain;
   } else {
      $new_domain =~ s/[\r|\n]//g;
   }
   return $new_domain;
}

# imapServerAddress
sub command12 {
   print "This is the address where your IMAP server resides.\n";
   print "[$WHT$imapServerAddress$NRM]: $WHT";
   $new_imapServerAddress = <STDIN>;
   if ($new_imapServerAddress eq "\n") {
      $new_imapServerAddress = $imapServerAddress;
   } else {
      $new_imapServerAddress =~ s/[\r|\n]//g;
   }
   return $new_imapServerAddress;
}

# imapPort
sub command13 {
   print "This is the port that your IMAP server is on.  Usually this is 143.\n";
   print "[$WHT$imapPort$NRM]: $WHT";
   $new_imapPort = <STDIN>;
   if ($new_imapPort eq "\n") {
      $new_imapPort = $imapPort;
   } else {
      $new_imapPort =~ s/[\r|\n]//g;
   }
   return $new_imapPort;
}

# useSendmail
sub command14 {
   print "You now need to choose the method that you will use for sending\n";
   print "messages in SquirrelMail.  You can either connect to an SMTP server\n";
   print "or use sendmail directly.\n";
   if ($useSendmail eq "true") {
      $default_value = "y";
   } else {
      $default_value = "n";
   }
   print "\n";
   print "Use Sendmail (y/n) [$WHT$default_value$NRM]: $WHT";
   $use_sendmail = <STDIN>;
   if (($use_sendmail =~ /^y\n/i) || (($use_sendmail =~ /^\n/) && ($default_value eq "y"))) {
      $useSendmail = "true";
   } else {
      $useSendmail = "false";
   }
   return $useSendmail;
}

# sendmail_path
sub command15 {
   if ($sendmail_path[0] !~ /./) {
      $sendmail_path = "/usr/sbin/sendmail";
   }
   print "Specify where the sendmail executable is located.  Usually /usr/sbin/sendmail\n";
   print "[$WHT$sendmail_path$NRM]: $WHT";
   $new_sendmail_path = <STDIN>;
   if ($new_sendmail_path eq "\n") {
      $new_sendmail_path = $sendmail_path;
   } else {
      $new_sendmail_path =~ s/[\r|\n]//g;
   }
   return $new_sendmail_path;
}

# smtpServerAddress
sub command16 {
   print "This is the location of your SMTP server.\n";
   print "[$WHT$smtpServerAddress$NRM]: $WHT";
   $new_smtpServerAddress = <STDIN>;
   if ($new_smtpServerAddress eq "\n") {
      $new_smtpServerAddress = $smtpServerAddress;
   } else {
      $new_smtpServerAddress =~ s/[\r|\n]//g;
   }
   return $new_smtpServerAddress;
}

# smtpPort
sub command17 {
   print "This is the port to connect to for SMTP.  Usually 25.\n";
   print "[$WHT$smtpPort$NRM]: $WHT";
   $new_smtpPort = <STDIN>;
   if ($new_smtpPort eq "\n") {
      $new_smtpPort = $smtpPort;
   } else {
      $new_smtpPort =~ s/[\r|\n]//g;
   }
   return $new_smtpPort;
}

# MOTD
sub command71 {
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
   return $new_motd;
}

################# FOLDERS ###################

# default_folder_prefix
sub command21 {
   print "Some IMAP servers (UW, for example) store mail and folders in\n";
   print "your user space in a separate subdirectory.  This is where you\n";
   print "specify what that directory is.\n";
   print "\n";
   print "EXAMPLE:  mail/";
   print "\n";
   print "NOTE:  If you use Cyrus, or some server that would not use this\n";
   print "       option, you must set this to 'none'.\n";
   print "\n";
   print "[$WHT$default_folder_prefix$NRM]: $WHT";
   $new_default_folder_prefix = <STDIN>;
   if ($new_default_folder_prefix eq "\n") {
      $new_default_folder_prefix = $default_folder_prefix;
   } else {
      $new_default_folder_prefix =~ s/[\r|\n]//g;
   }
   if (($new_default_folder_prefix =~ /^\s*$/) || ($new_default_folder_prefix =~ /none/i)) {
      $new_default_folder_prefix = "";
   } else {
      $new_default_folder_prefix =~ s/\/*$//g;
      $new_default_folder_prefix =~ s/$/\//g;
   }   
   return $new_default_folder_prefix;
}

# Show Folder Prefix
sub command22 {
   print "It is possible to set up the default folder prefix as a user\n";
   print "specific option, where each user can specify what their mail\n";
   print "folder is.  If you set this to false, they will never see the\n";
   print "option, but if it is true, this option will appear in the\n";
   print "'options' section.\n";
   print "\n";
   print "NOTE:  You set the default folder prefix in option '1' of this\n";
   print "       section.  That will be the default if the user doesn't\n";
   print "       specify anything different.\n";
   print "\n";
   
   if ($show_prefix_option eq "true") {
      $default_value = "y";
   } else {
      $default_value = "n";
   }
   print "\n";
   print "Show option (y/n) [$WHT$default_value$NRM]: $WHT";
   $new_show = <STDIN>;
   if (($new_show =~ /^y\n/i) || (($new_show =~ /^\n/) && ($default_value eq "y"))) {
      $show_prefix_option = "true";
   } else {
      $show_prefix_option = "false";
   }
   return $show_prefix_option;
}

# Trash Folder 
sub command23 {
   print "You can now specify where the default trash folder is located.\n";
   print "On servers where you do not want this, you can set it to anything\n";
   print "and set option 7 to false.\n";
   print "\n";
   print "This is relative to where the rest of your email is kept.  You do\n";
   print "not need to worry about their mail directory.  If this folder\n";
   print "would be ~/mail/trash on the filesystem, you only need to specify\n";
   print "that this is 'trash', and be sure to put 'mail/' in option 1.\n";
   print "\n";

   print "[$WHT$trash_folder$NRM]: $WHT";
   $new_trash_folder = <STDIN>;
   if ($new_trash_folder eq "\n") {
      $new_trash_folder = $trash_folder;
   } else {
      $new_trash_folder =~ s/[\r|\n]//g;
   }
   return $new_trash_folder;
}

# Sent Folder 
sub command24 {
   print "This is where messages that are sent will be stored.  SquirrelMail\n";
   print "by default puts a copy of all outgoing messages in this folder.\n";
   print "\n";
   print "This is relative to where the rest of your email is kept.  You do\n";
   print "not need to worry about their mail directory.  If this folder\n";
   print "would be ~/mail/sent on the filesystem, you only need to specify\n";
   print "that this is 'sent', and be sure to put 'mail/' in option 1.\n";
   print "\n";

   print "[$WHT$sent_folder$NRM]: $WHT";
   $new_sent_folder = <STDIN>;
   if ($new_sent_folder eq "\n") {
      $new_sent_folder = $sent_folder;
   } else {
      $new_sent_folder =~ s/[\r|\n]//g;
   }
   return $new_sent_folder;
}

# List special folders first 
sub command25 {
   print "SquirrelMail has what we call 'special folders' that are not\n";
   print "manipulated and viewed like normal folders.  Some examples of\n";
   print "these folders would be INBOX, Trash, Sent, etc.  This option\n";
   print "Simply asks if you want these folders listed first in the folder\n";
   print "listing.\n";
   print "\n";
   
   if ($list_special_folders_first eq "true") {
      $default_value = "y";
   } else {
      $default_value = "n";
   }
   print "\n";
   print "List first (y/n) [$WHT$default_value$NRM]: $WHT";
   $new_show = <STDIN>;
   if (($new_show =~ /^y\n/i) || (($new_show =~ /^\n/) && ($default_value eq "y"))) {
      $list_special_folders_first = "true";
   } else {
      $list_special_folders_first = "false";
   }
   return $list_special_folders_first;
}

# Show special folders color 
sub command26 {
   print "SquirrelMail has what we call 'special folders' that are not\n";
   print "manipulated and viewed like normal folders.  Some examples of\n";
   print "these folders would be INBOX, Trash, Sent, etc.  This option\n";
   print "wants to know if we should display special folders in a\n";
   print "color than the other folders.\n";
   print "\n";
   
   if ($use_special_folder_color eq "true") {
      $default_value = "y";
   } else {
      $default_value = "n";
   }
   print "\n";
   print "Show color (y/n) [$WHT$default_value$NRM]: $WHT";
   $new_show = <STDIN>;
   if (($new_show =~ /^y\n/i) || (($new_show =~ /^\n/) && ($default_value eq "y"))) {
      $use_special_folder_color = "true";
   } else {
      $use_special_folder_color = "false";
   }
   return $use_special_folder_color;
}

# default move to trash
sub command27 {
   print "By default, should messages get moved to the trash folder?  You\n";
   print "can specify the default trash folder in option 3.  If this is set\n";
   print "to false, messages will get deleted immediately without moving\n";
   print "to the trash folder.\n";
   print "\n";
   print "Trash folder is currently: $trash_folder\n";
   print "\n";
   
   if ($default_move_to_trash eq "true") {
      $default_value = "y";
   } else {
      $default_value = "n";
   }
   print "By default, move to trash (y/n) [$WHT$default_value$NRM]: $WHT";
   $new_show = <STDIN>;
   if (($new_show =~ /^y\n/i) || (($new_show =~ /^\n/) && ($default_value eq "y"))) {
      $default_move_to_trash = "true";
   } else {
      $default_move_to_trash = "false";
   }
   return $default_move_to_trash;
}

# Auto expunge 
sub command28 {
   print "The way that IMAP handles deleting messages is as follows.  You\n";
   print "mark the message as deleted, and then to 'really' delete it, you\n";
   print "expunge it.  This option asks if you want to just have messages\n";
   print "marked as deleted, or if you want SquirrelMail to expunge the \n";
   print "messages too.\n";
   print "\n";
   
   if ($auto_expunge eq "true") {
      $default_value = "y";
   } else {
      $default_value = "n";
   }
   print "Auto expunge (y/n) [$WHT$default_value$NRM]: $WHT";
   $new_show = <STDIN>;
   if (($new_show =~ /^y\n/i) || (($new_show =~ /^\n/) && ($default_value eq "y"))) {
      $auto_expunge = "true";
   } else {
      $auto_expunge = "false";
   }
   return $auto_expunge;
}

# Default sub of inbox 
sub command29 {
   print "Some IMAP servers (Cyrus) have all folders as subfolders of INBOX.\n";
   print "This can cause some confusion in folder creation for users when\n";
   print "they try to create folders and don't put it as a subfolder of INBOX\n";
   print "and get permission errors.  This option asks if you want folders\n";
   print "to be subfolders of INBOX by default.\n";
   print "\n";
   
   if ($default_sub_of_inbox eq "true") {
      $default_value = "y";
   } else {
      $default_value = "n";
   }
   print "Default sub of INBOX (y/n) [$WHT$default_value$NRM]: $WHT";
   $new_show = <STDIN>;
   if (($new_show =~ /^y\n/i) || (($new_show =~ /^\n/) && ($default_value eq "y"))) {
      $default_sub_of_inbox = "true";
   } else {
      $default_sub_of_inbox = "false";
   }
   return $default_sub_of_inbox;
}

# Show contain subfolder option 
sub command210 {
   print "Some IMAP servers (UW) make it so that there are two types of\n";
   print "folders.  Those that contain messages, and those that contain\n";
   print "subfolders.  If this is the case for your server, set this to\n";
   print "true, and it will ask the user whether the folder they are\n";
   print "creating contains subfolders or messages.\n";
   print "\n";
   
   if ($show_contain_subfolders_option eq "true") {
      $default_value = "y";
   } else {
      $default_value = "n";
   }
   print "Show option (y/n) [$WHT$default_value$NRM]: $WHT";
   $new_show = <STDIN>;
   if (($new_show =~ /^y\n/i) || (($new_show =~ /^\n/) && ($default_value eq "y"))) {
      $show_contain_subfolders_option = "true";
   } else {
      $show_contain_subfolders_option = "false";
   }
   return $show_contain_subfolders_option;
}

# special folders
sub command211 {
   print "\nSpecial folders are folders that can't be manipulated like normal\n";
   print "user-created folders.  A couple of examples of these would be the\n";
   print "trash folder, the sent folder, etc.\n";
   print "Special Folders:\n";
   $count = 0;
   print "\n";
   while ($count < @special_folders) {
      print "   $count) $WHT" . $special_folders[$count] . "$NRM\n";
      $count++;
   }
   print "\n[folders] command (?=help) > ";
   $input = <STDIN>;
   $input =~ s/[\r|\n]//g;
   while ($input !~ /^d$/i) {
      ## ADD
      if ($input =~ /^\s*\+\s*.*/) {
         $input =~ s/^\s*\+\s*//;
         $special_folders[$#special_folders+1] = $input;
      }
   
      elsif ($input =~ /^\s*-\s*[0-9]?/i) {
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
            $removed = 0;
            while ($count <= $#special_folders) {
               if ($count != $rem_num) {
                  @new_special_folders = (@new_special_folders, $special_folders[$count]);     
               }
               if ($count == $rem_num) {
                  print "Removed: $special_folders[$rem_num]\n";
                  $removed = 1;
               }
               $count++;
            }
            if ($removed != 1) {
               print "Error: Can't delete an entry that's not there!\n";
            }
            @special_folders = @new_special_folders;
         }
      }
   
      elsif ($input =~ /^\s*l\s*/i) {
         $count = 0;
         print "\n";
         while ($count < @special_folders) {
            print "   $count) $WHT" . $special_folders[$count] . "$NRM\n";
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
   
      print "\n[folders] command (?=help) > ";
      $input = <STDIN>;
      $input =~ s/[\r|\n]//g;
   }
   return @special_folders;
}

############# GENERAL OPTIONS #####################

# Default Charset
sub command31 {
   print "This option controls what character set is used when sending\n";
   print "mail and when sending HTML to the browser.  Do not set this\n";
   print "to US-ASCII, use ISO-8859-1 instead.  For cyrillic, it is best\n";
   print "to use KOI8-R, since this implementation is faster than most\n";
   print "of the alternatives\n";
   print "\n";

   print "[$WHT$default_charset$NRM]: $WHT";
   $new_default_charset = <STDIN>;
   if ($new_default_charset eq "\n") {
      $new_default_charset = $default_charset;
   } else {
      $new_default_charset =~ s/[\r|\n]//g;
   }
   return $new_default_charset;
}

# Auto Forward
sub command32 {
   print "There are some places that we will automatically forward the user\n";
   print "rather than display pointless messages like 'Message sent!' or\n";
   print "stuff like that.  Use this or not?\n";
   print "\n";
   print "NOTE:  This is not working currently as we are trying to find a\n";
   print "       better way to handle this.  This option really doesn't do\n";
   print "       much.\n";
   print "\n";
   
   if ($auto_forward eq "true") {
      $default_value = "y";
   } else {
      $default_value = "n";
   }
   print "Auto forward (y/n) [$WHT$default_value$NRM]: $WHT";
   $new_show = <STDIN>;
   if (($new_show =~ /^y\n/i) || (($new_show =~ /^\n/) && ($default_value eq "y"))) {
      $auto_forward = "true";
   } else {
      $auto_forward = "false";
   }
   return $auto_forward;
}

# Data directory
sub command33 {
   print "It is a possible security hole to have a writable directory\n";
   print "under the web server's root directory (ex: /home/httpd/html).\n";
   print "For this reason, it is possible to put the data directory\n";
   print "anywhere you would like.  The path name can be absolute or\n";
   print "relative (to the config directory).  It doesn't matter.  Here\n";
   print "are two examples:\n";
   print "  Absolute:    /usr/local/squirrelmail/data/\n";
   print "  Relative:    ../data/\n";
   print "\n";

   print "[$WHT$data_dir$NRM]: $WHT";
   $new_data_dir = <STDIN>;
   if ($new_data_dir eq "\n") {
      $new_data_dir = $data_dir;
   } else {
      $new_data_dir =~ s/[\r|\n]//g;
   }
   if ($new_data_dir =~ /^\s*$/) {
      $new_data_dir = "";
   } else {
      $new_data_dir =~ s/\/*$//g;
      $new_data_dir =~ s/$/\//g;
   }   
   return $new_data_dir;
}

# Attachment directory
sub command34 {
   print "Path to directory used for storing attachments while a mail is\n";
   print "being sent.  There are a few security considerations regarding this\n";
   print "directory:\n";
   print "  1.  It should have the permission 733 (rwx-wx-wx) to make it\n";
   print "      impossible for a random person with access to the webserver\n";
   print "      to list files in this directory.  Confidential data might\n";
   print "      be laying around in there.\n";
   print "  2.  Since the webserver is not able to list the files in the\n";
   print "      content is also impossible for the webserver to delete files\n";
   print "      lying around there for too long.\n";
   print "  3.  It should probably be another directory than the data\n";
   print "      directory specified in option 3.\n";
   print "\n";

   print "[$WHT$attachment_dir$NRM]: $WHT";
   $new_attachment_dir = <STDIN>;
   if ($new_attachment_dir eq "\n") {
      $new_attachment_dir = $attachment_dir;
   } else {
      $new_attachment_dir =~ s/[\r|\n]//g;
   }
   if ($new_attachment_dir =~ /^\s*$/) {
      $new_attachment_dir = "";
   } else {
      $new_attachment_dir =~ s/\/*$//g;
      $new_attachment_dir =~ s/$/\//g;
   }   
   return $new_attachment_dir;
}


sub command35 {
   print "This is the default size (in pixels) of the left folder list.\n";
   print "Default is 200, but you can set it to whatever you wish.  This\n";
   print "is a user preference, so this will only show up as their default.\n";
   print "\n";
   print "[$WHT$default_left_size$NRM]: $WHT";
   $new_default_left_size = <STDIN>;
   if ($new_default_left_size eq "\n") {
      $new_default_left_size = $default_left_size;
   } else {
      $new_default_left_size =~ s/[\r|\n]//g;
   }
   return $new_default_left_size;
}


sub command41 {
   print "\nNow we will define the themes that you wish to use.  If you have added\n";
   print "a theme of your own, just follow the instructions (?) about how to add\n";
   print "them.  You can also change the default theme.\n";
   print "[theme] command (?=help) > ";
   $input = <STDIN>;
   $input =~ s/[\r|\n]//g;
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
}   



sub save_data {
   open (FILE, ">config.php");

   print FILE "<?\n\t/** SquirrelMail configuration\n";
   print FILE "\t ** Created using the configure script, conf.pl\n\t **/\n\n";

   print FILE "\t\$org_name   = \"$org_name\";\n";
   print FILE "\t\$org_logo   = \"$org_logo\";\n";
   print FILE "\t\$org_title  = \"$org_title\";\n";

   print FILE "\n";

   print FILE "\t\$domain               = $domain;\n";
   print FILE "\t\$imapServerAddress    = $imapServerAddress;\n";
   print FILE "\t\$imapPort             = $imapPort;\n";
   print FILE "\t\$useSendmail          = $useSendmail;\n";
   print FILE "\t\$smtpServerAddress    = $smtpServerAddress;\n";
   print FILE "\t\$smtpPort             = $smtpPort;\n";
   print FILE "\t\$sendmailPath         = $sendmailPath;\n";
   
   print FILE "\n";

   print FILE "\t\$default_folder_prefix            = \"$default_folder_prefix\";\n";
   print FILE "\t\$trash_folder                     = \"$default_trash_folder\";\n";
   print FILE "\t\$sent_folder                      = \"$sent_folder\";\n";
   print FILE "\t\$show_prefix_option               =  $show_prefix_option;\n";
   print FILE "\t\$list_special_folders_first       =  $list_special_folders_first;\n";
   print FILE "\t\$use_special_folder_color         =  $use_special_folder_color;\n";
   print FILE "\t\$default_move_to_trash            =  $default_move_to_trash;\n";
   print FILE "\t\$auto_expunge                     =  $auto_expunge;\n";
   print FILE "\t\$default_sub_of_inbox             =  $default_sub_of_inbox;\n";
   print FILE "\t\$show_contains_subfolders_option  = $show_contains_subfolders_option;\n";
   for ($count=0; $count <= $#special_folders; $count++) {
      print FILE "\t\$special_folders[$count]              = \"$special_folders[$count]\";\n";
   }
   print FILE "\n";

	print FILE "\t\$default_charset   = \"$default_charset\";\n";
	print FILE "\t\$auto_forward      =  $auto_forward;\n";
	print FILE "\t\$data_dir          = \"$data_dir\";\n";
	print FILE "\t\$attachment_dir    = \"$attachment_dir\";\n";
	print FILE "\t\$default_left_size =  $default_left_size;\n";

   print FILE "\n";

   for ($count=0; $count <= $#theme_name; $count++) {
		print FILE "\t\$theme[$count][\"PATH\"] = \"$theme_path[$count]\";\n";
		print FILE "\t\$theme[$count][\"NAME\"] = \"$theme_name[$count]\";\n";
	}
	
   print FILE "\n";

	print FILE "\t\$motd = \"$motd\";\n";

   print FILE "?>\n";
   close FILE;
}
