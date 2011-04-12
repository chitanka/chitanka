<?php
namespace Chitanka\LibBundle\Legacy;

class ClearCachePage extends Page {

	protected
		$action = 'clearCache';


	public function __construct() {
		parent::__construct();
		$this->title = 'Прочистване на склада';
		$this->texts = str_replace("\r", '', $this->request->value('texts'));
		$this->texts = explode("\n", $this->texts);
		$this->start = (int) $this->request->value('start');
		$this->end = (int) $this->request->value('end');
	}


	protected function buildContent() {
		if ( !empty($this->texts) ) {
			$this->texts = array_unique($this->texts);
			foreach ($this->texts as $key => $textId) {
				$textId = trim($textId);
				if ( empty($textId) ) {
					unset($this->texts[$key]);
					continue;
				}
				CacheManager::clearDlCache($textId);
			}
			$this->addMessage('Копията на следните текстове бяха изтрити: '.
				implode(', ', $this->texts));
		}
		if ( !empty($this->start) && !empty($this->end) ) {
			if ( $this->start > $this->end ) {
				$t = $this->start;
				$this->start = $this->end;
				$this->end = $t;
			}
			for ($i = $this->start; $i <= $this->end; $i++) {
				CacheManager::clearDlCache($i);
			}
			$this->addMessage("Копията на текстовете с номера от
				$this->start до $this->end бяха изтрити.");
		}
		return $this->makeForm();
	}


	protected function makeForm() {
		$texts = $this->out->textarea('texts', '', '', 25, 8);
		$submit = $this->out->submitButton('Прочистване');
		return <<<EOS

<form action="" method="post">
<div>
	<label for="texts">Номера на текстове (по един на ред):</label><br />
	$texts<br />
	$submit
</div>
</form>
EOS;
	}
}
