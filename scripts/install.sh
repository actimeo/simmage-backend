#! /bin/sh

# Create database
psql -p $DBPORT -c "create user $DBUSER password '$DBPASS'" -U postgres
psql -p $DBPORT -c "CREATE DATABASE $DBNAME WITH ENCODING='UTF8' owner=$DBUSER" -U postgres
PGPASSWORD=$DBPASS psql -p $DBPORT $DBNAME -c "CREATE SCHEMA pgcrypto AUTHORIZATION $DBUSER;"
psql -p $DBPORT $DBNAME -c "CREATE EXTENSION pgcrypto WITH SCHEMA pgcrypto;" -U postgres

# Install schemas
PGBASE=vendor/actimeo/pgproc/src
BASE=src

. scripts/files.inc

echo 'Installing SQL from files:'
for i in $FILES; do 
    echo " - $i";
done
(echo 'BEGIN TRANSACTION; ' && cat $FILES && echo 'COMMIT; ' ) |  PGPASSWORD=$DBPASS PGOPTIONS="--client-min-messages=warning" psql -p $DBPORT -v ON_ERROR_STOP=1 -q -h localhost -U $DBUSER $DBNAME

#bash ./install.sh
#bash ./update.sh
echo "<?php \$pg_host = 'localhost'; \$pg_user = '$DBUSER'; \$pg_pass = '$DBPASS'; \$pg_database = '$DBNAME'; \$pg_port = '$DBPORT';" > config.inc.php
