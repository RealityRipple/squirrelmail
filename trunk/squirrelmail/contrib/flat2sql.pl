#!/usr/bin/perl
#
# Converts file based preferences into SQL statements.
#
# WARNING: this script is experimental. Don't use it as
# privileged user or backup your data directory before using it.
#
# Copyright (c) 2002, Michael Blandford and Tal Yardeni
# Copyright (c) 2005-2006 The SquirrelMail Project Team
#
# This script is licensed under GPL.
# $Id$
#

##### Default values #####
$db = "squirrelmail";
$abook_table = "address";
$pref_table = "userprefs";
$dbtype = 'mysql';
##### ##### #####

use Getopt::Long;

&GetOptions( \%opts, qw( abook data_dir:s delete h help pref sig user:s db:s pref_table:s abook_table:s) );

&Usage if ( defined $opts{h} or defined $opts{help} );

unless ( defined $opts{abook} or defined $opts{pref} or defined $opts{sig}) {
    $opts{abook}=TRUE;
    $opts{pref}=TRUE;
    $opts{sig}=TRUE;
}


if ( defined $opts{db} and $opts{db} ) {
    $db = $opts{db};
}
if ( defined $opts{pref_table} and $opts{pref_table} ) {
    $pref_table = $opts{pref_table};
}
if ( defined $opts{abook_table} and $opts{abook_table}) {
    $abook_table = $opts{abook_table};
}

# Get data directory option and display help if it is not defined
if ( defined $opts{data_dir} and $opts{data_dir} ) {
    $data_dir = $opts{data_dir};
} else {
    &Usage;
}

# Are we looking for specific users or all users?
# There has to be a better way to do this - Below
 @user_list = split ( /,/, $opts{user} ) if defined $opts{user};

# Here we go
# If no arguments are passed, and we cant open the dir, we should
# get a usage.
opendir(DIR, $data_dir) or 
  die "DIRECTORY READ ERROR: Could not open $data_dir!!\n";

while ( $filename = readdir DIR ) {
  next if ( $filename eq "." or $filename eq ".." );
  $filename =~ /(.*)\.(.*)/;
  $username = $1;

  # Deal with the people
  # There has to be a better way to do this - Above
  next if ( defined $opts{user} and grep(!/$username/, @user_list));

  # Deal with the extension files
  $ext = $2;
  next unless $ext;
  &abook if ( $ext eq "abook" and defined $opts{abook} );
  &pref  if ( $ext eq "pref"  and defined $opts{pref}  ); 
  &sig if ( $ext =~ /si([g\d])$/ and defined $opts{sig});
}
closedir ( DIR );

# All done.  Below are functions

# Process a user address file

sub abook {
  print "DELETE FROM $db.$abook_table WHERE owner = '".escape_sql_string($username,true)."';\n"
    if ( defined $opts{delete} );

  open(ABOOK, "<$data_dir/$filename") or 
    die "FILE READ ERROR: Could not open $filename!!\n";

  while (my $line = <ABOOK> ) {

    chomp $line;
    my ( $nickname,$firstname,$lastname,$email,$label ) = split(/\|/, $line);

    print "INSERT INTO $db.$abook_table "
        . "(owner,nickname,firstname,lastname,email,label) "
        . "VALUES ('"
        .escape_sql_string($username)."','"
        .escape_sql_string($nickname)."','"
        .escape_sql_string($firstname)."','"
        .escape_sql_string($lastname)."','"
        .escape_sql_string($email)."','"
        .escape_sql_string($label)."');\n"; 
  }

  close(ABOOK);
}

# Process a user preference file
sub pref {
  print "DELETE FROM $db.$pref_table "
    . "WHERE user = '".escape_sql_string($username,true)."' and prefkey not like '___sig\%___';\n"
    if ( defined $opts{delete} );

  open(PREFS, "<$data_dir/$filename") or 
    die "FILE READ ERROR: Could not open $filename!!\n";

  while (my $line = <PREFS> ) {

    chomp $line;
    my ( $prefkey, $prefval ) = split(/=/, $line);

    print "INSERT INTO $db.$pref_table "
        . "(user,prefkey,prefval) "
        . "VALUES ('"
        .escape_sql_string($username)."','"
        .escape_sql_string($prefkey)."','"
        .escape_sql_string($prefval)."');\n"; 

  }

  close(PREFS);
}

# Process a user signature file
sub sig {

  $del_ext = $1;  
  $del_ext = "nature" if ( $del_ext eq "g" );
  print "DELETE FROM $db.$pref_table "
    . "WHERE user = '".escape_sql_string($username,true)."' and prefkey like '___sig" . escape_sql_string($del_ext,true) . "___';\n"
      if ( defined $opts{delete} );

  open(SIG, "<$data_dir/$filename") or 
    die "FILE READ ERROR: Could not open $filename!!\n";

  my  @lines = <SIG>;
  close(SIG);

  $filename =~ /.*\.si([g,\d]$)/;
  $prefkey = "___sig";
  if ( $1 eq "g" ) {
    $prefkey .= "nature___";
  } else {
    $prefkey .= "$1___";
  }

  print "INSERT INTO $db.$pref_table (user,prefkey,prefval) "
     . "VALUES ('".escape_sql_string($username)."','"
     .escape_sql_string($prefkey)."','"
     .escape_sql_string(join("", @lines))."');\n";
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
    $str =~ s/([\x0])/\\0/g;
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
$prog = $1;
  print <<EOL;

This program generates SQL statements to aid importing SquirrelMail 
user config into a database.

WARNING: this script is experimental. Don't use it as
privileged user or backup your data directory before using it.

Usage: $prog --data_dir=<data_dir> [--delete] [--abook] [--pref] [--sig]  
  [--user=<username0[,username1[,username2]...]]
  [--db=<database>] [--pref_table=<userprefs>] [--abook_table=<address>]

--data_dir option must define path to SquirrelMail data directory. If
option is not defined, script displays this help message.

--abook option is used to generate SQL with address books.
--pref option is used to generate SQL with user preferences.
--sig option is used to generate SQL with signatures.

--db option can be used to set database name. Script defaults to 
'squirrelmail'.

--pref_table option can be used to set preference table name. Script
defaults to 'userprefs'.

--abook_table option can be used to set address book table name. Script
defaults to 'address'.

Prefs --abook, --pref, and --sig are assumed if none of them as passed

--delete removes all previous values for users ( --users=<> ) already in 
the database.  This is useful to reimport users.  
It respects --abook, --pref, and --sig.

If --user is not specified, script extracts all user data.

EOL
  exit 1;
}
