<?php namespace App\Tests\Feed;

use App\Tests\TestCase;
use App\Feed\FeedCleaner;

class FeedCleanerTest extends TestCase {
	public function testRemoveScriptContent() {
		$html = <<<HTML
Some text
<script src="http://example"></script>
More text
<script src="http://example"><!-- dummy --></script>
Some more text
< script >
	alert('Boo!')
< / script >

<scr<script>Evil</script>ipt>alert("Hey!");</script>
HTML;
		$cleanedHtml = FeedCleaner::removeScriptContent($html);
		$this->assertNotContains('script', $cleanedHtml, 'Script tags should be removed');
		$this->assertContains('More text', $cleanedHtml, 'Text inbetween shoud stay');
		$this->assertContains('Some more text', $cleanedHtml, 'Text inbetween shoud stay');
	}

	public function testRemoveImageBeacons() {
		$html = 'Some text <img alt="" border="0" height="1" src="http://beacon" width="1">';
		$cleanedHtml = FeedCleaner::removeImageBeacons($html);
		$this->assertNotContains('<img', $cleanedHtml);
	}

	public function testRemoveImageBeaconsButLeaveNormalImages() {
		$html = <<<HTML
<img src="http://example/normalimage" width="100">
Some text <img alt="" border="0" height="1" src="http://beacon" width="1">
HTML;
		$cleanedHtml = FeedCleaner::removeImageBeacons($html);
		$this->assertNotContains('beacon', $cleanedHtml);
		$this->assertContains('normalimage', $cleanedHtml);
	}

	public function testRemoveSocialFooterLinks() {
		$html = '<a href="https://test">test</a> <a href="http://feeds.wordpress.com/1.0/godelicious/test/" rel="nofollow"><img alt="" border="0" src="http://feeds.wordpress.com/1.0/delicious/test/"></a> <a href="http://feeds.wordpress.com/1.0/gofacebook/test/" rel="nofollow"><img alt="" border="0" src="http://feeds.wordpress.com/1.0/facebook/test/"></a>';
		$cleanedHtml = FeedCleaner::removeSocialFooterLinks($html);
		echo ($cleanedHtml);
		$this->assertNotContains('<img', $cleanedHtml);
	}

}
