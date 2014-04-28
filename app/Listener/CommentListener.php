<?php namespace App\Listener;

use Doctrine\ORM\EntityManager;
use FOS\CommentBundle\Event\CommentEvent;
use FOS\CommentBundle\Event\ThreadEvent;
use App\Service\Notifier;
use App\Entity\Comment;

class CommentListener {
	private $mailer;
	private $em;

	public function __construct(\Swift_Mailer $mailer, EntityManager $em) {
		$this->mailer = $mailer;
		$this->em = $em;
	}

	public function onCommentPostPersist(CommentEvent $event) {
		/* @var $comment Comment */
		$comment = $event->getComment();
		if ($comment->isForWorkEntry() && !$comment->isDeleted()) {
			$notifier = new Notifier($this->mailer);
			$notifier->sendMailByNewWorkroomComment($comment, $comment->getWorkEntry(), $this->loadExtraRecipientsForWorkEntryComment($comment));
		}
	}

	protected function loadExtraRecipientsForWorkEntryComment(Comment $comment) {
		$recipients = array();
		$usernames = array_map('trim', explode(',', $comment->getCc()));
		$users = $this->em->getRepository('App:User')->findByUsernames($usernames);
		foreach ($users as $user) {
			$recipients[$user->getEmail()] = $user->getName();
		}
		$recipients['chitanka+workroom@gmail.com'] = 'Работно ателие';

		return $recipients;
	}

	public function onThreadPostPersist(ThreadEvent $event) {
		/* @var $thread Thread */
		$thread = $event->getThread();
		$target = $thread->getTarget($this->em)->setCommentThread($thread);
		$this->em->persist($target);
		$this->em->flush();
	}
}
