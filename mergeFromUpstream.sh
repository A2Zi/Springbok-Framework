#!/bin/sh
# récupère les modifs de l'upstream et les merge sur la branche a2zi
git pull
git checkout master
git pull
git fetch upstream
git merge upstream/master
git push
cd .. && sudo su www-data -c "php enhance_cli.php"
cd src
git checkout a2zi
git merge master
git push
