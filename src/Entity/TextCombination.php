<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Persistence\TextCombinationRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table
 */
class TextCombination extends Entity implements \JsonSerializable {

	/**
	 * The smallest ID is always in the field 'text1'.
	 * @var Text
	 * @ORM\Id @ORM\ManyToOne(targetEntity="Text", inversedBy="textCombinations1")
	 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
	 */
	private $text1;

	/**
	 * @var Text
	 * @ORM\Id @ORM\ManyToOne(targetEntity="Text", inversedBy="textCombinations2")
	 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
	 */
	private $text2;

	/**
	 * @var array<int>
	 * @ORM\Column(type="simple_array", nullable=true)
	 */
	private $skippedParagraphs1 = [];

	/**
	 * @var array<int>
	 * @ORM\Column(type="simple_array", nullable=true)
	 */
	private $skippedParagraphs2 = [];

	public function __construct(array $texts, array $data) {
		if (count($texts) < 2) {
			throw new \InvalidArgumentException('Give at least two texts');
		}
		$texts = $this->sortTextsById($texts);
		$this->text1 = $texts[0];
		$this->text2 = $texts[1];
		$this->setData($data);
	}

	public function setData(array $data) {
		$this->skippedParagraphs1 = $this->sortNumbers($data[$this->text1->getId()] ?? []);
		$this->skippedParagraphs2 = $this->sortNumbers($data[$this->text2->getId()] ?? []);
	}

	public function getText1() { return $this->text1; }
	public function getText2() { return $this->text2; }

	public function toArray(): array {
		return [
			$this->text1->getId() => $this->skippedParagraphs1,
			$this->text2->getId() => $this->skippedParagraphs2,
		];
	}

	public function jsonSerialize() {
		return $this->toArray();
	}

	private function sortTextsById(array $texts): array {
		usort($texts, function (Text $text1, Text $text2) {
			return $text1->getId() <=> $text2->getId();
		});
		return $texts;
	}

	private function sortNumbers(array $numbers) {
		sort($numbers);
		return $numbers;
	}
}
