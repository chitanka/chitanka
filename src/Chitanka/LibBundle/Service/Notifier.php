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
		$sender = array('notifier@chitanka.info' => $comment->getAuthorName().' (Моята библиотека)');
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
		$commentBody = $comment->getBody();
		$authorName = $comment->getAuthorName();
		$title = $workEntry->getTitle();
		$link = $comment->getThread()->getPermalink().'#fos_comment_'.$comment->getId();
		return <<<BODY
$commentBody
_______________________________________________________________________________
Автор на коментара: $authorName
Относно: $title
$link
_______________________________________________________________________________

Посетете работното ателие на Моята библиотека, за да отговорите на съобщението.

BODY;
	}
}
