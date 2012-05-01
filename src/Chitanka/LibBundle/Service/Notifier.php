<?php
namespace Chitanka\LibBundle\Service;

use \Swift_Mailer;
use \Swift_Message;
use Chitanka\LibBundle\Entity\Comment;
use Chitanka\LibBundle\Entity\WorkEntry;
use Chitanka\LibBundle\Entity\User;

class Notifier {

	private $mailer;

    public function __construct(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

	public function sendMailByNewWorkroomComment(Comment $comment, WorkEntry $workEntry)
	{
		/* @var $recipient User */
		$recipient = $comment->hasParent() ? $comment->getParent()->getAuthor() : $workEntry->getUser();
		$message = Swift_Message::newInstance('Нов коментар в работното ателие')
			->setFrom(array('notifier@chitanka.info' => 'Моята библиотека'))
			->setTo(array($recipient->getEmail() => $recipient->getName()))
			->setBody($this->createMailBodyByNewWorkroomComment($comment, $workEntry));

		$this->mailer->send($message);
	}

	private function createMailBodyByNewWorkroomComment(Comment $comment, WorkEntry $workEntry)
	{
		$commentBody = $comment->getBody();
		$authorName = $comment->getAuthorName();
		$title = $workEntry->getTitle();
		$link = 'http://chitanka.info/workroom/entry/'.$workEntry->getId().'#fos_comment_'.$comment->getId();
		return <<<BODY
Здравейте!

В работното ателие на Моята библиотека е пуснато следното съобщение относно „{$title}“. Негов автор е $authorName.

$commentBody

$link
BODY;
	}
}
