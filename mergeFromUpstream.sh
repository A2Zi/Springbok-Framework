git checkout master
git fetch upstream
git merge upstream/master
git push
sudo su www-data -c "php ../enhance_cli.php"
git checkout a2zi
git merge master
git push
