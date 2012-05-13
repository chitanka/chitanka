<?php
namespace Chitanka\LibBundle\Tests\Controller;

class SequenceControllerTest extends WebTestCase
{
	/**
	 * @group html
	 */
	public function testIndex()
	{
		$page = $this->request('sequences');

		$this->assertHtmlPageIs($page, 'sequences');
		$this->assertCount(1, $page->filter('h1'));
	}

	/**
	 * @group html
	 */
	public function testListByAlphaByLetterA()
	{
		$page = $this->request("sequences/alpha/".urlencode('А'));

		$this->assertHtmlPageIs($page, 'sequences_by_alpha');
	}

	/**
	 * @group html
	 */
	public function testShow()
	{
		$sequence = 'BARD-HA1';
		$page = $this->request("sequence/$sequence");

		$this->assertHtmlPageIs($page, 'sequence_show');
	}

	/**
	 * @group opds
	 */
	public function testIndexOpds()
	{
		$page = $this->request("sequences.opds");

		$this->assertOpdsPageIs($page, 'sequences');
		$this->assertCountGe(1, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testListByAlphaByLetterAOpds()
	{
		$route = "sequences/alpha/".urlencode('А').".opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
	}

	/**
	 * @group opds
	 */
	public function testShowOpds()
	{
		$sequence = 'BARD-HA1';
		$route = "sequence/$sequence.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
	}
}
