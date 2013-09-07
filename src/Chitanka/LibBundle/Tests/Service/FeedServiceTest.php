<?php
namespace Chitanka\LibBundle\Tests\Service;

use Chitanka\LibBundle\Tests\TestCase;
use Chitanka\LibBundle\Service\FeedService;

class FeedServiceTest extends TestCase
{
	public function testRemoveScriptContent()
	{
		$service = new FeedService;
		$html = <<<HTML
Some text
<script src="http://example.com"></script>
More text
<script src="http://example.com"><!-- dummy --></script>
Some more text
< script >
	alert('Boo!')
< / script >

<scr<script>Evil</script>ipt>alert("Hey!");</script>
HTML;
		$cleanedHtml = $service->removeScriptContent($html);
		$this->assertNotContains('script', $cleanedHtml, 'Script tags should be removed');
		$this->assertContains('More text', $cleanedHtml, 'Text inbetween shoud stay');
		$this->assertContains('Some more text', $cleanedHtml, 'Text inbetween shoud stay');
	}

	public function testRemoveImageBeacons()
	{
		$service = new FeedService;
		$html = 'Some text <img alt="" border="0" height="1" src="http://beacon.com" width="1">';
		$cleanedHtml = $service->removeImageBeacons($html);
		$this->assertNotContains('<img', $cleanedHtml);
	}

	public function testRemoveImageBeaconsButLeaveNormalImages()
	{
		$service = new FeedService;
		$html = <<<HTML
<img src="http://example.com/normalimage" width="100">
Some text <img alt="" border="0" height="1" src="http://beacon.com" width="1">
HTML;
		$cleanedHtml = $service->removeImageBeacons($html);
		$this->assertNotContains('beacon.com', $cleanedHtml);
		$this->assertContains('normalimage', $cleanedHtml);
	}

}
