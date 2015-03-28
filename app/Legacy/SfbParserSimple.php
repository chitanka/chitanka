<?php namespace App\Legacy;

function makeDbRows($file, $headlev) {
	$parser = new SfbParserSimple($file, $headlev);
	$parser->convert();

	$dbRows = [];
	foreach ($parser->headers() as $nr => $hdata) {
		if (!isset($hdata['titles'])) {
			continue;
		}
		foreach ($hdata['titles'] as $lev => $title) {
			$dbRows[] = [$nr, $lev, $title, $hdata['fpos'], $hdata['lcnt']];
		}
	}
	return $dbRows;
}

class SfbParserSimple {

	protected $debug = false;
	protected $titMarks = [1=>'>', '>>', '>>>', '>>>>', '>>>>>'];
	private $handle;
	private $reqdepth;
	private $lcnt;
	private $line;
	private $lcmd;
	private $ltext;
	private $hasNextLine;
	private $headers;
	private $fpos;
	private $curUnknownHead;

	public function __construct($file, $reqdepth = 1) {
		$this->handle = fopen($file, 'r');
		$this->reqdepth = $reqdepth;
		$this->lcnt = 0;
		$this->hasNextLine = false;
		$this->headers = [];
		$this->fpos = 0;
		$this->curUnknownHead = [1=>1, 1, 1, 1, 1];
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
		$flatHeaders = [];
		foreach ($this->headers() as $nr => $hdata) {
			if (empty($hdata['titles'])) {
				continue;
			}
			foreach ($hdata['titles'] as $lev => $title) {
				$flatHeaders[] = [
					'nr' => $nr,
					'level' => $lev,
					'title' => $title,
					'file_pos' => $hdata['fpos'],
					'line_count' => $hdata['lcnt'],
				];
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
		switch ($this->lcmd) {
			case '>': $this->doTitle(1); break;
			case '>>': $this->doTitle(2); break;
			case '>>>': $this->doTitle(3); break;
			case '>>>>': $this->doTitle(4); break;
			case '>>>>>': $this->doTitle(5); break;
		}
	}

	/**
	 * @param int $level
	 */
	protected function doTitle($level) {
		if ($this->debug) {
			echo "in doTitle($level)\n";
		}
		if ($this->reqdepth < $level) {
			return;
		}
		$fpos = $this->fpos;
		$lcnt = $this->lcnt;
		if ($this->ltext[0] == '>') {
			$header = $this->curUnknownHead[$level]++;
		} else {
			$header = $this->ltext;
			if (!$this->titleHasEndingSymbol($this->ltext)) { 
				$header .= '.';
			}
		}
		$this->nextLine();
		while ( $this->lcmd == $this->titMarks[$level] ) {
			$header .= ' '.$this->ltext;
			if (!$this->titleHasEndingSymbol($this->ltext)) { 
				$header .= '.';
			}
			$this->nextLine();
		}
		if (!preg_match('/ г\.$/', $header)) {
			$header = rtrim($header, '.');
		}
		$this->headers[] = [$level, $header, $fpos, $lcnt];
		$this->hasNextLine = true;
	}

	protected function titleHasEndingSymbol($title) {
		return preg_match('/[.,;:?!]_*$/', $title);
	}

	protected function makeEndHeaders() {
		$len = count($this->headers);
		$newheaders = [];
		$prevlev = 100;
		$curnr = 1;
		for ($i=0; $i < $len; $i++) {
			list($lev, $title, $fpos, $cnt) = $this->headers[$i];
			if ($lev <= $prevlev) {
				$newheaders[$curnr++] = [
					'fpos' => $fpos, 'lcnt' => $cnt,
					'titles' => [$lev => $title]
				];
			} else {
				$newheaders[$curnr-1]['titles'][$lev] = $title;
			}
			$prevlev = $lev;
		}
		// А сега, на мястото на lcnt ще се съхрани разликата в lcnt-тите на два съседни елемента
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
