#!/usr/bin/perl
#
# Converts file-based preferences into SQL statements.
#
# WARNING: this script is experimental.  We recommend that
# you not use it when logged in as a privileged user (such
# as root).  Also, ALWAYS back up your data directory before
# using this script.
#
# Copyright (c) 2002, Michael Blandford and Tal Yardeni
# Copyright (c) 2005-2016 The SquirrelMail Project Team
#
# This script is licensed under the GNU Public License (GPL).
# See: http://opensource.org/licenses/gpl-license.php
# $Id$
#

##### Default values #####
# TODO: expose the database type as a CLI option, but first need more sections in sub escape_sql_string()
my $dbtype = 'mysql';
my $abookdb = "squirrelmail";
my $prefdb = "squirrelmail";
my $abook_table = "address";
my $abook_owner="owner";
my $abook_nickname="nickname";
my $abook_firstname="firstname";
my $abook_lastname="lastname";
my $abook_email="email";
my $abook_label="label";
my $pref_table = "userprefs";
my $pref_user = "user";
my $pref_key = "prefkey";
my $pref_value = "prefval";
##### ##### #####

use Getopt::Long;

my (%opts, $verbose, $data_dir);

&GetOptions( \%opts, qw( abook data_dir:s delete h help v verbose pref sig user:s abookdb:s prefdb:s pref_table:s abook_table:s abook_owner:s abook_nickname:s abook_firstname:s abook_lastname:s abook_email:s abook_label:s pref_user:s pref_key:s pref_value:s) );

&Usage   if ( defined $opts{h} or defined $opts{help} );

unless ( defined $opts{abook} or defined $opts{pref} or defined $opts{sig}) {
    $opts{abook}='TRUE';
    $opts{pref}='TRUE';
    $opts{sig}='TRUE';
}


if ( defined $opts{verbose} or defined $opts{v} ) {
    $verbose = 1;
}
if ( defined $opts{abookdb} and $opts{abookdb} ) {
    $abookdb = $opts{abookdb};
}
if ( defined $opts{prefdb} and $opts{prefdb} ) {
    $prefdb = $opts{prefdb};
}
if ( defined $opts{pref_table} and $opts{pref_table} ) {
    $pref_table = $opts{pref_table};
}
if ( defined $opts{pref_user} and $opts{pref_user} ) {
    $pref_user = $opts{pref_user};
}
if ( defined $opts{pref_key} and $opts{pref_key} ) {
    $pref_key = $opts{pref_key};
}
if ( defined $opts{pref_value} and $opts{pref_value} ) {
    $pref_value = $opts{pref_value};
}
if ( defined $opts{abook_table} and $opts{abook_table}) {
    $abook_table = $opts{abook_table};
}
if ( defined $opts{abook_owner} and $opts{abook_owner} ) {
    $abook_owner = $opts{abook_owner};
}
if ( defined $opts{abook_nickname} and $opts{abook_nickname} ) {
    $abook_nickname = $opts{abook_nickname};
}
if ( defined $opts{abook_firstname} and $opts{abook_firstname} ) {
    $abook_firstname = $opts{abook_firstname};
}
if ( defined $opts{abook_lastname} and $opts{abook_lastname} ) {
    $abook_lastname = $opts{abook_lastname};
}
if ( defined $opts{abook_email} and $opts{abook_email} ) {
    $abook_email = $opts{abook_email};
}
if ( defined $opts{abook_label} and $opts{abook_label} ) {
    $abook_label = $opts{abook_label};
}

# Get data directory option and display help if it is not defined
if ( defined $opts{data_dir} and $opts{data_dir} ) {
    $data_dir = $opts{data_dir};
} else {
    &Usage;
}

# Are we looking for specific users or all users?
# There has to be a better way to do this - Below
my @user_list = split ( /,/, $opts{user} ) if defined $opts{user};

inspect_files($data_dir);

# All done.  Below are functions


# Finds needed user files in the given directory
# and recurses any nested data directories as needed
#
sub inspect_files {

  my ($data_dir) = @_;
  my ($filename, $username, $ext);
  local *DIR;

  # Here we go
  # If no arguments are passed, and we cant open the dir, we should
  # get a usage.
  opendir(DIR, $data_dir) or 
    die "DIRECTORY READ ERROR: Could not open $data_dir!!\n";

  while ( $filename = readdir DIR ) {

    next if ( $filename eq "." or $filename eq ".." );

    if ($verbose) { print STDERR "; INSPECTING: $data_dir/$filename\n"; }

    # recurse into nested (hashed) directories
    #
    if ($filename =~ /^[0123456789abcdef]$/ && -d "$data_dir/$filename") {
      inspect_files("$data_dir/$filename");
    }

    next unless $filename =~ /(.*)\.(.*)/;
    $username = $1;
    next unless $username;

    # Deal with the people
    # There has to be a better way to do this - Above
    next if (defined $opts{user} and !grep($username eq $_, @user_list));

    # Deal with the extension files
    $ext = $2;
    next unless $ext;

    &abook("$data_dir/$filename", $username)
      if ( $ext eq "abook" and defined $opts{abook} );
    &pref("$data_dir/$filename", $username)
      if ( $ext eq "pref"  and defined $opts{pref}  ); 
    &sig("$data_dir/$filename", $username)
      if ( $ext =~ /si([g\d])$/ and defined $opts{sig});
  }
  closedir ( DIR );

}

# Process a user address file

sub abook {

  my ($filepath, $username) = @_;

  if ($verbose) { print STDERR "; PARSING ADDRESS BOOK DATA FROM: $filepath\n"; }

  if ( defined $opts{delete} ) {
    print "DELETE FROM $abookdb.$abook_table WHERE $abook_owner = '"
        . escape_sql_string($username,'TRUE')
        . "';\n"
  }

  open(ABOOK, "<$filepath") or 
    die "FILE READ ERROR: Could not open $filepath!!\n";

  while (my $line = <ABOOK> ) {

    chomp $line;
    my ( $nickname,$firstname,$lastname,$email,$label ) = split(/\|/, $line);

    print "INSERT INTO $abookdb.$abook_table "
        . "($abook_owner, $abook_nickname, $abook_firstname, $abook_lastname, $abook_email, $abook_label) "
        . "VALUES ('"
        . escape_sql_string($username) . "', '"
        . escape_sql_string($nickname) . "', '"
        . escape_sql_string($firstname) . "', '"
        . escape_sql_string($lastname) . "', '"
        . escape_sql_string($email) . "', '"
        . escape_sql_string($label) . "');\n"; 
  }

  close(ABOOK);
}

# Process a user preference file
sub pref {

  my ($filepath, $username) = @_;

  if ($verbose) { print STDERR "; PARSING PREFERENCE DATA FROM: $filepath\n"; }

  if ( defined $opts{delete} ) {
    print "DELETE FROM $prefdb.$pref_table "
        . "WHERE $pref_user = '"
        . escape_sql_string($username,'TRUE')
        . "' AND $pref_key NOT LIKE '\\_\\_\\_sig%\\_\\_\\_';\n"
  }

  open(PREFS, "<$filepath") or 
    die "FILE READ ERROR: Could not open $filepath!!\n";

  while (my $line = <PREFS> ) {

    chomp $line;
    my ( $prefkey, $prefval ) = split(/=/, $line, 2);

    print "INSERT INTO $prefdb.$pref_table "
        . "($pref_user, $pref_key, $pref_value) "
        . "VALUES ('"
        . escape_sql_string($username) . "', '"
        . escape_sql_string($prefkey) . "', '"
        . escape_sql_string($prefval) . "');\n"; 

  }

  close(PREFS);
}

# Process a user signature file
sub sig {

  my ($filepath, $username) = @_;

  if ($verbose) { print STDERR "; PARSING SIGNATURE DATA FROM: $filepath\n"; }

  my $del_ext = $1;  
  $del_ext = "nature" if ( $del_ext eq "g" );

  if ( defined $opts{delete} ) {
    print "DELETE FROM $prefdb.$pref_table "
        . "WHERE $pref_user = '"
        . escape_sql_string($username,'TRUE')
        . "' AND $pref_key = '___sig"
        . escape_sql_string($del_ext,'TRUE')
        . "___';\n"
  }

  open(SIG, "<$filepath") or 
    die "FILE READ ERROR: Could not open $filepath!!\n";

  my  @lines = <SIG>;
  close(SIG);

  $filepath =~ /.*\.si([g,\d])$/;
  my $prefkey = "___sig";
  if ( $1 eq "g" ) {
    $prefkey .= "nature___";
  } else {
    $prefkey .= "$1___";
  }

  print "INSERT INTO $prefdb.$pref_table ($pref_user, $pref_key, $pref_value) "
      . "VALUES ('"
      . escape_sql_string($username) . "', '"
      . escape_sql_string($prefkey) . "', '"
      . escape_sql_string(join("", @lines)) . "');\n";
}

# Escapes sql strings
# MySQL escaping:
#  http://dev.mysql.com/doc/refman/5.0/en/string-syntax.html
#  full - \x00 (null), \n, \r, \, ', " and \x1a (Control-Z) 
#         add % and _ in pattern matching expressions. 
#  short - only character used for quoting and backslash should be escaped
# PostgreSQL
# Oracle
# Sybase - different quoting of '
sub escape_sql_string() {
  my ($str,$isPattern) = @_;

  if ($dbtype eq 'mysql'){
    # escape \, ' and "
    $str =~ s/(['"\\])/\\$1/g;
    # escape \x1a
    $str =~ s/([\x1a])/\\Z/g;
    # escape ascii null
    $str =~ s/([\x00])/\\0/g;
    # escape line feed
    $str =~ s/([\n])/\\n/g;
    # escape cr
    $str =~ s/([\r])/\\r/g;
    if ($isPattern) {
      $str =~ s/([%_])/\\$1/g;
    }
  } else {
    die "ERROR: Unsupported database type";
  }
  return $str;
}


# Print out the usage screen
sub Usage {

$0 =~ /.*\/(.*)/;
my $prog = $1;
  print <<EOL;

This program generates SQL statements that aid in importing
SquirrelMail user configuration settings from files to
those in a database.

WARNING: this script is experimental.  We recommend that
you not use it when logged in as a privileged user (such
as root).  Also, ALWAYS back up your data directory before
using this script.

Usage: $prog --data_dir=<path to data directory> \\
                   [--delete] \\
                   [--abook] [--sig] [--pref] \\
                   [--user=<username1[,username2[,username3]...]>] \\
                   [--abookdb=<database>] \\
                   [--abook_table=<table name>] \\
                   [--abook_owner=<field name>] \\
                   [--abook_nickname=<field name>] \\
                   [--abook_firstname=<field name>] \\
                   [--abook_lastname=<field name>] \\
                   [--abook_email=<field name>] \\
                   [--abook_label=<field name>] \\
                   [--prefdb=<database>] \\
                   [--pref_table=<table name>] \\
                   [--pref_user=<field name>] \\
                   [--pref_key=<field name>] \\
                   [--pref_value=<field name>] \\
                   [--verbose] [-v]
                   [--help] [-h]

When none of --abook, --sig or --pref is specified, all three
will be assumed.

If --user is not specified, data for all users will be extracted.

--data_dir        is not optional and must define the path to the
                  SquirrelMail data directory.  If it is not given,
                  this help message is displayed.

--delete          causes the inclusion of SQL statements that remove all
                  previous setting values from the database for each user.
                  This setting obeys --user, --abook, --pref and --sig.
                  This setting is useful when re-importing settings.
         
--abook           causes the inclusion of SQL statements that import user
                  address book data.

--sig             causes the inclusion of SQL statements that import user
                  (email) signature data.

--pref            causes the inclusion of SQL statements that import all
                  other general user preference data.

--user            can be used to limit the users for which to extract data.
                  One or more (comma-separated) usernames can be given.

--abookdb         can be used to specify a custom database name for the
                  address book database.  If not given, "squirrelmail"
                  is used.

--abook_table     can be used to specify a custom address book table
                  name.  If not given, "address" is used.

--abook_owner     can be used to specify a custom field name for the
                  "owner" field in the address book database table
                  (the username goes in this field).  If not given,
                  "owner" is used.

--abook_nickname  can be used to specify a custom field name for the
                  "nickname" field in the address book database table.
                  If not given, "nickname" is used.

--abook_firstname can be used to specify a custom field name for the
                  "firstname" field in the address book database table.
                  If not given, "firstname" is used.

--abook_lastname  can be used to specify a custom field name for the
                  "lastname" field in the address book database table.
                  If not given, "lastname" is used.

--abook_email     can be used to specify a custom field name for the
                  email field in the address book database table
                  (the actual email address goes in this field).  If
                  not given, "email" is used.

--abook_label     can be used to specify a custom field name for the
                  "label" field in the address book database table.
                  If not given, "label" is used.

--prefdb          can be used to specify a custom database name for the
                  user preferences database.  If not given, "squirrelmail"
                  is used.

--pref_table      can be used to specify a custom preference table
                  name.  If not given, "userprefs" is used.

--pref_user       can be used to specify a custom field name for the
                  "user" field in the preferences database table
                  (the username goes in this field).  If not given,
                  "user" is used.

--pref_key        can be used to specify a custom field name for the
                  key field in the preferences database table (the
                  preference name goes in this field).  If not given,
                  "prefkey" is used.

--pref_value      can be used to specify a custom field name for the
                  value field in the preferences database table
                  (the preference value goes in this field).  If not
                  given, "prefval" is used.

--verbose         Displays extra diagnostic output on STDERR.  If you
                  redirect standard output to a file, verbose output
                  will not interfere with other normal output.

-v                Same as --verbose.

--help            Displays this usage information.

-h                Same as --help.

EOL
  exit 1;
}
