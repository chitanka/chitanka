<?php
namespace Chitanka\LibBundle\Listener;

use Doctrine\ORM\EntityManager;
use FOS\CommentBundle\Event\CommentEvent;
use FOS\CommentBundle\Event\ThreadEvent;
use Chitanka\LibBundle\Service\Notifier;
use Chitanka\LibBundle\Entity\Comment;

class CommentListener
{
	private $mailer;
	private $em;

    public function __construct(\Swift_Mailer $mailer, EntityManager $em)
    {
        $this->mailer = $mailer;
		$this->em = $em;
    }

	public function onCommentPostPersist(CommentEvent $event)
	{
		/* @var $comment Comment */
		$comment = $event->getComment();
		if ($comment->isForWorkEntry()) {
			$notifier = new Notifier($this->mailer);
			$notifier->sendMailByNewWorkroomComment($comment, $comment->getWorkEntry(), $this->loadExtraRecipientsForWorkEntryComment($comment));
		}
	}

	protected function loadExtraRecipientsForWorkEntryComment(Comment $comment)
	{
		$recipients = array();
		$usernames = array_map('trim', explode(',', $comment->getCc()));
		$users = $this->em->getRepository('LibBundle:User')->findByUsernames($usernames);
		foreach ($users as $user) {
			$recipients[$user->getEmail()] = $user->getName();
		}
		return $recipients;
	}

	public function onThreadPostPersist(ThreadEvent $event)
	{
		/* @var $thread Thread */
		$thread = $event->getThread();
		$target = $thread->getTarget($this->em)->setCommentThread($thread);
		$this->em->persist($target);
		$this->em->flush();
	}
}
