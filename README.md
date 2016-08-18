[![Build Status](https://travis-ci.org/actimeo/simmage-backend.svg?branch=master)](https://travis-ci.org/actimeo/simmage-backend)

# simmage-backend
Variation v2 (SIMMAGE project) postgreSQL backend.

The backend is composed of a PostgreSQL database, divided in several schemas, and PL/PgSQL procedures.

For a normal usage, the database should not be accessed directly with SQL requests, but PL/PgSQL procedures should be used.

The PgProcedures (https://github.com/actimeo/pgproc) PHP module can be used to access PL/PgSQL procedures from PHP.

## Prerequisites:

- PostgreSQL server 9.1 or higher
- PHP 5.5 or higher

## Install

- get sources from github:

```sh
$ git clone https://github.com/actimeo/simmage-backend.git
$ cd simmage-backend
```

- Install necessary PHP modules
```sh
$ composer install
```

- In your favorite PostgreSQL server, create a new connection role

```sh
postgres$ psql
postgres=# create user simmage password 'apassword';
```

- copy `config.inc.php.sample` to `config.inc.php`
- `config.sh.sample` to `config.sh`
- edit these 2 files with your database information. **CAUTION: Provide a database name which does not exist on your server, or it will be erased!**
- run the following script, as postgres user:

```sh
$ sudo su postgres -c ./scripts/update.sh
```


## Run tests

```sh
./vendor/bin/phpunit src/
```
