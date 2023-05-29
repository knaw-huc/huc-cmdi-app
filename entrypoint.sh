#!/bin/bash

mkdir -p \
    /var/www/html/ccf/data/records/inprogress/ \
    /var/www/html/ccf/data/uploads \
    /var/www/html/ccf/smarty/templates_c

chmod uga+rwx \
    /var/www/html/ccf/data/uploads/ \
    /var/www/html/ccf/data/records/ \
    /var/www/html/ccf/data/records/inprogress/ \
    /var/www/html/ccf/smarty/templates_c

if [ ! -f /var/www/html/ccf/data/profiles/${PROFILE}.loaded ]; then
    sleep 10 # give mariadb some time to start up
    python3 /var/www/html/ccf/ccf-add-profile.py ${PROFILE} "${TITLE}"
    touch /var/www/html/ccf/data/profiles/${PROFILE}.loaded
    
    if [ -f /var/www/html/ccf/data/records/inprogress/records.sql ]; then
        cat /var/www/html/ccf/data/records/inprogress/records.sql | mysql "--host=${DB_SERVER}" "--user=${DB_USER}" "--password=${DB_PASSWD}" "--database=${DB_NAME}"
    fi
fi

apache2-foreground