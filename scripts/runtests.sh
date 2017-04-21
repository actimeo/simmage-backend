#! /bin/bash

rm -rf .traces 
./vendor/bin/phpunit --stop-on-error src && ./vendor/bin/phpunit --stop-on-error tests
rc=$?
if [[ $rc != 0 ]]; then
    exit $rc
fi
#php ./scripts/import.php ./arrangement-test
./vendor/bin/phpunit --stop-on-error tests-arr
