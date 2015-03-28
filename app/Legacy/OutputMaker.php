<?php namespace App\Legacy;

use App\Util\Number;
use App\Util\String;

class OutputMaker {

	private $argSeparator = '&';

	public function textField($name, $id = '', $value = '', $size = 30, $maxlength = 255, $tabindex = null, $title = '', $attrs = []) {
		if (empty($id)) { $id = $name; }
		$attrs = [
			'type' => 'text', 'name' => $name, 'id' => $id,
			'size' => $size, 'maxlength' => $maxlength,
			'value' => $value, 'title' => $title, 'tabindex' => $tabindex
		] + $attrs;
		return $this->xmlElement('input', null, $attrs);
	}

	public function textarea($name, $id = '', $value = '', $rows = 5, $cols = 80, $tabindex = null, $attrs = []) {
		if (empty($id)) { $id = $name; }
		$attrs = [
			'name' => $name, 'id' => $id,
			'cols' => $cols, 'rows' => $rows, 'tabindex' => $tabindex
		] + $attrs;
		return $this->xmlElement('textarea', String::myhtmlentities($value), $attrs);
	}

	public function checkbox($name, $id = '', $checked = false, $label = '', $value = null, $tabindex = null, $attrs = []) {
		if (empty($id)) { $id = $name; }
		$attrs = [
			'type' => 'checkbox', 'name' => $name, 'id' => $id,
			'value' => $value, 'tabindex' => $tabindex
		] + $attrs;
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
		$attrs = ['type' => 'hidden', 'name' => $name, 'value' => $value];
		return $this->xmlElement('input', null, $attrs);
	}

	public function passField($name, $id = '', $value = '', $size = 30, $maxlength = 255, $tabindex = null, $attrs = []) {
		if (empty($id)) { $id = $name; }
		$attrs = [
			'type' => 'password', 'name' => $name, 'id' => $id,
			'size' => $size, 'maxlength' => $maxlength, 'value' => $value,
			'tabindex' => $tabindex
		] + $attrs;
		return $this->xmlElement('input', null, $attrs);
	}

	public function fileField($name, $id = '', $tabindex = null, $title = '', $attrs = []) {
		if (empty($id)) { $id = $name; }
		$attrs = [
			'type' => 'file', 'name' => $name, 'id' => $id,
			'title' => $title, 'tabindex' => $tabindex
		] + $attrs;
		return $this->xmlElement('input', null, $attrs);
	}

	public function makeMaxFileSizeField() {
		return $this->hiddenField('MAX_FILE_SIZE', Number::iniBytes(ini_get('upload_max_filesize')));
	}

	public function submitButton($value, $title = '', $tabindex = null, $putname = true, $attrs = []) {
		$attrs = [
			'type' => 'submit', 'value' => $value, 'title' => $title,
			'tabindex' => $tabindex
		] + $attrs;
		if ( is_string($putname) ) {
			$attrs['name'] = $putname;
		} else if ($putname) {
			$attrs['name'] = 'submitButton';
		}
		return $this->xmlElement('input', null, $attrs);
	}

	public function selectBox($name, $id = '', $opts = [], $selId = 0, $tabindex = null, $attrs = []) {
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
			$oattrs = ['value' => $key, 'title' => $title];
			if ( in_array( $key, $selId) ) $oattrs['selected'] = 'selected';
			$o .= "\n\t". $this->xmlElement('option', $val, $oattrs);
		}
		if (empty($id)) { $id = $name; }
		$attrs = [
			'name' => $name, 'id' => $id, 'tabindex' => $tabindex
		] + $attrs;
		return $this->xmlElement('select', $o, $attrs);
	}

	public function link($url, $text = '', $title = '', $attrs = [], $args = []) {
		if ($text === '') $text = $url;
		return $this->link_raw($url, String::myhtmlspecialchars($text), $title, $attrs, $args);
	}

	/**
	 * @param string $url
	 * @param string $text
	 * @param string $title
	 * @param array $attrs
	 * @param array $args
	 * @return string
	 */
	public function link_raw($url, $text, $title = '', array $attrs = [], array $args = []) {
		$q = [];
		foreach ($args as $field => $value) {
			$q[] = $field . Request::PARAM_SEPARATOR . $value;
		}
		if ( !empty($q) ) {
			$url .= implode($this->argSeparator, $q);
		}
		$attrs = [ 'href' => $url ] + $attrs;
		if ( ! empty( $title ) ) $attrs['title'] = $title;
		return $this->xmlElement('a', $text, $attrs);
	}

	/**
	 * @param string $text
	 */
	public function label($text, $for, $title = '', $attrs = []) {
		$attrs = [
			'for' => $for, 'title' => $title
		] + $attrs;
		return $this->xmlElement('label', $text, $attrs);
	}

	/**
	 * @param string $name
	 * @param string $content
	 * @param array $attrs
	 * @param bool $doEscape
	 */
	public function xmlElement($name, $content = '', $attrs = [], $doEscape = true) {
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

}
