<?php
namespace Chitanka\LibBundle\Legacy;

function makeDbRows($file, $headlev) {
	$parser = new SfbParserSimple($file, $headlev);
	$parser->convert();

	$dbRows = array();
	foreach ($parser->headers() as $nr => $hdata) {
		extract($hdata);
		if ( !isset($titles) ) continue;
		foreach ($titles as $lev => $title) {
			$dbRows[] = array($nr, $lev, $title, $fpos, $lcnt);
		}
	}
	return $dbRows;
}

class SfbParserSimple {

	protected $debug = false;
	protected $titMarks = array(1=>'>', '>>', '>>>', '>>>>', '>>>>>');


	public function __construct($file, $reqdepth = 1) {
		$this->handle = fopen($file, 'r');
		$this->reqdepth = $reqdepth;
		$this->lcnt = 0;
		$this->hasNextLine = false;
		$this->headers = array();
		$this->fpos = 0;
		$this->curUnknownHead = array(1=>1, 1, 1, 1, 1);
	}


	public function __destruct() {
		fclose($this->handle);
	}


	public function convert() {
		while ( $this->nextLine() !== false ) {
			$this->doText();
		}
	}


	public function headers() {
		return $this->makeEndHeaders();
	}

	public function headersFlat() {
		$flatHeaders = array();
		foreach ($this->headers() as $nr => $hdata) {
			if (empty($hdata['titles'])) {
				continue;
			}
			foreach ($hdata['titles'] as $lev => $title) {
				$flatHeaders[] = array(
					'nr' => $nr,
					'level' => $lev,
					'title' => $title,
					'file_pos' => $hdata['fpos'],
					'line_count' => $hdata['lcnt'],
				);
			}
		}
		return $flatHeaders;
	}

	protected function nextLine() {
		if ($this->hasNextLine) {
			$this->hasNextLine = false;
			return $this->line;
		}
		if ( (feof($this->handle) ) /*&& !$this->hasNextLine*/ ) {
			$this->lcmd = $this->ltext = null;
			return false;
		}
		$this->fpos = ftell($this->handle);
		$this->lcnt++;
		$this->line = rtrim( fgets($this->handle) );
		$parts = explode("\t", $this->line, 2);
		$this->lcmd = $parts[0];
		$this->ltext = isset($parts[1]) ? $parts[1] : $this->line;
		return $this->line;
	}


	protected function doText() {
		#if ($this->debug) echo "in doText: '$this->line'\n";
		switch ($this->lcmd) {
		case '>': $this->doTitle(1); break;
		case '>>': $this->doTitle(2); break;
		case '>>>': $this->doTitle(3); break;
		case '>>>>': $this->doTitle(4); break;
		case '>>>>>': $this->doTitle(5); break;
		}
	}


	protected function doTitle($level) {
		if ($this->debug) echo "in doTitle($level)\n";
		if ($this->reqdepth < $level) { return; }
		#echo $this->fpos." - $this->ltext\n";
		$fpos = $this->fpos;
		$lcnt = $this->lcnt;
		if ( $this->ltext[0] == '>' ) {
			$header = $this->curUnknownHead[$level]++;
		} else {
			$header = $this->ltext;
			if ( !$this->titleHasEndingSymbol($this->ltext) ) { $header .= '.'; }
		}
		$this->nextLine();
		while ( $this->lcmd == $this->titMarks[$level] ) {
			$header .= ' '.$this->ltext;
			if ( !$this->titleHasEndingSymbol($this->ltext) ) { $header .= '.'; }
			$this->nextLine();
		}
		if ( !preg_match('/ г\.$/', $header) ) {
			$header = rtrim($header, '.');
		}
		$this->headers[] = array($level, $header, $fpos, $lcnt);
		$this->hasNextLine = true;
	}


	protected function titleHasEndingSymbol($title) {
		return preg_match('/[.,;:?!]_*$/', $title);
	}


	protected function makeEndHeaders() {
		$len = count($this->headers);
		$newheaders = array();
		$prevlev = 100;
		$curnr = 1;
		for ($i=0; $i < $len; $i++) {
			list($lev, $title, $fpos, $cnt) = $this->headers[$i];
			if ($lev <= $prevlev) {
				$newheaders[$curnr++] = array(
					'fpos' => $fpos, 'lcnt' => $cnt,
					'titles' => array($lev => $title)
				);
			} else {
				$newheaders[$curnr-1]['titles'][$lev] = $title;
			}
			$prevlev = $lev;
		}
		// А сега, на мястото на lcnt ще се съхрани разликата в lcnt-тите на два
		// съседни елемента
		$len = count($newheaders);
		$prevcnt = 1;
		for ($i=2; $i <= $len; $i++) {
			$newheaders[$i-1]['lcnt'] = $newheaders[$i]['lcnt'] - $prevcnt;
			$prevcnt = $newheaders[$i]['lcnt'];
		}
		$newheaders[$len]['lcnt'] = $this->lcnt - $prevcnt;
		// първото заглавие да почва от 0, за да се вземат и абзаците преди него
		$newheaders[1]['fpos'] = 0;
		return $newheaders;
	}

}
