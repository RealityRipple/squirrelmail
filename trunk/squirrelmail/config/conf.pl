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
		print "\n";
		print "R   Return to Main Menu\n";
	} elsif ($menu == 4) {
		print $WHT."General Options\n".$NRM;
		print "\n";
		print "R   Return to Main Menu\n";
	} elsif ($menu == 5) {
		print $WHT."Themes\n".$NRM;
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
	print "X   Quit without saving\n";
	print "Q   Save and exit\n";

	print "\n";
	print "Command >> ".$WHT;
	$command = <STDIN>;
	$command =~ s/[\n|\r]//g; 
	print "$NRM\n";

	# Read the commands they entered.
	if (($command eq "R") || ($command eq "r")) {
		$menu = 0;
	} else {
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
		} elsif ($menu == 4) {
		} elsif ($menu == 5) {
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
