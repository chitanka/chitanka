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
		$sender = array('NO_REPLY_I_REPEAT_NO_REPLY@chitanka.info' => $comment->getAuthorName().' (Моята библиотека)');
		$message = Swift_Message::newInstance('Kоментар в ателието — '.$workEntry->getTitle())
			->setFrom($sender)
			->setTo(array(
				$recipient->getEmail() => $recipient->getName(),
				'chitanka+workroom@gmail.com' => 'Работно ателие',
			))
			->setBody($this->createMailBodyByNewWorkroomComment($comment, $workEntry));
		$headers = $message->getHeaders();
		$headers->addMailboxHeader('Reply-To', $sender);

		$this->mailer->send($message);
	}

	private function createMailBodyByNewWorkroomComment(Comment $comment, WorkEntry $workEntry)
	{
		return <<<BODY
{$comment->getBody()}
_______________________________________________________________________________
Автор на коментара: {$comment->getAuthorName()}
Относно: {$workEntry->getTitle()} ({$workEntry->getAuthor()})

Посетете работното ателие на Моята библиотека, за да отговорите на съобщението.
{$comment->getThread()->getPermalink()}#fos_comment_{$comment->getId()}

BODY;
	}
}
