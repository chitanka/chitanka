<?php

namespace App\Legacy;


class Setup {

	static private
		$setupDone = false,
		$config = null;

	static private
		/** @var Request */      $request,
		/** @var mlDatabase */   $db,
		/** @var OutputMaker */  $outputMaker;




	static public function getPage($name, $controller, $container, $execute = true)
	{
		self::doSetup($container);

		$class = 'App\Legacy\\'.$name.'Page';
		$page = new $class(array(
			'request'    => self::request(),
			'db'         => self::db(),
			'out'        => self::outputMaker(),
			'controller' => $controller,
			'container' => $container,
			//'newRequest' => $controller->get('request'),
			'user'       => $controller->getUser(),
			'logDir' => __DIR__ . '/../../../../app/logs',
		));

		if ($execute) {
			$page->execute();
		}

		return $page;
	}



	static public function doSetup($container)
	{
		if ( self::$setupDone ) {
			return;
		}

		self::$config = $container;

		self::defineConstants();

		ini_set('date.timezone', self::setting('default_timezone'));

		self::$setupDone = true;
	}


	static public function defineConstants()
	{
		define('BASEDIR', __DIR__ . '/../../../../web'); // TODO remove

		define('SESSION_NAME', 'mls');

		self::defineDbTableConsts();
		$admin_email = self::setting('admin_email');
		list($email, $admin) = each($admin_email);
		define('ADMIN', $admin);
		define('ADMIN_EMAIL', $email);
		define('SITENAME', self::setting('sitename'));
		define('SITE_EMAIL', self::setting('site_email'));
	}


	static public function setting($settingName)
	{
		return self::$config->getParameter($settingName);
	}

	static public function request()
	{
		return self::setupRequest();
	}


	static public function db()
	{
		return self::setupDb();
	}

	static public function outputMaker($forceNew = false)
	{
		return self::setupOutputMaker($forceNew);
	}


	static private function setupDb()
	{
		if ( ! isset(self::$db) ) {
			$conn = self::$config->get('doctrine.dbal.default_connection');
			self::$db = new mlDatabase($conn->getHost(), $conn->getUsername(), $conn->getPassword(), $conn->getDatabase());
		}
		return self::$db;
	}


	static private function setupRequest()
	{
		if ( ! isset(self::$request) ) {
			self::$request = new Request();
		}
		return self::$request;
	}


	static private function setupOutputMaker($forceNew)
	{
		if ( $forceNew || ! isset(self::$outputMaker) ) {
			self::$outputMaker = new OutputMaker();
		}
		return self::$outputMaker;
	}


	static private function defineDbTableConsts($prefix = '')
	{
		$tables = array(
			'AUTHOR_OF'     => 'text_author',
			'BOOK'          => 'book',
			'BOOK_AUTHOR'   => 'book_author',
			'BOOK_TEXT'     => 'book_text',
			'COMMENT'       => 'text_comment',
			'DL_CACHE'      => 'download_cache',
			'DL_CACHE_TEXT' => 'download_cache_text',
			'EDIT_HISTORY'  => 'text_revision',
			'HEADER'        => 'header',
			'LABEL'         => 'label',
			'LABEL_LOG'     => 'label_log',
			'LICENSE'       => 'license',
			'PERSON'        => 'person',
			'QUESTION'      => 'question',
			'READER_OF'     => 'user_text_read',
			'SER_AUTHOR_OF' => 'series_author',
			'SERIES'        => 'series',
			'TEXT'          => 'text',
			'TEXT_LABEL'    => 'text_label',
			'TEXT_RATING'   => 'text_rating',
			'TRANSLATOR_OF' => 'text_translator',
			'USER'          => 'user',
			'USER_TEXT'     => 'user_text_contrib',
			'WORK'          => 'work_entry',
			'WORK_MULTI'    => 'work_contrib',
		);
		foreach ($tables as $constant => $table) {
			define('DBT_' . $constant, $prefix . $table);
		}
	}

}
