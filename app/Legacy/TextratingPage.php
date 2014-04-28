<?php namespace App\Legacy;

class TextratingPage extends TextPage {

	protected
		$action = 'textrating',
		$includeUserLinks = true;

	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Оценки';
		$this->username = $this->request->value('username');
	}

	protected function buildContent() {
		if ( ! empty( $this->username ) ) {
			return $this->makeListByUser();
		}
		return '';
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

}
