#!/bin/env bash

TEMP=`getopt -o :e:i: --long env:,idc:  -- "$@"`
echo "start package"

if [ ! -d "output" ]; then
    rm -rf output
    mkdir output
fi

cp -r ./base ./output/
cp -r ./coder ./output/
cp -r ./plugin ./output/
cp -r ./tool ./output/
cp -r ./vendor ./output/

echo "end package"