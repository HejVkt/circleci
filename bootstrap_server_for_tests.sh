apt-get install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install -y php7.1 git curl php7.1-mbstring php7.1-gd php7.1-simplexml php7.1-dom php7.1-curl php7.1-zip php7.1-pgsql

sudo apt-key adv --keyserver keyserver.ubuntu.com --recv 68576280
sudo apt-add-repository "deb https://deb.nodesource.com/node_8.x $(lsb_release -sc) main"
sudo apt-get update
sudo apt-get install -y nodejs
npm install -g yarn

curl -sS https://getcomposer.org/installer -o composer-setup.php
php -r "if (hash_file('SHA384', 'composer-setup.php') === '669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt/ $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'; \
sudo apt-get install -y wget ca-certificates; \
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -; \
sudo apt-get update; \
sudo apt-get install -y postgresql-9.6

sudo -u postgres psql
CREATE ROLE tests WITH CREATEDB LOGIN PASSWORD 'tests';
CREATE DATABASE tests OWNER tests;
CREATE DATABASE tests0 OWNER tests;
CREATE DATABASE tests1 OWNER tests;
CREATE DATABASE tests2 OWNER tests;
CREATE DATABASE tests3 OWNER tests;
CREATE DATABASE tests4 OWNER tests;
CREATE DATABASE tests5 OWNER tests;
CREATE DATABASE tests6 OWNER tests;
CREATE DATABASE tests7 OWNER tests;
CREATE DATABASE tests78 OWNER tests;

wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | sudo apt-key add -
sudo sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google-chrome.list'
sudo apt-get update
sudo apt-get install -y google-chrome-stable

adduser php
sudo -iu php
ssh-keygen
cat /home/php/.ssh/id_rsa.pub

git clone git@github.com:IT-swap/pmo-portal.git
cd pmo-portal

cp phpunit.xml phpunit_tests.xml

sed -i 's#postgres://webapp:secret@localhost:5744/webapp?sslmode=disable#postgres://tests:tests@localhost:5432/tests?sslmode=disable#' phpunit_tests.xml
composer global require hirak/prestissimo
composer install
npm install yarn
./node_modules/yarn/bin/yarn
# time npm run dev # watch on supervisor?
./vendor/bin/phpunit -c phpunit_tests.xml

echo >> ./database/migrations/2014_10_12_000000_create_users_table.php
./vendor/bin/paratest -c phpunit_tests.xml -f --filter Feature
CIRCLECI=true ./vendor/bin/phpunit -c phpunit_tests.xml --filter Browser

apt-get install -y supervisor
service supervisor restart

echo -e '#!/bin/bash\nnpm run watch' > /home/php/watch.sh
chmod +x /home/php/watch.sh

echo "[program:watch]
command=/home/php/watch.sh
directory=/home/php/pmo-portal/
user=php
autostart=true
autorestart=true
stdout_logfile=/home/php/watch.log
redirect_stderr=true
" > /etc/supervisor/conf.d/watch.conf

supervisorctl reread
supervisorctl update
