#!/bin/sh
git checkout master
git add -A
git commit -a
git push
git checkout a2zi
git merge master
git push
