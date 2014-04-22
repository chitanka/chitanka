<?php
namespace App\Legacy;

class ListPage extends Page {

	const
		PARAM_OBJ = 'o'
	;

	protected
		$action = 'list',
		$outFormats = array(
			'html',
			'csv',
		),
		$objects = array(
			'title' => 'Опростен списък на заглавията',
			'author' => 'Опростен списък на авторите',
			'translator' => 'Опростен списък на преводачите',
		),
		$_objRawSep = '.'
	;


	public function __construct()
	{
		parent::__construct();
		$this->title = 'Опростени списъци';
		$this->objectRaw = $this->request->value(self::PARAM_OBJ, '', 1);
	}


	protected function buildContent()
	{
		if ( empty( $this->objectRaw ) ) {
			return $this->getObjectsList();
		}
		$this->_initObjectVars( $this->objectRaw );
		$listerClass = $this->object . 'List';
		$lister = new $listerClass($this->db, $this);
		$format = 'format' . ucfirst($this->outFormat);
		return $this->$format( $lister->getList() );
	}


	protected function _initObjectVars($raw)
	{
		$parts = explode($this->_objRawSep, $raw);
		$this->object = Legacy::normVal(@$parts[0], array_keys($this->objects));
		$this->outFormat = Legacy::normVal(@$parts[1], $this->outFormats);
		$this->title = $this->objects[ $this->object ];
	}


	public function getObjectsList()
	{
		$items = array();
		foreach ( $this->objects as $object => $title ) {
			$links = array();
			foreach ( $this->outFormats as $format ) {
				$params = array(
					self::FF_ACTION => $this->action,
					self::PARAM_OBJ => $object . $this->_objRawSep . $format,
				);
				$links[] = $this->out->internLink($format, $params, 2);
			}
			$items[] = "$title: " . implode(', ', $links);
		}
		return $this->out->ulist($items);
	}


	protected function formatHtml($data)
	{
		foreach ( $data[0] as $k => $v ) {
			$data[0][$k] = array(
				array('type' => 'header'),
				$v
			);
		}
		$this->addStyle('#navigation {display:none}');
		return $this->out->simpleTable($this->title, $data);
	}


	protected function formatCsv($data)
	{
		$this->contentType = 'text/csv';
		$this->fullContent = $this->getAsCsv($data);
		return '';
	}


	/** Format an array as CSV */
	public function getAsCsv($data, $valSep = ',', $valDelim = '"')
	{
		$o = '';
		foreach ($data as $row) {
			$o .= $valDelim
				. implode($valDelim . $valSep . $valDelim, $row)
				. $valDelim . "\n";
		}
		return $o;
	}

}



abstract class ObjectList {

	protected
		// used also for ordering
		$cols = array(),
		$dbcols = array(),
		$_sqlQuery = '',
		// this will contain the columns which should be translated somehow
		$_translate = array(),
		// will contain the generated list data
		$data = array()
	;

	public function __construct($db, $page)
	{
		$this->_db = $db;
		$this->_page = $page;
		$this->_init();
	}


	public function getList()
	{
		$this->data[] = array_values( $this->cols );
		$this->_populateList();
		return $this->data;
	}


	protected function _init() {}

	protected function _getSqlQuery()
	{
		$sel = '';
		foreach ( $this->cols as $col => $_ ) {
			$sel .= ',' . (isset($this->dbcols[$col]) ? $this->dbcols[$col] : $col);
		}
		$this->_sqlQuery['SELECT'] = ltrim($sel, ',');
		return $this->_db->extselectQ( $this->_sqlQuery );
	}


	protected function _populateList()
	{
		$this->_initTranslateList();
		$this->_db->iterateOverResult($this->_getSqlQuery(), 'getListItem', $this);
	}



	public function getListItem($dbrow)
	{
		$this->data[] = $this->_translateDbRow($dbrow);
		return '';
	}


	protected function _translateDbRow($dbrow)
	{
		foreach ( $this->_translate as $key => $tmethod ) {
			$dbrow[$key] = $this->$tmethod($dbrow[$key], $dbrow);
		}
		return $dbrow;
	}


	protected function _initTranslateList()
	{
		if ( ! empty( $this->_translate ) ) {
			return;
		}
		foreach ($this->cols as $col => $_) {
			$method = $col;
			if ( method_exists($this, $method) ) {
				$this->_translate[$col] = $method;
			}
		}
	}

}



class TitleList extends ObjectList {

	protected
		// used also for ordering
		$cols = array(
			'id' => '№',
			'author' => 'Автор',
			'title' => 'Заглавие',
			//'sernr' => '',
			'series' => 'Серия',
			'translator' => 'Преводач',
			'year' => 'Година',
			'orig_title' => 'Оригинално заглавие',
			'trans_year' => 'Година на превод',
			'type' => 'Форма',
			'labels' => 'Етикети',
			'lastedit' => 'Посл. редакция',
		),
		$dbcols = array(
			'id' => 't.id',
			'author' => 'GROUP_CONCAT( DISTINCT a.name ORDER BY aof.pos SEPARATOR ", " )',
			'title' => 't.title',
			//'sernr' => 't.sernr',
			'series' => 's.name',
			'translator' => 'GROUP_CONCAT( DISTINCT tr.name ORDER BY tof.pos SEPARATOR ", " )',
			'year' => 't.year',
			'orig_title' => 't.orig_title',
			'trans_year' => 't.trans_year',
			'type' => 't.type',
			'labels' => 'GROUP_CONCAT( DISTINCT l.name SEPARATOR ", " )',
			'lastedit' => 'h.date',
		)
		;


	protected function _init()
	{
		$this->_sqlQuery = array(
			'FROM' => DBT_TEXT . ' t',
			'LEFT JOIN' => array(
				DBT_AUTHOR_OF     . ' aof' => 't.id = aof.text',
				DBT_PERSON        . ' a'   => 'aof.person = a.id',
				DBT_SERIES        . ' s'   => 't.series = s.id',
				DBT_TRANSLATOR_OF . ' tof' => 't.id = tof.text',
				DBT_PERSON        . ' tr'  => 'tof.person = tr.id',
				DBT_TEXT_LABEL    . ' tl'  => 't.id = tl.text',
				DBT_LABEL         . ' l'   => 'tl.label = l.id',
				DBT_EDIT_HISTORY  . ' h'   => 't.lastedit = h.id',
			),
			'GROUP BY' => 't.id',
			'ORDER BY' => 'a.last_name, t.title, s.name, t.sernr'
		);
	}

	public function type($dbval, $dbrow)
	{
		return work_type($dbval);
	}

	public function year($dbval, $dbrow)
	{
		return $this->_page->makeYearView($dbval);
	}

	public function trans_year($dbval, $dbrow)
	{
		return $this->_page->makeYearView($dbval);
	}

}



abstract class PersonList extends ObjectList {

	protected
		// used also for ordering
		$cols = array(
			'name' => 'Име',
			'orig_name' => 'Оригинално изписване',
			'country' => 'Държава',
			'alt_names' => 'Псевдоними',
		),
		$dbcols = array(
			'name' => 'p.name',
			'orig_name' => 'p.orig_name',
			'country' => 'p.country',
			'alt_names' => 'GROUP_CONCAT( DISTINCT alt.name SEPARATOR "; " )',
		),
		$_dbRoleBit = 0
	;


	protected function _init()
	{
		$this->_sqlQuery = array(
			'FROM' => DBT_PERSON . ' p',
			'WHERE' => array("role & $this->_dbRoleBit"),
			'LEFT JOIN' => array(
				DBT_PERSON_ALT . ' alt' => 'alt.person = p.id',
			),
			'GROUP BY' => 'p.id',
			'ORDER BY' => 'p.last_name, p.name'
		);
	}

	public function country($dbval, $dbrow)
	{
		return country_name($dbval);
	}

}


class AuthorList extends PersonList {
	protected $_dbRoleBit = 1;
}


class TranslatorList extends PersonList {
	protected $_dbRoleBit = 2;
}
