<?php namespace App\Service;

use App\Entity\Text;
use App\Entity\User;
use App\Legacy\mlDatabase as LegacyDb;
use App\Util\String;

class TextService {

	private $legacyDb;

	public function __construct(LegacyDb $db) {
		$this->legacyDb = $db;
	}

	/**
	 * Get similar texts based ot readers count.
	 * @param Text $text
	 * @param int $limit   Return up to this limit number of texts
	 * @param User $reader Do not return texts marked as read by this reader
	 */
	public function findTextAlikes(Text $text, $limit = 10, User $reader = null) {
		$qa = [
			'SELECT'   => 'text_id, count(*) readers',
			'FROM'     => DBT_READER_OF .' r',
			'WHERE'    => [
				'r.text_id' => ['<>', $text->getId()],
				'r.user_id IN ('
					. $this->legacyDb->selectQ(DBT_READER_OF, ['text_id' => $text->getId()], 'user_id')
					. ')',
			],
			'GROUP BY' => 'r.text_id',
			'ORDER BY' => 'readers DESC',
		];
		if ( is_object($reader) ) {
			$qa['WHERE'][] = 'text_id NOT IN ('
				. $this->legacyDb->selectQ(DBT_READER_OF, ['user_id' => $reader->getId()], 'text_id')
				. ')';
		}
		$res = $this->legacyDb->extselect($qa);
		$alikes = $textsInQueue = [];
		$lastReaders = 0;
		$count = 0;
		while ( $row = $this->legacyDb->fetchAssoc($res) ) {
			$count++;
			if ( $lastReaders > $row['readers'] ) {
				if ( $count > $limit ) {
					break;
				}
				$alikes = array_merge($alikes, $textsInQueue);
				$textsInQueue = [];
			}
			$textsInQueue[] = $row['text_id'];
			$lastReaders = $row['readers'];
		}

		if ( $count > $limit ) {
			$alikes = array_merge($alikes, $this->filterSimilarByLabel($text, $textsInQueue, $limit - count($alikes)));
		}

// 		if ( empty($texts) ) {
// 			$texts = $this->getSimilarByLabel($text, $limit, $reader);
// 		}

		return $alikes;
	}

	public function buildTextHeadersUpdateQuery($fileOrString, $textId, $headlevel) {
		require_once __DIR__ . '/../Legacy/SfbParserSimple.php';

		$data = [];
		if (strpos($fileOrString, "\n") !== false) {
			$file = tempnam(sys_get_temp_dir(), 'chitanka-text-');
			file_put_contents($file, $fileOrString);
		} else {
			$file = $fileOrString;
		}
		foreach (\App\Legacy\makeDbRows($file, $headlevel) as $row) {
			$name = $row[2];
			$name = strtr($name, ['_' => '']);
			$name = $this->legacyDb->escape(String::my_replace($name));
			$data[] = [$textId, $row[0], $row[1], $name, $row[3], $row[4]];
		}
		if ($file != $fileOrString) {
			unlink($file);
		}
		$sql = [$this->legacyDb->deleteQ('text_header', ['text_id' => $textId])];
		if (!empty($data)) {
			$fields = ['text_id', 'nr', 'level', 'name', 'fpos', 'linecnt'];
			$sql[] = $this->legacyDb->multiinsertQ('text_header', $data, $fields);
		}

		return $sql;
	}

	/**
	 * Get similar texts based ot readers count.
	 * @param Text $text
	 * @param int $limit   Return up to this limit number of texts
	 * @param User $reader  Do not return texts marked as read by this reader
	 */
	private function getSimilarByLabel(Text $text, $limit = 10, User $reader = null) {
		$qa = [
			'SELECT'   => 'text_id',
			'FROM'     => DBT_TEXT_LABEL,
			'WHERE'    => [
				'text_id' => ['<>', $text->getId()],
				'label_id IN ('
					. $this->legacyDb->selectQ(DBT_TEXT_LABEL, ['text_id' => $text->getId()], 'label_id')
					. ')',
			],
			'GROUP BY' => 'text_id',
			'ORDER BY' => 'COUNT(text_id) DESC',
			'LIMIT'    => $limit,
		];
		if ( $reader ) {
			$qa['WHERE'][] = 'text_id NOT IN ('
				. $this->legacyDb->selectQ(DBT_READER_OF, ['user_id' => $reader->getId()], 'text_id')
				. ')';
		}
		$res = $this->legacyDb->extselect($qa);
		$texts = [];
		while ($row = $this->legacyDb->fetchRow($res)) {
			$texts[] = $row[0];
		}
		return $texts;
	}

	/**
	 * @param Text $text
	 * @param array $textIds
	 * @param int $limit   Return up to this limit number of texts
	 */
	private function filterSimilarByLabel(Text $text, $textIds, $limit) {
		$qa = [
			'SELECT'   => 'text_id',
			'FROM'     => DBT_TEXT_LABEL,
			'WHERE'    => [
				'text_id' => ['IN', $textIds],
				'label_id IN ('
					. $this->legacyDb->selectQ(DBT_TEXT_LABEL, ['text_id' => $text->getId()], 'label_id')
					. ')',
			],
			'GROUP BY' => 'text_id',
			'ORDER BY' => 'COUNT(text_id) DESC',
			'LIMIT'    => $limit,
		];
		$res = $this->legacyDb->extselect($qa);
		$texts = [];
		while ($row = $this->legacyDb->fetchRow($res)) {
			$texts[] = $row[0];
		}
		return $texts;
	}

}
