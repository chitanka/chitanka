<?php namespace App\Legacy;

class Setup {

	private static
		$setupDone = false,
		$config = null;

	private static
		/** @var Request */      $request,
		/** @var mlDatabase */   $db,
		/** @var OutputMaker */  $outputMaker;
	private static $dbal;
	private static $parameters = [];

	public static function getPage($name, $controller, $container, array $repositories, array $parameters = [], $execute = true) {
		self::$parameters = $parameters;
		self::doSetup($container);

		$class = 'App\Legacy\\'.$name.'Page';
		$page = new $class([
			'request'    => self::request(),
			'db'         => self::db(),
			'out'        => self::outputMaker(),
			'controller' => $controller,
			'container'  => $container,
			'sfrequest'  => $container->get('request_stack')->getMasterRequest(),
			'user'       => $controller->getUser(),
			'logDir' => __DIR__ . '/../../var/log',
			'parameters' => $parameters,
		] + $repositories);

		if ($execute) {
			$page->execute();
		}

		return $page;
	}

	public static function doSetup($container) {
		if ( self::$setupDone ) {
			return;
		}

		self::$config = $container;

		self::defineConstants();

		ini_set('date.timezone', self::setting('default_timezone'));

		self::$setupDone = true;
	}

	public static function defineConstants() {
		define('BASEDIR', __DIR__ . '/../../web'); // TODO remove

		define('SESSION_NAME', 'mls');

		self::defineDbTableConsts();
		define('ADMIN', self::setting('admin'));
		define('ADMIN_EMAIL', self::setting('admin_email'));
		define('SITENAME', self::setting('sitename'));
		define('SITE_EMAIL', self::setting('site_email'));
	}

	/**
	 * @param string $settingName
	 */
	public static function setting($settingName) {
		return self::$parameters[$settingName];
	}

	public static function request() {
		return self::setupRequest();
	}

	public static function db() {
		return self::setupDb();
	}

	public static function dbal(): \Doctrine\DBAL\Connection {
		return self::$dbal ?? self::$dbal = self::$config->get('doctrine.dbal.default_connection');
	}

	public static function outputMaker($forceNew = false) {
		return self::setupOutputMaker($forceNew);
	}

	private static function setupDb() {
		if ( ! isset(self::$db) ) {
			self::$db = new mlDatabase(self::dbal());
		}
		return self::$db;
	}

	private static function setupRequest() {
		if ( ! isset(self::$request) ) {
			self::$request = new Request();
		}
		return self::$request;
	}

	/**
	 * @param bool $forceNew
	 */
	private static function setupOutputMaker($forceNew) {
		if ( $forceNew || ! isset(self::$outputMaker) ) {
			self::$outputMaker = new OutputMaker();
		}
		return self::$outputMaker;
	}

	private static function defineDbTableConsts() {
		define('DBT_AUTHOR_OF', 'text_author');
		define('DBT_BOOK', 'book');
		define('DBT_BOOK_AUTHOR', 'book_author');
		define('DBT_BOOK_TEXT', 'book_text');
		define('DBT_COMMENT', 'text_comment');
		define('DBT_DL_CACHE', 'download_cache');
		define('DBT_DL_CACHE_TEXT', 'download_cache_text');
		define('DBT_EDIT_HISTORY', 'text_revision');
		define('DBT_HEADER', 'header');
		define('DBT_LABEL', 'label');
		define('DBT_LICENSE', 'license');
		define('DBT_PERSON', 'person');
		define('DBT_QUESTION', 'question');
		define('DBT_READER_OF', 'user_text_read');
		define('DBT_SER_AUTHOR_OF', 'series_author');
		define('DBT_SERIES', 'series');
		define('DBT_TEXT', 'text');
		define('DBT_TEXT_LABEL', 'text_label');
		define('DBT_TEXT_RATING', 'text_rating');
		define('DBT_TRANSLATOR_OF', 'text_translator');
		define('DBT_USER', 'user');
		define('DBT_USER_TEXT', 'user_text_contrib');
		define('DBT_WORK', 'work_entry');
		define('DBT_WORK_MULTI', 'work_contrib');
	}

}
