<?php namespace App\Listener;

use App\Entity\Comment;
use App\Entity\Thread;
use App\Entity\EntityManager;
use App\Mail\WorkroomNotifier;
use FOS\CommentBundle\Event\CommentEvent;
use FOS\CommentBundle\Event\ThreadEvent;

class CommentListener {
	private $mailer;
	private $em;

	/**
	 * @param \Swift_Mailer $mailer
	 * @param EntityManager $em
	 */
	public function __construct(\Swift_Mailer $mailer, EntityManager $em) {
		$this->mailer = $mailer;
		$this->em = $em;
	}

	/**
	 * @param CommentEvent $event
	 */
	public function onCommentPostPersist(CommentEvent $event) {
		$this->sendNotificationsOnCommentChange($event->getComment());
	}

	/**
	 * @param ThreadEvent $event
	 */
	public function onThreadPostPersist(ThreadEvent $event) {
		$this->attachThreadToTargetEntity($event->getThread());
	}

	private function sendNotificationsOnCommentChange(Comment $comment) {
		if ($comment->isForWorkEntry() && !$comment->isDeleted()) {
			$notifier = new WorkroomNotifier($this->mailer);
			$notifier->sendMailByNewWorkroomComment($comment, $comment->getWorkEntry(), $this->loadExtraRecipientsForWorkEntryComment($comment));
		}
	}

	private function attachThreadToTargetEntity(Thread $thread) {
		$target = $thread->getTarget($this->em)->setCommentThread($thread);
		$this->em->getThreadRepository()->save($target);
	}

	/**
	 * @param Comment $comment
	 * @return string
	 */
	private function loadExtraRecipientsForWorkEntryComment(Comment $comment) {
		$recipients = [];
		$usernames = array_map('trim', explode(',', $comment->getCc()));
		$users = $this->em->getUserRepository()->findByUsernames($usernames);
		foreach ($users as $user) {
			if ($user->canReceiveEmail()) {
				$recipients[$user->getEmail()] = $user->getName();
			}
		}
		$recipients['chitanka+workroom@gmail.com'] = 'Работно ателие';

		return $recipients;
	}

}
