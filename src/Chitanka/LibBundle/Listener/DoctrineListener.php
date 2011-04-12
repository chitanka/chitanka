<?php
namespace Chitanka\LibBundle\Listener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Chitanka\LibBundle\Entity\TextRating;

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
