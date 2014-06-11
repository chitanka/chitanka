<?php namespace App\Legacy;

use App\Util\Number;
use Buzz\Browser;

class Legacy {

	static private $types = array(
		// code => array(singular, plural)
		'anecdote' => array('Анекдот', 'Анекдоти'),
		'fable' => array('Басня', 'Басни'),
		'biography' => array('Биография', 'Биографии'),
		'dialogue' => array('Диалог', 'Диалози'),
		'docu' => array('Документалистика', 'Документалистика'),
		'essay' => array('Есе', 'Есета'),
		'interview' => array('Интервю', 'Интервюта'),
		'gamebook' => array('Книга игра', 'Книги игри'),
		'memo' => array('Мемоари/спомени', 'Мемоари/спомени'),
		'science' => array('Научен текст', 'Научни текстове'),
		'popscience' => array('Научнопопулярен текст', 'Научнопопулярни текстове'),
		'novelette' => array('Новела', 'Новели'),
		'ocherk' => array('Очерк', 'Очерци'),
		'shortstory' => array('Разказ', 'Разкази'),
		'review' => array('Рецензия', 'Рецензии'),
		'novel' => array('Роман', 'Романи'),
		'play' => array('Пиеса', 'Пиеси'),
		'letter' => array('Писмо', 'Писма'),
		'poetry' => array('Поезия', 'Поезия'),
		'poem' => array('Поема', 'Поеми'),
		'novella' => array('Повест', 'Повести'),
		'outro' => array('Послеслов', 'Послеслови'),
		'intro' => array('Предговор', 'Предговори'),
		'tale' => array('Приказка', 'Приказки'),
		'pritcha' => array('Притча', 'Притчи'),
		'travelnotes' => array('Пътепис', 'Пътеписи'),
		'speech' => array('Реч', 'Речи'),
		'article' => array('Статия', 'Статии'),
		'prosepoetry' => array('Лирика в проза', 'Лирика в проза'),
		'screenplay' => array('Сценарий', 'Сценарии'),
		'textbook' => array('Учебник', 'Учебници'),
		'feuilleton' => array('Фейлетон', 'Фейлетони'),
		'haiku' => array('Хайку', 'Хайку'),
		'jure' => array('Юридически текст', 'Юридически текстове'),
		'critique' => array('Литературна критика', 'Литературна критика'),
		'philosophy' => array('Философски текст', 'Философски текст'),
		'religion' => array('Религиозен текст', 'Религиозен текст'),
		'historiography' => array('Историография', 'Историография'),
		'collection' => array('Сборник', 'Сборник'),
		'other' => array('Разни', 'Разни'),
	);

	/**
	 * @param string $code
	 * @param bool $singular
	 * @return string
	 */
	public static function workType($code, $singular = true) {
		if ( !array_key_exists($code, self::$types) ) return '';
		return $singular ? self::$types[$code][0] : self::$types[$code][1];
	}

	/**
	 * @param bool $singular
	 * @return array
	 */
	public static function workTypes($singular = true) {
		$ntypes = array();
		foreach (self::$types as $code => $name) {
			$ntypes[$code] = $singular ? self::$types[$code][0] : self::$types[$code][1];
		}
		return $ntypes;
	}

	/**
	 * @param var $var
	 * @param mixed $value
	 */
	public static function fillOnEmpty(&$var, $value) {
		if ( empty($var) ) {
			$var = $value;
		}
	}

	public static function getMaxUploadSizeInMiB() {
		return Number::int_b2m(Number::iniBytes(ini_get('upload_max_filesize')));
	}

	/**
	 * @param string $url
	 * @param array $postData
	 * @return string
	 */
	public static function getFromUrl($url, array $postData = array()) {
		$ch = curl_init();

		$options = array(
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,    // return content
			CURLOPT_HEADER         => false,   // don't return headers
			CURLOPT_CONNECTTIMEOUT => 30,      // timeout on connect
			CURLOPT_TIMEOUT        => 60,      // timeout on response
			CURLOPT_USERAGENT      => 'Mylib (http://chitanka.info)',
			CURLOPT_FOLLOWLOCATION => true,
		);
		if ( ! empty($postData)) {
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $postData;
		}

		curl_setopt_array($ch, $options);
		$contents = curl_exec($ch);

		curl_close($ch);

		return $contents;
	}

	/**
	 * @param string $url
	 * @param int $cacheTime
	 * @return string
	 */
	public static function getFromUrlOrCache($url, $cacheTime = 0) {
		$id = md5($url);
		$action = 'url';

		if ( $cacheTime && CacheManager::cacheExists($action, $id, $cacheTime) ) {
			return CacheManager::getCache($action, $id);
		}

		$content = self::getFromUrl($url);
		if ( empty($content) ) {
			return '';
		}

		return CacheManager::setCache($action, $id, $content);
	}

	/**
	 * @param string $url
	 * @param \Buzz\Browser $browser
	 * @param int $cacheDays
	 * @return string
	 */
	public static function getMwContent($url, Browser $browser, $cacheDays = 7) {
		$id = md5($url);
		$action = 'info';

		if ( CacheManager::cacheExists($action, $id, $cacheDays) ) {
			return CacheManager::getCache($action, $id);
		}

		try {
			$response = $browser->get("$url?action=render", array('User-Agent: Mylib (http://chitanka.info)'));
			if ($response->isOk()) {
				$content = self::processMwContent($response->getContent(), $url);
				return CacheManager::setCache($action, $id, $content);
			}
		} catch (\RuntimeException $e) {
			return null;
		}

		return null;
	}

	/**
	 * @param string $content
	 * @param string $url
	 * @return string
	 */
	static protected function processMwContent($content, $url) {
		$up = parse_url($url);
		$server = "$up[scheme]://$up[host]";
		$content = strtr($content, array(
			'&nbsp;' => '&#160;',
			' href="/wiki/' => ' href="'.$server.'/wiki/',
		));
		$patterns = array(
			'/rel="[^"]+"/' => '',
			// images
			'| src="(/\w)|' => " src=\"$server$1",
		);
		$content = preg_replace(array_keys($patterns), array_values($patterns), $content);

		$content = sprintf('<div class="editsection">[<a href="%s?action=edit" title="Редактиране на статията">±</a>]</div>', $url) . $content;

		return $content;
	}

	/**
	 * @param string $pass
	 * @param int $loops
	 */
	public static function sha1_loop($pass, $loops = 1) {
		for ($i=0; $i < $loops; $i++) {
			$pass = sha1($pass);
		}

		return $pass;
	}

}
