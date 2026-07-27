#!/bin/bash

find vendor -type d | \
grep -iE "/\.?(demo|doc|docs|example|examples|test|tests|github|circleci|travis)$" | \
xargs --no-run-if-empty rm -r

find vendor -type f | \
grep -iE "/\.?(readme|changelog|faq|contributing|history|upgrading|upgrade|package|composer|travis|psalm|phpmd|scrutinizer|coveralls|gush|phpstorm)\.[^/]+$" | \
xargs --no-run-if-empty rm -r

find vendor -type f | \
grep -iE "/\.?(php_cs|phpstan|gitignore|gitattributes|editorconfig)[^/]+$" | \
xargs --no-run-if-empty rm -r

find vendor -type f | \
grep -iE "/(phpunit\.xml|phpunit\.xml\.dist)$" | \
xargs --no-run-if-empty rm -r
