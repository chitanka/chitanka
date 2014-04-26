<?php namespace App\Legacy;

use App\Util\Char;
use App\Util\String;

class OutputMaker {

	public
		$inencoding = 'utf-8',
		$outencoding = 'utf-8';

	protected
		$defArgSeparator = '&',
		$argSeparator = '&',
		$queryStart = '?';

	public function textField($name, $id = '', $value = '', $size = 30, $maxlength = 255, $tabindex = null, $title = '', $attrs = array()) {
		Legacy::fillOnEmpty($id, $name);
		$attrs = array(
			'type' => 'text', 'name' => $name, 'id' => $id,
			'size' => $size, 'maxlength' => $maxlength,
			'value' => $value, 'title' => $title, 'tabindex' => $tabindex
		) + $attrs;
		return $this->xmlElement('input', null, $attrs);
	}

	public function textarea($name, $id = '', $value = '', $rows = 5, $cols = 80, $tabindex = null, $attrs = array()) {
		Legacy::fillOnEmpty($id, $name);
		$attrs = array(
			'name' => $name, 'id' => $id,
			'cols' => $cols, 'rows' => $rows, 'tabindex' => $tabindex
		) + $attrs;
		return $this->xmlElement('textarea', String::myhtmlentities($value), $attrs);
	}

	public function checkbox($name, $id = '', $checked = false, $label = '', $value = null, $tabindex = null, $attrs = array()) {
		Legacy::fillOnEmpty($id, $name);
		$attrs = array(
			'type' => 'checkbox', 'name' => $name, 'id' => $id,
			'value' => $value, 'tabindex' => $tabindex
		) + $attrs;
		if ($checked) { $attrs['checked'] = 'checked'; }
		if ( !empty($label) ) {
			$label = $this->label($label, $id);
		}
		return $this->xmlElement('input', null, $attrs) . $label;
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function hiddenField($name, $value = '') {
		$attrs = array('type' => 'hidden', 'name' => $name, 'value' => $value);
		return $this->xmlElement('input', null, $attrs);
	}

	public function passField($name, $id = '', $value = '', $size = 30, $maxlength = 255, $tabindex = null, $attrs = array()) {
		Legacy::fillOnEmpty($id, $name);
		$attrs = array(
			'type' => 'password', 'name' => $name, 'id' => $id,
			'size' => $size, 'maxlength' => $maxlength, 'value' => $value,
			'tabindex' => $tabindex
		) + $attrs;
		return $this->xmlElement('input', null, $attrs);
	}

	public function fileField($name, $id = '', $tabindex = null, $title = '', $attrs = array()) {
		Legacy::fillOnEmpty($id, $name);
		$attrs = array(
			'type' => 'file', 'name' => $name, 'id' => $id,
			'title' => $title, 'tabindex' => $tabindex
		) + $attrs;
		return $this->xmlElement('input', null, $attrs);
	}

	public function makeMaxFileSizeField() {
		return $this->hiddenField('MAX_FILE_SIZE', Legacy::ini_bytes( ini_get('upload_max_filesize') ));
	}

	public function submitButton($value, $title = '', $tabindex = null, $putname = true, $attrs = array()) {
		$attrs = array(
			'type' => 'submit', 'value' => $value, 'title' => $title,
			'tabindex' => $tabindex
		) + $attrs;
		if ( is_string($putname) ) {
			$attrs['name'] = $putname;
		} else if ($putname) {
			$attrs['name'] = 'submitButton';
		}
		return $this->xmlElement('input', null, $attrs);
	}

	public function selectBox($name, $id = '', $opts = array(), $selId = 0, $tabindex = null, $attrs = array()) {
		$o = '';
		if ( ! is_array( $selId ) ) {
			$selId = (array) $selId; // threat it as a multiple-select box
		}
		foreach ($opts as $key => $opt) {
			if ( is_object($opt) ) {
				$key = $opt->id;
				$val = $opt->name;
				$title = isset($opt->title) ? $opt->title : '';
			} else if ( is_array($opt) ) {
				list($val, $title) = $opt;
			} else {
				$val = $opt;
				$title = '';
			}
			$oattrs = array('value' => $key, 'title' => $title);
			if ( in_array( $key, $selId) ) $oattrs['selected'] = 'selected';
			$o .= "\n\t". $this->xmlElement('option', $val, $oattrs);
		}
		Legacy::fillOnEmpty($id, $name);
		$attrs = array(
			'name' => $name, 'id' => $id, 'tabindex' => $tabindex
		) + $attrs;
		return $this->xmlElement('select', $o, $attrs);
	}

	public function link($url, $text = '', $title = '', $attrs = array(), $args = array()) {
		if ($text === '') $text = $url;
		return $this->link_raw($url, $this->escape($text), $title, $attrs, $args);
	}

	/**
	 * @param string $url
	 * @param string $text
	 * @param string $title
	 * @param array $attrs
	 * @param array $args
	 * @return string
	 */
	public function link_raw($url, $text, $title = '', array $attrs = array(), array $args = array()) {
		$q = array();
		foreach ($args as $field => $value) {
			$q[] = $field . Request::PARAM_SEPARATOR . $value;
		}
		if ( !empty($q) ) {
			$url .= implode($this->argSeparator, $q);
		}
		$attrs = array( 'href' => $url ) + $attrs;
		if ( ! empty( $title ) ) $attrs['title'] = $title;
		return $this->xmlElement('a', $text, $attrs);
	}

	public function listItem($item, $attrs = array()) {
		return "\n\t" . $this->xmlElement('li', $item, $attrs);
	}

	/**
	 * @param string $text
	 */
	public function label($text, $for, $title = '', $attrs = array()) {
		$attrs = array(
			'for' => $for, 'title' => $title
		) + $attrs;
		return $this->xmlElement('label', $text, $attrs);
	}

	public function ulist($items, $attrs = array()) {
		$oitems = '';
		foreach ($items as $item) {
			if ( empty( $item ) ) {
				continue;
			}
			$lattrs = array();
			if ( is_array($item) ) {
				assert( 'count($item) >= 2' );
				list($item, $lattrs) = $item;
			}
			$oitems .= $this->listItem($item, $lattrs);
		}
		if ( empty($oitems) ) {
			return '';
		}
		return $this->xmlElement('ul', $oitems, $attrs);
	}

	public function xmlElement($name, $content = '', $attrs = array(), $doEscape = true) {
		$end = is_null($content) ? ' />' : ">$content</$name>";
		return '<'.$name . $this->makeAttribs($attrs, $doEscape) . $end;
	}

	private function makeAttribs($attrs, $doEscape = true) {
		$o = '';
		foreach ($attrs as $attr => $value) {
			$o .= $this->attrib($attr, $value, $doEscape);
		}
		return $o;
	}

	private function attrib($attrib, $value, $doEscape = true) {
		if ( is_null($value) || ( empty($value) && $attrib == 'title' ) ) {
			return '';
		}

		$value = strip_tags($value);
		return ' '. $attrib .'="'
			. ( $doEscape ? String::myhtmlspecialchars($value) : $value )
			.'"';
	}

	/**
		Creates an HTML table.

		@param $caption Table caption
		@param $data Array of arrays, i.e.
			array(
				array(CELL, CELL, ...),
				array(CELL, CELL, ...),
				...
			)
			CELL can be:
			— a string — equivalent to a simple table cell
			— an array:
				— first element must be an associative array for cell attributes;
					if this array contains a key 'type' with the value 'header',
					then the cell is rendered as a header cell
				— second element must be a string representing the cell content
		@param attrs Optional associative array for table attributes
	*/
	public function simpleTable($caption, $data, $attrs = array()) {
		$ext = $this->makeAttribs($attrs);
		$t = "\n<table class=\"content\"$ext>";
		if ( !empty($caption) ) {
			$t .= "<caption>$caption</caption>";
		}
		$curRowClass = '';
		foreach ($data as $row) {
			$curRowClass = $this->nextRowClass($curRowClass);
			$t .= "\n<tr class=\"$curRowClass\">";
			foreach ($row as $cell) {
				$ctype = 'd';
				if ( is_array($cell) ) {
					if ( isset( $cell[0]['type'] ) ) {
						$ctype = $cell[0]['type'] == 'header' ? 'h' : 'd';
						unset( $cell[0]['type'] );
					}
					$cattrs = $this->makeAttribs($cell[0]);
					$content = $cell[1];
				} else {
					$cattrs = '';
					$content = $cell;
				}
				$t .= "\n\t<t{$ctype}{$cattrs}>{$content}</t{$ctype}>";
			}
			$t .= "\n</tr>";
		}
		return $t.'</table>';
	}

	public function nextRowClass($curRowClass = '') {
		return $curRowClass == 'even' ? 'odd' : 'even';
	}

	public function addUrlQuery($url, $args) {
		if ( !empty($this->queryStart) && strpos($url, $this->queryStart) === false ) {
			$url .= $this->queryStart;
		}
		foreach ((array) $args as $key => $val) {
			$sep = $this->getArgSeparator($url);
			$url = preg_replace("!$sep$key".Request::PARAM_SEPARATOR."[^$sep]*!", '', $url);
			$url .= $sep . $key . Request::PARAM_SEPARATOR . $this->urlencode($val);
		}
		return $url;
	}

	private function getArgSeparator($url = '') {
		if ( empty($url) || strpos($url, $this->defArgSeparator) === false ) {
			return $this->argSeparator;
		}
		return $this->defArgSeparator;
	}

	/**
		TODO was done with myhtmlentities() (r1146), check why.
		XHTML Mobile does not have most of the html entities,
		so revert back to myhtmlspecialchars().
	*/
	public function escape($s) {
		return String::myhtmlspecialchars( $s );
	}

	/**
	 * @param string $str
	 * @return string
	 */
	private function urlencode($str) {
		$enc = urlencode($str);
		if ( strpos($str, '/') !== false ) {
			$enc = strtr($enc, array('%2F' => '/'));
		}
		return $enc;
	}

	/**
	 * @param string $name
	 * @param int $maxlength
	 * @return string
	 */
	public function slugify($name, $maxlength = 40) {
		$name = strtr($name, array(
			' ' => '_', '/' => '_',
			'²' => '2', '°' => 'deg',
			'—' => '',
		));
		$name = Char::cyr2lat($name);
		$name = Legacy::removeDiacritics($name);
		$name = iconv($this->inencoding, 'ISO-8859-1//TRANSLIT', $name);
		$name = strtolower($name);
		$name = preg_replace('/__+/', '_', $name);
		$name = preg_replace('/[^\w\d_]/', '', $name);
		$name = rtrim(substr($name, 0, $maxlength), '_');

		return $name;
	}

	/**
	 * @param string $elm
	 * @param array $attrs
	 * @param bool $xml
	 * @return string
	 */
	private function getEmptyTag($elm, array $attrs = array(), $xml = true) {
		$end = $xml ? '/>' : ' />';
		return '<'. $elm . $this->makeAttribs($attrs) . $end;
	}

	public function getRssLink($url, $title = '') {
		return $this->getEmptyTag('link', array(
			 'rel'   => 'alternate',
			 'type'  => 'application/rss+xml',
			 'title' => "$title (RSS 2.0)",
			 'href'  => $url,
		), false);
	}

}
