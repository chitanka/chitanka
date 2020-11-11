<?php namespace App\Entity;

use App\Math\Combiner;

/**
 *
 */
class TextCombinationRepository extends EntityRepository {

	/** @param Text[] $texts */
	public function getForTexts($texts): array {
		$ids = array_map(function(Text $t) { return $t->getId(); }, $texts);
		return array_filter(array_map(function($idPair) {
			return $this->getForIds($idPair);
		}, Combiner::combineIntoSetsOfTwo($ids)));
	}

	/**
	 * Save one or more combinations of texts.
	 * If data contains only two texts, then there is only one combination.
	 * For 3 texts there are 3 combinations, for 4 – 6 and so on.
	 * The number of combinations for a given number of texts `n` is `(n(n-1) / 2)`
	 * @return TextCombination[]
	 */
	public function saveFromArray(array $data): array {
		return array_map(function($data) {
			$combination = $this->getForData($data);
			$this->save($combination);
			return $combination;
		}, Combiner::combineEveryTwoWithKeys($data));
	}

	protected function getForIds(array $ids) {
		if (count($ids) < 2) {
			throw new \InvalidArgumentException('Give at least two IDs');
		}
		// the smallest ID is always in the field 'text1'
		sort($ids);
		return $this->findOneBy(['text1' => $ids[0], 'text2' => $ids[1]]);
	}

	private function getForData(array $data): TextCombination {
		$combination = $this->getForIds(array_keys($data));
		if ($combination) {
			$combination->setData($data);
		} else {
			$combination = $this->constructEntityForData($data);
		}
		return $combination;
	}

	private function constructEntityForData(array $data): TextCombination {
		$texts = $this->_em->getRepository('App:Text')->findByIds(array_keys($data));
		return new TextCombination($texts, $data);
	}

}
