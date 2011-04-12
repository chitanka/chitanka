<?php

namespace Chitanka\LibBundle\Legacy;


class Setup {

	private static
		$setupDone = false,
		$config = null;

	private static
		/** @var Request */      $request,
		/** @var mlDatabase */   $db,
		/** @var OutputMaker */  $outputMaker;




	public static function getPage($name, $controller, $container, $execute = true)
	{
		self::doSetup($container);

		$class = 'Chitanka\LibBundle\Legacy\\'.$name.'Page';
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



	public static function doSetup($container)
	{
		if ( self::$setupDone ) {
			return;
		}

		self::$config = $container;

		self::defineConstants();

		ini_set('date.timezone', self::setting('default_timezone'));

		self::$setupDone = true;
	}


	public static function defineConstants()
	{
		define('BASEDIR', __DIR__ . '/../../../../web'); // TODO remove

		define('SESSION_NAME', 'mls');

		self::defineDbTableConsts();
		$admin_email = self::setting('admin_email');
		list($admin, $email) = each($admin_email);
		define('ADMIN', $admin);
		define('ADMIN_EMAIL', $email);
		define('SITENAME', self::setting('sitename'));
		define('SITE_EMAIL', self::setting('site_email'));
	}


	public static function setting($settingName)
	{
		return self::$config->getParameter($settingName);
	}

	public static function request()
	{
		return self::setupRequest();
	}


	public static function db()
	{
		return self::setupDb();
	}

	public static function outputMaker($forceNew = false)
	{
		return self::setupOutputMaker($forceNew);
	}


	private static function setupDb()
	{
		if ( ! isset(self::$db) ) {
			$conn = self::$config->get('doctrine.dbal.default_connection');
			self::$db = new mlDatabase($conn->getHost(), $conn->getUsername(), $conn->getPassword(), $conn->getDatabase());

// 			if (isset($slave_server)) {
// 				self::$db->setSlave($slave_server, $slave_user, $slave_pass, $slave_name);
// 			}
//
// 			if (isset($master_server)) {
// 				self::$db->setMaster($master_server, $master_user, $master_pass, $master_name);
// 			}
		}
		return self::$db;
	}


	private static function setupRequest()
	{
		if ( ! isset(self::$request) ) {
			self::$request = new Request();
		}
		return self::$request;
	}


	private static function setupOutputMaker($forceNew)
	{
		if ( $forceNew || ! isset(self::$outputMaker) ) {
			self::$outputMaker = new OutputMaker();
		}
		return self::$outputMaker;
	}


	private static function defineDbTableConsts($prefix = '')
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
