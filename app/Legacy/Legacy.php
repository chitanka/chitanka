<?php namespace App\Legacy;

use App\Util\Number;

class Legacy {

	private static $types = [
		// code => array(singular, plural)
		'anecdote' => ['Анекдот', 'Анекдоти'],
		'fable' => ['Басня', 'Басни'],
		'biography' => ['Биография', 'Биографии'],
		'dialogue' => ['Диалог', 'Диалози'],
		'docu' => ['Документалистика', 'Документалистика'],
		'essay' => ['Есе', 'Есета'],
		'interview' => ['Интервю', 'Интервюта'],
		'gamebook' => ['Книга игра', 'Книги игри'],
		'memo' => ['Мемоари/спомени', 'Мемоари/спомени'],
		'science' => ['Научен текст', 'Научни текстове'],
		'popscience' => ['Научнопопулярен текст', 'Научнопопулярни текстове'],
		'novelette' => ['Новела', 'Новели'],
		'ocherk' => ['Очерк', 'Очерци'],
		'shortstory' => ['Разказ', 'Разкази'],
		'review' => ['Рецензия', 'Рецензии'],
		'novel' => ['Роман', 'Романи'],
		'play' => ['Пиеса', 'Пиеси'],
		'letter' => ['Писмо', 'Писма'],
		'poetry' => ['Поезия', 'Поезия'],
		'poem' => ['Поема', 'Поеми'],
		'novella' => ['Повест', 'Повести'],
		'outro' => ['Послеслов', 'Послеслови'],
		'intro' => ['Предговор', 'Предговори'],
		'tale' => ['Приказка', 'Приказки'],
		'pritcha' => ['Притча', 'Притчи'],
		'travelnotes' => ['Пътепис', 'Пътеписи'],
		'speech' => ['Реч', 'Речи'],
		'article' => ['Статия', 'Статии'],
		'prosepoetry' => ['Лирика в проза', 'Лирика в проза'],
		'screenplay' => ['Сценарий', 'Сценарии'],
		'textbook' => ['Учебник', 'Учебници'],
		'feuilleton' => ['Фейлетон', 'Фейлетони'],
		'haiku' => ['Хайку', 'Хайку'],
		'jure' => ['Юридически текст', 'Юридически текстове'],
		'critique' => ['Литературна критика', 'Литературна критика'],
		'philosophy' => ['Философски текст', 'Философски текст'],
		'religion' => ['Религиозен текст', 'Религиозен текст'],
		'historiography' => ['Историография', 'Историография'],
		'collection' => ['Сборник', 'Сборник'],
		'other' => ['Разни', 'Разни'],
	];

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
		$ntypes = [];
		foreach (self::$types as $code => $name) {
			$ntypes[$code] = $singular ? self::$types[$code][0] : self::$types[$code][1];
		}
		return $ntypes;
	}

	public static function getMaxUploadSizeInMiB() {
		return Number::int_b2m(Number::iniBytes(ini_get('upload_max_filesize')));
	}

	/**
	 * @param string $url
	 * @param array $postData
	 * @return string
	 */
	public static function getFromUrl($url, array $postData = []) {
		$ch = curl_init();

		$options = [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,    // return content
			CURLOPT_HEADER         => false,   // don't return headers
			CURLOPT_CONNECTTIMEOUT => 30,      // timeout on connect
			CURLOPT_TIMEOUT        => 60,      // timeout on response
			CURLOPT_USERAGENT      => 'Mylib (http://chitanka.info)',
			CURLOPT_FOLLOWLOCATION => true,
		];
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
