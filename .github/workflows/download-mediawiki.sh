#! /bin/bash
set -ex

MW_BRANCH=$1
MW_REPO=$2

git clone https://github.com/"$MW_REPO"/mediawiki.git --depth=1 --branch="$MW_BRANCH"

cd mediawiki
composer update --prefer-dist --no-progress
