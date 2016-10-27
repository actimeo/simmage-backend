#! /bin/sh

rm -rf .traces 
./vendor/bin/phpunit --stop-on-error src && ./vendor/bin/phpunit --stop-on-error tests
