#!/bin/bash
# $Id$ 

# WARNING. In order to use it with squirrelmail, you should 
# modify phpDocumentor.ini and add .mod extension 
# to [_phpDocumentor_phpfile_exts] settings.

#/**
#  * makedoc - PHPDocumentor script to save your settings
#  * 
#  * Put this file inside your PHP project homedir, edit its variables and run whenever you wants to
#  * re/make your project documentation.
#  * 
#  * The version of this file is the version of PHPDocumentor it is compatible.
#  * 
#  * It simples run phpdoc with the parameters you set in this file.
#  * NOTE: Do not add spaces after bash variables.
#  *
#  * @copyright         makedoc.sh is part of PHPDocumentor project {@link http://freshmeat.net/projects/phpdocu/} and its LGPL
#  * @author            Roberto Berto <darkelder (inside) users (dot) sourceforge (dot) net>
#  * @version           Release-1.1.0
#  */


##############################
# should be edited
##############################

#/**
#  * title of generated documentation, default is 'Generated Documentation'
#  * 
#  * @var               string TITLE
#  */
TITLE="Squirrelmail Devel CVS Documentation"

#/** 
#  * name to use for the default package. If not specified, uses 'default'
#  *
#  * @var               string PACKAGES
#  */
PACKAGES="squirrelmail"

#/** 
#  * name of a directory(s) to parse directory1,directory2
#  * $PWD is the directory where makedoc.sh 
#  *
#  * @var               string PATH_PROJECT
#  */
PATH_PROJECT=$PWD

#/**
#  * path of PHPDoc executable
#  *
#  * @var               string PATH_PHPDOC
#  */
PATH_PHPDOC=/home/chilts/phpDocumentor-1.2.2/phpdoc

#/**
#  * where documentation will be put
#  *
#  * @var               string PATH_DOCS
#  */
PATH_DOCS=/var/www/smdocs

#/**
#  * what outputformat to use (html/pdf)
#  *
#  * @var               string OUTPUTFORMAT
#  */
OUTPUTFORMAT=HTML

#/** 
#  * converter to be used
#  *
#  * @var               string CONVERTER
#  */
#CONVERTER=Smarty
CONVERTER=frames

#/**
#  * template to use
#  *
#  * @var               string TEMPLATE
#  */
#TEMPLATE=default
TEMPLATE=earthli

#/**
#  * parse elements marked as private
#  *
#  * @var               bool (on/off)           PRIVATE
#  */
PRIVATE=off

#/**
#  * Ignore certain files (comma separated, wildcards enabled)
#  * @var               string IGNORE
#  */
IGNORE=CVS/,*.txt,contrib/,index.php

# make documentation
$PATH_PHPDOC -d $PATH_PROJECT -t $PATH_DOCS -ti "$TITLE" -dn $PACKAGES \
-o $OUTPUTFORMAT:$CONVERTER:$TEMPLATE -pp $PRIVATE -i $IGNORE


# vim: set expandtab :
