<?php
namespace App\Legacy;

class TextPage extends Page {

	public function getTextPartContent()
	{
		if ( ! $this->initData() ) {
			return '';
		}
		$data = json_encode(array(
			'toc' => $this->makeToc(),
			'text' => $this->makeTextContent() . $this->makeEndMessage()
		));
		return $this->request->value('jsoncallback') . "($data);";
	}

	protected function makeNextSeriesWorkLink($separate = false) {
		$nextWork = $this->work->getNextFromSeries();
		$o = '';
		if ( is_object($nextWork) ) {
			$sl = $this->makeSeriesLink($this->work->series);
			$tl = $this->makeSimpleTextLink(
				$nextWork->title, $nextWork->getId(), 1, ''/*, array('rel' => 'next')*/);
			$type = Legacy::workTypeArticle($nextWork->type);
			$sep = $separate ? '<hr />' : '';
			$stype = Legacy::seriesTypeArticle($this->work->seriesType);
			$o = "$sep<p>Към следващото произведение от $stype „{$sl}“ — $type „{$tl}“</p>";
		}
		return $o;
	}


	protected function makeNextBookWorkLink($separate = false) {
		$o = '';
		foreach ($this->work->getNextFromBooks() as $book => $nextWork) {
			if ( is_object($nextWork) ) {
				$sl = $this->makeBookLink($book, $this->work->books[$book]['title']);
				$tl = $this->makeSimpleTextLink(
					$nextWork->title, $nextWork->getId(), 1, ''/*, array('rel' => 'next')*/);
				$type = Legacy::workTypeArticle($nextWork->type);
				switch ( $this->work->books[$book]['type'] ) {
					case 'collection' : $bt = 'сборника'; break;
					case 'poetry' : $bt = 'стихосбирката'; break;
					case 'book' : default : $bt = 'книгата'; break;
				}
				$o .= "\n<p>Към следващото произведение от $bt „{$sl}“ — $type „{$tl}“</p>";
			}
		}
		$sep = !empty($o) && $separate ? '<hr />' : '';
		return $sep . $o;
	}


	protected function makeLicenseView($name, $uri = '') {
		if ( empty($uri) ) {
			return "($name)";
		}
		return "(<a href='$uri' rel='license'>$name</a>)";
	}

}
