
DESTDIR=/usr/local/bin

# Solaris
#CC=gcc
#CFLAGS=-I/usr/local/tools/bind920/bind/include -I/usr/local/tools/bind920/include
#LDFLAGS=-L/usr/local/tools/bind920/lib -llwres -lsocket -lnsl -lpthread

# Linux
CC=gcc
CFLAGS=
LDFLAGS= -lpthread -llwres

all: bulkquery

bulkquery: bulkquery.c
	$(CC) $(CFLAGS) -o bulkquery bulkquery.c $(LDFLAGS)

clean:
	rm bulkquery

install:
	cp bulkquery $(DESTDIR)/bulkquery
	chmod 755 $(DESTDIR)/bulkquery
