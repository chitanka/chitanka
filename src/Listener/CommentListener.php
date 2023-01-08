<?php namespace App\Listener;

use App\Entity\Comment;
use App\Persistence\EntityManager;
use App\Entity\Thread;
use App\Mail\WorkroomNotifier;
use FOS\CommentBundle\Event\CommentEvent;
use FOS\CommentBundle\Event\ThreadEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class CommentListener {
	private $mailer;
	private $em;
	private $tokenStorage;

	/**
	 * @param \Swift_Mailer $mailer
	 * @param EntityManager $em
	 * @param TokenStorage $tokenStorage
	 */
	public function __construct(\Swift_Mailer $mailer, EntityManager $em, TokenStorage $tokenStorage) {
		$this->mailer = $mailer;
		$this->em = $em;
		$this->tokenStorage = $tokenStorage;
	}

	/**
	 * @param CommentEvent $event
	 */
	public function onCommentPrePersist(CommentEvent $event) {
		$this->registerCommentAuthorByDoctrine($event->getComment());
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

	private function registerCommentAuthorByDoctrine(Comment $comment) {
		$user = $this->em->merge($this->tokenStorage->getToken()->getUser());
		$comment->setAuthor($user);
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
