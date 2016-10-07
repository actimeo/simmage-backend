#! /bin/bash
# to be run locally, as postgres user

function err {
  echo 'ERROR: '$1;
  exit 1;
}

# you can use: sudo su postgres -c ./scripts/update.sh
[ $(whoami) = 'postgres' ] || err "must be postgres (try: sudo su postgres -c $0)"

[ -e config.sh ] && . config.sh || err 'config.sh not found';

psql <<EOF
DROP DATABASE IF EXISTS $DBNAME;
CREATE DATABASE $DBNAME WITH ENCODING='UTF8' OWNER=$DBUSER;
EOF

PGPASSWORD=$DBPASS psql $DBNAME <<EOF
CREATE SCHEMA pgcrypto AUTHORIZATION $DBUSER;
CREATE EXTENSION pgcrypto WITH SCHEMA pgcrypto;
EOF

# Install schemas
PGBASE=vendor/actimeo/pgproc/src
BASE=src
FILES="$PGBASE/sql/*.sql $PGBASE/plpgsql/*.sql"
FILES="$FILES $BASE/portal/sql/portal.sql $BASE/portal/sql/mainview_*.sql $BASE/portal/sql/personview_*.sql $BASE/portal/plpgsql/*.sql $BASE/portal/sql/comments.sql"
FILES="$FILES $BASE/organ/sql/organ.sql $BASE/organ/plpgsql/*.sql $BASE/organ/sql/comments.sql"
FILES="$FILES $BASE/login/sql/auth.sql $BASE/login/plpgsql/*.sql $BASE/login/sql/comments.sql"
FILES="$FILES $BASE/events/sql/eventtype.sql $BASE/events/sql/eventsviews.sql $BASE/events/plpgsql/*.sql $BASE/events/sql/comments.sql"
FILES="$FILES $BASE/pgdoc/sql/schema.sql $BASE/pgdoc/plpgsql/*.sql"

echo 'Installing SQL from files:'
for i in $FILES; do 
    echo " - $i";
done
(echo 'BEGIN TRANSACTION; ' && cat $FILES && echo 'COMMIT; ' ) |  PGPASSWORD=$DBPASS PGOPTIONS="--client-min-messages=warning" psql -v ON_ERROR_STOP=1 -q -h localhost -U $DBUSER $DBNAME

