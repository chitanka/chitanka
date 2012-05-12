<?php
namespace Chitanka\LibBundle\Tests\Controller;

class SequenceControllerTest extends WebTestCase
{
	public function testIndex()
	{
		$page = $this->request('sequences');

		$this->assertHtmlPageIs($page, 'sequences');
		$this->assertCount(1, $page->filter('h1'));
	}

	public function testListByAlphaByLetterA()
	{
		$page = $this->request("sequences/alpha/".urlencode('А'));

		$this->assertHtmlPageIs($page, 'sequences_by_alpha');
	}

	public function testShow()
	{
		$sequence = 'BARD-HA1';
		$page = $this->request("sequence/$sequence");

		$this->assertHtmlPageIs($page, 'sequence_show');
	}

	public function testIndexAtom()
	{
		$page = $this->request("sequences.atom");

		$this->assertAtomPageIs($page, 'sequences');
		$this->assertCountGe(1, $page->filter('entry'));
	}

	public function testListByAlphaByLetterAAtom()
	{
		$route = "sequences/alpha/".urlencode('А').".atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
	}

	public function testShowAtom()
	{
		$sequence = 'BARD-HA1';
		$route = "sequence/$sequence.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
	}
}
