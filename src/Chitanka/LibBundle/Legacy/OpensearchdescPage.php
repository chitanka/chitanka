<?php
namespace Chitanka\LibBundle\Legacy;

class OpensearchdescPage extends Page {

	public function __construct() {
		parent::__construct();
		$this->action = 'opensearchdesc';
		$this->contentType = 'application/opensearchdescription+xml';
		$this->skin->useAbsolutePath();

		$defObj = key($this->searchOptions);
		$this->searchKey = $this->request->value('key', $defObj, 1, $this->searchOptions);
		$this->searchKeyTitle = $this->searchOptions[$this->searchKey];
		$this->title = 'Описание за OpenSearch — ' . $this->searchKeyTitle;
	}


	protected function buildContent() {
		$pi = '<?xml version="1.0"?>';
		$searchUrl =  $this->request->server() . 'action' . $this->searchKey;
		$favicon = $this->getFavicon();
		$this->addTemplates();
		return $this->fullContent = Legacy::expandTemplates(<<<EOS
$pi
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/" xmlns:moz="http://www.mozilla.org/2006/browser/search/">
	<ShortName>$this->sitename ($this->searchKeyTitle)</ShortName>
	<Description>Търсене в $this->sitename по $this->searchKeyTitle</Description>
	<InputEncoding>$this->inencoding</InputEncoding>
	<Image width="16" height="16" type="image/png">$favicon</Image>
	<Url type="text/html" method="get" template="$searchUrl?q={searchTerms}&amp;prefix=%&amp;sortby=first&amp;mode=simple"/>
	<Url type="application/x-suggestions+json" template="$searchUrl?q={searchTerms}&amp;prefix=%&amp;ajaxFunc=openSearch"/>
	<moz:SearchForm>$searchUrl</moz:SearchForm>
</OpenSearchDescription>
EOS
);
	}

}
