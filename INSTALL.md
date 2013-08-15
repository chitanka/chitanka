Всички указания са дадени така, сякаш ще се изпълняват върху операционната система ГНУ/Линукс. С времето ще се появят и още, нужно е само някой да седне и да ги напише. :-) В уикито на библиотека е създадена [специална страница](http://wiki.chitanka.info/Install), която ще служи като сборен пункт за всички указания по инсталацията.


1. Необходим софтуер
====================

Ето какво ще ви е необходимо, за да пуснете софтуера на Моята библиотека — chitanka:

 - Уеб сървър Apache с PHP (версия на PHP >= 5.3.2)
 - MySQL сървър (версия >= 4.1)

При Apache трябва да са включени модулите `rewrite` и `expires`.

При PHP са нужни разширенията `curl` и `xsl`.

2. Изтегляне
============

2.1. Клониране по git
---------------------

Ако разполагате с git, може да изтеглите софтуера чрез клониране:

	git clone git://gitorious.org/chitanka/chitanka-production.git

Ще получите нова директория chitanka. По-нататък ще я наричам `/PATH/TO/chitanka`.

Сега копирайте файла `app/config/parameters.yml.dist` като `app/config/parameters.yml`, а след това изпълнете в конзолата:

	/PATH/TO/chitanka/bin/vendors install

Това ще отнеме от една до няколко минути.

След това много лесно може да актуализирате софтуера само чрез следните команди:

	cd /PATH/TO/chitanka
	git pull


2.2. Изтегляне на торент
------------------------

_Тази стъпка се отнася за [торента с динамичната версия](http://forum.chitanka.info/chitanka-download-own-server-t3178.html)._

Разархивирайте файла chitanka.tar.gz в избрана от вас директория. В нея ще се създаде нова директория с име chitanka. По-надолу ще се обръщам към тази нова директория с името `/PATH/TO/chitanka`.


3. Настройка
============

Сега е нужно да разрешите на софтуера (сървъра) да пише в следните директории:
app/cache, app/logs, web/cache

Това става най-лесно през командния ред:

	cd /PATH/TO/chitanka
	chmod -R a+w app/cache app/logs web/cache

Ако разполагате и с файла със съдържанието на библиотеката (текстове, изображения), го разархивирайте в директорията /PATH/TO/chitanka/web:

	tar zxvf chitanka-content.tar.gz -C /PATH/TO/chitanka/web

ВНИМАНИЕ: Настоящия торент съдържа излишна структура на директориите, която започва от `var/www/chitanka/content`, затова накрая ще получите `/PATH/TO/chitanka/web/var/www/chitanka/content` вместо правилното `/PATH/TO/chitanka/web/content`. Налага се ръчно да преместите директорията content на нужното място — в директорията /PATH/TO/chitanka/web.


4. База от данни
================

_Тази стъпка се отнася за торента с динамичната версия._

Създайте нова база от данни с име chitanka. Например така:

	mysql -u root -e "CREATE DATABASE chitanka"

Ако root има парола, ползвайте `mysql -u root -p`.

После вмъкнете съдържанието на файла db.sql в новата база:

	mysql -u root chitanka < db.sql

При желание може да създадете специален потребител с достъп само да тази база от данни.

Във файла app/config/parameters.yml е посочена конфигурацията за базата от данни. По подразбиране този файл съдържа:

	database_host:      localhost
	database_name:      chitanka
	database_user:      root
	database_password:  ~

Това ще рече, че базата от данни се намира на локалния компютър и се нарича chitanka. За достъп до нея ще се ползва потребителя root, който няма парола.

Ако решите да ползвате друга конфигурация, напр. root с парола или пък съвсем друг потребител, просто въведете вашите данни във файла app/config/parameters.yml.

Ето [актуална версия на базата от данни на Моята библиотека](http://download.chitanka.info/chitanka.sql.gz).


5. Настройка на сървъра
=======================

Ето примерни конфигурации за Apache 2 и nginx:

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

На мястото на /PATH/TO запишете съответните директории.

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

		location ~ ^/index\.php(/|$) {
			fastcgi_pass 127.0.0.1:9000;
			# or thru a unix socket
			#fastcgi_pass unix:/var/run/php5-fpm.sock;
			fastcgi_split_path_info ^(.+\.php)(/.*)$;
			include fastcgi_params;
		}
	}

5.3. Настройка на домейна
-------------------------

После в [/etc/hosts](http://en.wikipedia.org/wiki/Hosts_%28file%29#Location_in_the_file_system) добавете следното:

	127.0.0.1	chitanka.local

Така домейнът `chitanka.local` ще се разпознава от системата ви.

Ако изберете да ползвате домейн, различен от `chitanka.local`, трябва да промените стандартната конфигурация на библиотеката. Отворете файла `app/config/parameters.yml` и заменете всички срещания на `chitanka.local` с името на избрания от вас домейн.


6. Пускане
==========

Ако всичко е минало по план, отворете <http://chitanka.local> и разгледайте вашата версия на Моята библиотека.


7. Помощ с инсталацията
=======================

Ако имате проблеми с инсталацията или пък искате да помогнете за подобряването на тези указания, посетете страницата <http://wiki.chitanka.info/Install>.
