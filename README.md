[![Build Status](https://travis-ci.org/actimeo/simmage-backend.svg?branch=master)](https://travis-ci.org/actimeo/simmage-backend)

# simmage-backend
Variation v2 (SIMMAGE project) postgreSQL backend

## Install

- get sources from github:

```sh
$ git clone https://github.com/actimeo/simmage-backend.git
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
- edit these 2 files with your database information. **CAUTION: Provide a database name which does not exists on your server, or it will be erased**
- run the following script, as postgres user:

```sh
$ sudo su postgres -c ./scripts/update.sh
```


## Run tests

```sh
./vendor/bin/phpunit src/
```
