#!/bin/sh
# récupère les modifs de l'upstream et les merge sur la branche a2zi
git checkout master
git fetch upstream
git merge upstream/master
cd .. && sudo su www-data -c "php enhance_cli.php"
cd src
git checkout a2zi
./pullAndMerge.sh
