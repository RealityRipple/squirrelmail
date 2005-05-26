#! /bin/make -f
#
# Copyright (c) 2005 The SquirrelMail Project Team
# This file is part of SquirrelMail webmail interface documentation.
#
# SquirrelMail is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# SquirrelMail is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with SquirrelMail; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#

# old multiple commands way
# COMMAND=sgml2

# new single command approach
COMMAND=linuxdoc --backend=

# rtf and check outputs can't process multiple files
SOURCE=admin.sgml user.sgml devel.sgml

SITEPATH="/home/groups/s/sq/squirrelmail/htdocs/manuals/"

all: admin user devel
	echo "all output formats are created!"

site:
	$(COMMAND)html $(SOURCE) \
	    --header=site_header.inc --footer=site_footer.inc \
	    --imagebuttons

html:
	$(COMMAND)$@ $(SOURCE)

check:
	$(COMMAND)$@ admin.sgml
	$(COMMAND)$@ user.sgml
	$(COMMAND)$@ devel.sgml

admin:
	$(COMMAND)txt $@.sgml
	$(COMMAND)html $@.sgml --split=2
	$(COMMAND)latex -o tex $@.sgml
	$(COMMAND)latex -o dvi $@.sgml
	$(COMMAND)latex -o ps $@.sgml
	$(COMMAND)latex -o pdf $@.sgml
	$(COMMAND)info $@.sgml
	$(COMMAND)lyx $@.sgml
	$(COMMAND)rtf $@.sgml
user:
	$(COMMAND)txt $@.sgml
	$(COMMAND)html $@.sgml --split=2
	$(COMMAND)latex -o tex $@.sgml
	$(COMMAND)latex -o dvi $@.sgml
	$(COMMAND)latex -o ps $@.sgml
	$(COMMAND)latex -o pdf $@.sgml
	$(COMMAND)info $@.sgml
	$(COMMAND)lyx $@.sgml
	$(COMMAND)rtf $@.sgml

devel:
	$(COMMAND)txt $@.sgml
	$(COMMAND)html $@.sgml --split=2
	$(COMMAND)latex -o tex $@.sgml
	$(COMMAND)latex -o dvi $@.sgml
	$(COMMAND)latex -o ps $@.sgml
	$(COMMAND)latex -o pdf $@.sgml
	$(COMMAND)info $@.sgml
	$(COMMAND)lyx $@.sgml
	$(COMMAND)rtf $@.sgml

install-site: clean site
	cp *.html *.png *.php $(SITEPATH)

install-packed: clean admin user devel
	tar -czf $(SITEPATH)admin-html.tar.gz admin*.html 
	tar -czf $(SITEPATH)user-html.tar.gz user*.html 
	tar -czf $(SITEPATH)devel-html.tar.gz devel*.html
	tar -czf $(SITEPATH)admin-rtf.tar.gz admin*.rtf 
	tar -czf $(SITEPATH)user-rft.tar.gz user*.rtf
	tar -czf $(SITEPATH)devel-rtf.tar.gz devel*.rtf
	gzip -c admin.txt > $(SITEPATH)admin.txt.gz
	gzip -c devel.txt > $(SITEPATH)devel.txt.gz
	gzip -c user.txt > $(SITEPATH)user.txt.gz
	gzip -c admin.tex > $(SITEPATH)admin.tex.gz
	gzip -c devel.tex > $(SITEPATH)devel.tex.gz
	gzip -c user.tex > $(SITEPATH)user.tex.gz
	gzip -c admin.dvi > $(SITEPATH)admin.dvi.gz
	gzip -c devel.dvi > $(SITEPATH)devel.dvi.gz
	gzip -c user.dvi > $(SITEPATH)user.dvi.gz
	gzip -c admin.ps > $(SITEPATH)admin.ps.gz
	gzip -c devel.ps > $(SITEPATH)devel.ps.gz
	gzip -c user.ps > $(SITEPATH)user.ps.gz
	gzip -c admin.info > $(SITEPATH)admin.info.gz
	gzip -c devel.info > $(SITEPATH)devel.info.gz
	gzip -c user.info > $(SITEPATH)user.info.gz
	gzip -c admin.lyx > $(SITEPATH)admin.lyx.gz
	gzip -c devel.lyx > $(SITEPATH)devel.lyx.gz
	gzip -c user.lyx > $(SITEPATH)user.lyx.gz
	cp admin.pdf devel.pdf user.pdf $(SITEPATH)
 
clean:
	rm -f *.txt *.html *.tex *.dvi *.ps *.pdf *.info *.lyx *.rtf
