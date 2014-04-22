<?php
namespace App\Listener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use App\Entity\TextRating;

class DoctrineListener
{

	public function preUpdate(PreUpdateEventArgs $eventArgs)
	{echo '#####';exit;
		if ($eventArgs->getEntity() instanceof TextRating) {
			if ($eventArgs->hasChangedField('rating')) {
				$text = $eventArgs->getEntity()->getText();
				$text->updateAvgRating($eventArgs->getNewValue('rating'), $eventArgs->getOldValue('rating'));
				$eventArgs->getEntityManager()->persist($text);
			}
		}
	}
}
