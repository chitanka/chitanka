<?php namespace App\Listener;

use App\Entity\TextRating;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class DoctrineListener {

	/**
	 * @param PreUpdateEventArgs $eventArgs
	 */
	public function preUpdate(PreUpdateEventArgs $eventArgs) {
		if ($eventArgs->getEntity() instanceof TextRating) {
			if ($eventArgs->hasChangedField('rating')) {
				$text = $eventArgs->getEntity()->getText();
				$text->updateAvgRating($eventArgs->getNewValue('rating'), $eventArgs->getOldValue('rating'));
				$eventArgs->getEntityManager()->persist($text);
			}
		}
	}
}
