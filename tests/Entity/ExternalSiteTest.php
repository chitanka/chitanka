<?php namespace App\Tests\Entity;

use App\Entity\ExternalSite;
use App\Tests\TestCase;

class ExternalSiteTest extends TestCase {

	/** @dataProvider data_extractMediaId */
	public function test_extractMediaId(ExternalSite $site, string $input, string $expected) {
		$result = $site->extractMediaId($input);
		$this->assertSame($expected, $result);
	}
	public function data_extractMediaId(): array {
		$site = new ExternalSite();
		$site->setUrl('https://my.site/MEDIAID');
		$siteWoUrl = new ExternalSite();
		return [
			[$site, '123', '123'],
			[$site, 'MEDIAID123', 'MEDIAID123'],
			[$site, 'https://my.site/123', '123'],
			[$site, 'http://my.site/123', 'http://my.site/123'],
			[$siteWoUrl, '123', '123'],
			[$siteWoUrl, 'https://my.site/123', 'https://my.site/123'],
		];
	}
}
