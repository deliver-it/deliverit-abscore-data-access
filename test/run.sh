#!/bin/bash

BASEDIR="$(dirname "$0")"
binary="phpunit"

RANDOMIZER=false

if [ $# -ge 1 ]; then
    if [ "$1" == "--random" ]; then
        binary="phpunit-randomizer"
        RANDOMIZER=true
        shift 1

    fi
fi
phpunit="$BASEDIR/../vendor/bin/$binary";

if [ ! -f "$phpunit" ]; then
    echo "phpunit is not found at '$phpunit'" 1>&2
    exit 1
fi
echo $phpunit
echo --coverage-html "$BASEDIR/report" -c "$BASEDIR/phpunit.xml.dist" --color $(if $RANDOMIZER; then echo "--order rand"; fi) $* $BASEDIR | xargs $phpunit

