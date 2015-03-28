<?php namespace App\Mail;

use App\Entity\Comment;
use App\Entity\WorkEntry;
use App\Entity\WorkContrib;
use Swift_Message;
use Swift_RfcComplianceException;

class WorkroomNotifier extends Notifier {

	public function sendMailByNewWorkroomComment(Comment $comment, WorkEntry $workEntry, array $recipients) {
		if (empty($recipients)) {
			return;
		}
		$sender = ['NO_REPLY_I_REPEAT_NO_REPLY@chitanka.info' => $comment->getAuthorName().' (Моята библиотека)'];
		$message = Swift_Message::newInstance('Коментар в ателието — '.$workEntry->getTitle());
		$message->setFrom($sender);
		$message->setBody($this->createMailBodyByNewWorkroomComment($comment, $workEntry));
		$headers = $message->getHeaders();
		$headers->addMailboxHeader('Reply-To', $sender);

		foreach ($recipients as $recipientEmail => $recipientName) {
			try {
				$message->setTo($recipientEmail, $recipientName);
			} catch (Swift_RfcComplianceException $e) {
				$this->logError(__METHOD__.": {$e->getMessage()} (recipient: {$recipientName})");
			}
			$this->sendMessage($message);
		}
	}

	private function createMailBodyByNewWorkroomComment(Comment $comment, WorkEntry $workEntry) {
		return <<<BODY
{$comment->getBody()}
_______________________________________________________________________________
Автор на коментара: {$comment->getAuthorName()}
Относно: {$workEntry->getTitle()} ({$workEntry->getAuthor()})

Посетете работното ателие на Моята библиотека, за да отговорите на съобщението.
{$comment->getThread()->getPermalink()}#fos_comment_{$comment->getId()}

BODY;
	}

	public function sendMailByOldWorkEntry(WorkEntry $workEntry) {
		$recipient = $workEntry->getUser();
		if ($recipient->canReceiveEmail()) {
			$sender = ['no-reply@chitanka.info' => 'Ателие (Моята библиотека)'];
			$message = Swift_Message::newInstance('Стар запис — '.$workEntry->getTitle());
			$message->setFrom($sender);
			$message->setBody($this->createMailBodyByOldWorkEntry($workEntry));
			$headers = $message->getHeaders();
			$headers->addMailboxHeader('Reply-To', $sender);

			try {
				$message->setTo($recipient->getEmail(), $recipient->getName());
			} catch (Swift_RfcComplianceException $e) {
				$this->logError(__METHOD__.": {$e->getMessage()} (recipient: {$recipient->getName()})");
			}
			$this->sendMessage($message);
		}

		foreach ($workEntry->getOpenContribs() as $contrib) {
			$this->sendMailByOldWorkContrib($contrib);
		}
	}

	public function sendMailByOldWorkContrib(WorkContrib $contrib) {
		$recipient = $contrib->getUser();
		if ($recipient->canReceiveEmail()) {
			$workEntry = $contrib->getEntry();
			$sender = ['no-reply@chitanka.info' => 'Ателие (Моята библиотека)'];
			$message = Swift_Message::newInstance('Стар запис — '.$workEntry->getTitle());
			$message->setFrom($sender);
			$message->setBody($this->createMailBodyByOldWorkContrib($contrib));
			$headers = $message->getHeaders();
			$headers->addMailboxHeader('Reply-To', $sender);

			try {
				$message->setTo($recipient->getEmail(), $recipient->getName());
			} catch (Swift_RfcComplianceException $e) {
				$this->logError(__METHOD__.": {$e->getMessage()} (recipient: {$recipient->getName()})");
			}
			$this->sendMessage($message);
		}
	}

	private function createMailBodyByOldWorkEntry(WorkEntry $workEntry) {
		return <<<BODY
Здравейте!

В работното ателие на Моята библиотека има ваш запис, който не е бил обновяван от дълго време. Става дума за „{$workEntry->getTitle()}“ ({$workEntry->getAuthor()}).

http://chitanka.info/workroom/entry/{$workEntry->getId()}

Посетете ателието и отбележете текущото състояние на подготовката. В случай че нямате възможност да довършите обработката, качете готовото дотук и запишете, че е нужно някой друг да поеме работата.
_______________________________________________________________________________
Това е автоматично съобщение. Не отговаряйте на него!

BODY;
	}

	private function createMailBodyByOldWorkContrib(WorkContrib $workContrib) {
		$workEntry = $workContrib->getEntry();
		return <<<BODY
Здравейте!

В работното ателие на Моята библиотека има запис, към който сте се включили, който не е бил обновяван от дълго време. Става дума за „{$workEntry->getTitle()}“ ({$workEntry->getAuthor()}).

http://chitanka.info/workroom/entry/{$workEntry->getId()}

Посетете ателието и отбележете текущото състояние на подготовката в полето „Напредък“. В случай че нямате възможност да довършите обработката, качете готовото дотук и запишете, че е нужно някой друг да поеме работата.
_______________________________________________________________________________
Това е автоматично съобщение. Не отговаряйте на него!

BODY;
	}

}
