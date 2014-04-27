<?php namespace App\Legacy;

use App\Entity\Text;

class TextratingPage extends TextPage {

	protected
		$action = 'textrating',
		$includeUserLinks = true;

	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Оценки';
		$this->textId = (int) $this->request->value( self::FF_TEXT_ID, 0, 1 );
		$this->username = $this->request->value('username');
	}

	protected function buildContent() {
		if ( ! empty( $this->username ) ) {
			return $this->makeListByUser();
		}
		return $this->makeListByText();
	}

	protected function makeListByText($limit = 0, $offset = 0) {
		$this->initData();
		if ( !is_object($this->work) ) {
			return '';
		}
		$qa = array(
			'SELECT' => 'tr.rating, tr.date, u.username',
			'FROM' => DBT_TEXT_RATING .' tr',
			'LEFT JOIN' => array(
				DBT_USER .' u' => 'tr.user_id = u.id',
			),
			'WHERE' => array('tr.text_id' => $this->textId),
			'ORDER BY' => "tr.date DESC",
			'LIMIT' => array($offset, $limit),
		);

		$this->data = array();
		$this->db->iterateOverResult(
			$this->db->extselectQ($qa), 'makeListByTextItem', $this);
		$this->title = 'Оценки за ' . $this->makeTextLinkWithAuthor($this->work);

		if ( empty($this->data) ) {
			return '<p class="no-items">Няма дадени оценки.</p>';
		}

		$this->data = array_merge(array(
			array(
				array( array('type' => 'header'), 'Дата'),
				array( array('type' => 'header'), 'Потребител'),
				array( array('type' => 'header'), 'Оценка'),
			)
		), $this->data);

		return $this->out->simpleTable($this->title, $this->data);
	}

	public function makeListByTextItem($dbrow) {
		$this->data[] = array(
			Legacy::humanDate($dbrow['date']),
			$this->includeUserLinks ? $this->makeUserLink($dbrow['username']) : $dbrow['username'],
			$dbrow['rating']
		);
	}

	protected function makeListByUser($limit = 0, $offset = 0) {
		$qa = array(
			'SELECT' => 'GROUP_CONCAT(a.name ORDER BY aof.pos) author,
				t.id textId, t.title, tr.rating, tr.date',
			'FROM' => DBT_TEXT_RATING .' tr',
			'LEFT JOIN' => array(
				DBT_TEXT .' t' => 'tr.text_id = t.id',
				DBT_AUTHOR_OF .' aof' => 't.id = aof.text_id',
				DBT_PERSON .' a' => 'aof.person_id = a.id',
			),
			'WHERE' => array('tr.user_id IN ('
				. $this->db->selectQ(DBT_USER,
					array('username' => $this->username),
					'id')
				. ')'),
			'GROUP BY' => 't.id',
			'ORDER BY' => "tr.date DESC",
			'LIMIT' => array($offset, $limit),
		);
		$this->data = array(
			array(
				array( array('type' => 'header'), 'Дата'),
				array( array('type' => 'header'), 'Заглавие'),
				array( array('type' => 'header'), 'Оценка'),
			)
		);
		$this->db->iterateOverResult(
			$this->db->extselectQ($qa), 'makeListByUserItem', $this);
		$this->title = 'Оценки от ' . $this->makeUserLink( $this->username );
		return $this->out->simpleTable($this->title, $this->data);
	}

	public function makeListByUserItem($dbrow) {
		$this->data[] = array(
			Legacy::humanDate($dbrow['date']),
			$this->makeSimpleTextLink($dbrow['title'], $dbrow['textId'])
				. $this->makeFromAuthorSuffix($dbrow),
			$dbrow['rating']
		);
	}

	protected function initData() {
		$this->work = $this->controller->getRepository('Text')->find($this->textId);
		if ( !is_object($this->work) ) {
			$this->addMessage("Няма такова произведение (номер $this->textId).", true);
			return false;
		}
		return true;
	}
}
