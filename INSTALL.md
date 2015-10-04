Дадените указания важат за операционната система ГНУ/Линукс.


1. Необходим софтуер
====================

Ето какво ще ви е необходимо, за да пуснете софтуера на Моята библиотека — chitanka:

 - Уеб сървър: Apache с PHP (версия на PHP >= 5.4) или [nginx](http://nginx.org/) с [PHP-FPM](http://php-fpm.org/)
 - MySQL сървър (версия >= 4.1)

При Apache трябва да са включени модулите `rewrite` и `expires`.

При PHP са нужни разширенията `gd`, `curl`, `xsl` и `intl`.


2. Изтегляне
============

Нужно е да разполагате с [git](http://git-scm.com/), за да може да клонирате хранилището на софтуера.

2.1. За огледало
----------------

За обикновено пускане на огледало на Моята библиотека ползвайте:

	git clone https://github.com/chitanka/chitanka-production.git chitanka

Ще получите нова директория chitanka. Нека се казва `/PATH/TO/chitanka`.

Сега копирайте файла `app/config/parameters.yml.dist` като `app/config/parameters.yml`.

След това много лесно може да актуализирате софтуера само чрез следните команди:

	cd /PATH/TO/chitanka && git pull

2.2. За разработчици
---------------------

Най-напред инсталирайте [composer](https://getcomposer.org/download/), ако все още не разполагате с него. Няма значение в коя директория ще сложите изпълнимия му файл composer.phar, но е препоръчително да е в някоя глобална директория, за да може да го използвате и за други проекти. По желание може да преименувате и самия файл, напр. `/usr/local/bin/composer`. Важно е да запомните къде се намира той, за да може да го извиквате след това.

Сега клонирайте хранилището на chitanka:

	git clone https://github.com/chitanka/chitanka.git

Ще получите нова директория chitanka. Нека се казва `/PATH/TO/chitanka`. След това изпълнете в конзолата:

	cd /PATH/TO/chitanka
	php /PATH/TO/composer.phar install

Това ще отнеме около десетина минути. В края ще се появи запитване за попълване на определени параметри. В скоби се намира стойността по подразбиране. Засега са важни само тези за базата от данни (database_xxx). За `database_driver` оставете `pdo_mysql`. При другите просто натиснете Enter.

Записаните параметри се намират във файла `app/config/parameters.yml` и могат да бъдат променяни по всяко време по-късно.

Последващите обновявания на софтуера могат да стават чрез:

	cd /PATH/TO/chitanka && git pull && php /PATH/TO/composer.phar update


3. Настройка
============

Сега е нужно да разрешите на софтуера (сървъра) да пише в директориите `var/cache`, `var/log`, `var/spool` и `web/cache`.

Това става най-лесно през командния ред:

	cd /PATH/TO/chitanka
	chmod -R a+w var/cache var/log var/spool web/cache

Ако разполагате и с файла със съдържанието на библиотеката (текстове, изображения), го разархивирайте в директорията /PATH/TO/chitanka/web:

	tar zxvf chitanka-content.tar.gz -C /PATH/TO/chitanka/web


4. База от данни
================

Първо си свалете [актуална версия на базата от данни на Моята библиотека](http://download.chitanka.info/chitanka.sql.gz).

След това създайте нова база от данни с име chitanka. Например така:

	mysql -u root -e "CREATE DATABASE chitanka"

Ако root има парола, ползвайте `mysql -u root -p`.

После вмъкнете съдържанието на файла `chitanka.sql.gz` в новата база:

	gunzip -c chitanka.sql.gz | mysql -u root chitanka

При желание може да създадете специален потребител с достъп само до тази база от данни.

Във файла `app/config/parameters.yml` е посочена конфигурацията за базата от данни. По подразбиране този файл съдържа:

	database_host:      localhost
	database_name:      chitanka
	database_user:      root
	database_password:  ~

Това ще рече, че базата от данни се намира на локалния компютър и се нарича chitanka. За достъп до нея ще се ползва потребителят root, който няма парола.

Ако решите да ползвате друга конфигурация, напр. root с парола или пък съвсем друг потребител, просто въведете нужните данни във файла `app/config/parameters.yml`.


5. Настройка на сървъра
=======================

Ето примерни конфигурации за Apache 2 и nginx. На мястото на /PATH/TO запишете съответните директории. За име на сървъра по-долу се ползва `chitanka.local`, но може да го смените с какъвто домейн пожелаете.

5.1. Apache 2
-------------

Настройте нов виртуален хост при апача (Apache 2), като добавите това в конфигурацията му:

	<VirtualHost *:80>
		DocumentRoot /PATH/TO/chitanka/web
		ServerName chitanka.local
		<Directory "/PATH/TO/chitanka/web">
			AllowOverride All
			Allow from All
		</Directory>
		LogLevel warn
		CustomLog /PATH/TO/LOG/chitanka.access.log common
		ErrorLog /PATH/TO/LOG/chitanka.error.log
	</VirtualHost>

5.2. nginx
----------

	server {
		listen 80;

		server_name chitanka.local;
		root /PATH/TO/chitanka/web;

		access_log /PATH/TO/LOG/chitanka.access.log;
		error_log /PATH/TO/LOG/chitanka.error.log;

		location / {
			index index.php;
			try_files $uri $uri/ /index.php$is_args$args;
		}

		location ~ /(index|index_dev)\.php($|/) {
			fastcgi_pass 127.0.0.1:9000;
			# or thru a unix socket
			#fastcgi_pass unix:/var/run/php5-fpm.sock;
			fastcgi_split_path_info ^(.+\.php)(/.*)$;
			include fastcgi_params;
		}

		location ~ /(css|js|thumb) {
			expires 1y;
			try_files /cache$request_uri @asset_generator;
		}
		location @asset_generator {
			rewrite ^/(css|js|thumb)/(.+) /$1/index.php?$2;
		}

		location ~* \.(eot|otf|ttf|woff)$ {
			add_header Access-Control-Allow-Origin *;
		}
	}

5.3. Настройка на домейна
-------------------------

После в [/etc/hosts](http://en.wikipedia.org/wiki/Hosts_%28file%29#Location_in_the_file_system) добавете следното:

	127.0.0.1	chitanka.local

Така домейнът `chitanka.local` ще се разпознава от системата ви.


6. Пускане
==========

Ако всичко е минало по план, отворете <http://chitanka.local> и разгледайте вашата версия на Моята библиотека.


7. Обновяване
=============

Автоматичното обновяване на софтуера и на съдържанието на библиотеката става чрез скрипта `bin/update`. Ето примерна конфигурация за [Cron](https://en.wikipedia.org/wiki/Cron), с която всеки ден в 0 часа ще се извършва обновяване на системата:

    0 0 * * *    /PATH/TO/chitanka/bin/update


8. Помощ с инсталацията
=======================

Ако имате проблеми с инсталацията или пък искате да помогнете за подобряването на тези указания, посетете страницата <http://wiki.chitanka.info/Install>.
