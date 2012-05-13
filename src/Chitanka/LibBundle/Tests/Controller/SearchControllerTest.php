<?php
namespace Chitanka\LibBundle\Tests\Controller;

class SearchControllerTest extends WebTestCase
{
	/**
	 * @group html
	 */
	public function testQuery()
	{
		$page = $this->request('search', array('q' => 'test'));

		$this->assertHtmlPageIs($page, 'search');
		$this->assertCount(1, $page->filter('h1'));
	}

}
