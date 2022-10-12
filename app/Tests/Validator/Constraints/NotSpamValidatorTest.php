<?php namespace App\Tests\Validator\Constraints;

use App\Tests\TestCase;
use App\Validator\Constraints\NotSpam;
use App\Validator\Constraints\NotSpamValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NotSpamValidatorTest extends TestCase {

	/** @dataProvider data_validate */
	public function test_validate($value, NotSpam $constraint, bool $expected) {
		$validator = new NotSpamValidator();
		$validator->initialize($this->createMock(ExecutionContextInterface::class));
		$this->assertSame($expected, $validator->validate($value, $constraint));
	}

	public function data_validate(): array {
		$standardNotSpam = new NotSpam(null, __DIR__.'/spam_phrases.ini');
		return [
			["Hello!\nYour inheritance is awaiting you.", $standardNotSpam, false],
			["Bon jour!\nYou just won the lottery.", $standardNotSpam, false],
			'regexp delimiter present' => ["Sorry, this is spam, because this is a #stop phrase.", $standardNotSpam, false],
			["Make the biggest profit.", $standardNotSpam, false],
			["Bon jour!\nHave a nice day!", $standardNotSpam, true],
		];
	}
}
