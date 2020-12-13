#!/bin/bash

find vendor -type d | \
grep -iE "/\.?(demo|doc|docs|example|examples|test|tests|github|circleci|travis)$" | \
xargs rm -r

find vendor -type f | \
grep -iE "/\.?(readme|changelog|faq|contributing|history|upgrading|upgrade|package|composer|travis|phpunit|psalm|phpmd|scrutinizer|coveralls|gush|phpstorm)\.[^/]+$" | \
xargs rm -r


find vendor -type f | \
grep -iE "/\.?(php_cs|phpstan|gitignore|gitattributes|editorconfig)[^/]+$" | \
xargs rm -r
