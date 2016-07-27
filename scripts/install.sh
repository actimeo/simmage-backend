#! /bin/sh

# Create database
psql -c "create user $DBUSER password '$DBPASS'" -U postgres
psql -c "CREATE DATABASE $DBNAME WITH ENCODING='UTF8' owner=$DBUSER" -U postgres
PGPASSWORD=$DBPASS psql $DBNAME -c "CREATE SCHEMA pgcrypto AUTHORIZATION $DBUSER;"
psql $DBNAME -c "CREATE EXTENSION pgcrypto WITH SCHEMA pgcrypto;" -U postgres

# Install schemas
FILES="pgproc/sql/*.sql pgproc/plpgsql/*.sql"
FILES="$FILES portal/sql/portal.sql portal/sql/comments.sql portal/sql/mainview_*.sql portal/sql/personview_*.sql portal/plpgsql/*.sql"
FILES="$FILES organ/sql/organ.sql organ/sql/comments.sql organ/plpgsql/*.sql"
FILES="$FILES login/sql/auth.sql login/sql/comments.sql login/plpgsql/*.sql"

echo 'Installing SQL from files:'
for i in $FILES; do 
    echo " - $i";
done
(echo 'BEGIN TRANSACTION; ' && cat $FILES && echo 'COMMIT; ' ) |  PGPASSWORD=$DBPASS PGOPTIONS="--client-min-messages=warning" psql -v ON_ERROR_STOP=1 -q -h localhost -U $DBUSER $DBNAME

#bash ./install.sh
#bash ./update.sh
echo "<?php \$pg_host = 'localhost'; \$pg_user = '$DBUSER'; \$pg_pass = '$DBPASS'; \$pg_database = '$DBNAME';" > config.inc.php
