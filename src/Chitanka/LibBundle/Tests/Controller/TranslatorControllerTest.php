<?php
namespace Chitanka\LibBundle\Tests\Controller;

class TranslatorControllerTest extends PersonControllerTest
{
	protected $routeBase = 'translators';

	/**
	 * @group html
	 */
	public function testShow()
	{
		$page = $this->request("translator/nikolaj-tellalov");

		$this->assertHtmlPageIs($page, 'translator_show');
	}

	/**
	 * @group opds
	 */
	public function testShowOpds()
	{
		$route = "translator/nikolaj-tellalov.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

}
