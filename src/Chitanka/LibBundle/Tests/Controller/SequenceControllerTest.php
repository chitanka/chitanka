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

	public function testIndexOpds()
	{
		$page = $this->request("sequences.opds");

		$this->assertOpdsPageIs($page, 'sequences');
		$this->assertCountGe(1, $page->filter('entry'));
	}

	public function testListByAlphaByLetterAOpds()
	{
		$route = "sequences/alpha/".urlencode('А').".opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
	}

	public function testShowOpds()
	{
		$sequence = 'BARD-HA1';
		$route = "sequence/$sequence.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
	}
}
