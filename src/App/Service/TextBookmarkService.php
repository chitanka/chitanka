<?php
namespace App\Service;

use Doctrine\ORM\EntityManager;
use App\Entity\Text;
use App\Entity\User;
use App\Entity\Bookmark;
use App\Entity\BookmarkRepository;
use App\Entity\BookmarkFolderRepository;

class TextBookmarkService {

	private $em;
	private $user;

	public function __construct(EntityManager $em, User $user) {
		$this->em = $em;
		$this->user = $user;
	}

	public function addBookmark(Text $text, $folder = 'favorities') {
		$folder = $this->getBookmarkFolderRepository()->getOrCreateForUser($this->user, $folder);
		$bookmark = $this->getBookmarkRepository()->findOneBy(array(
			'folder' => $folder->getId(),
			'text' => $text->getId(),
			'user' => $this->user->getId(),
		));
		if ($bookmark) { // an existing bookmark, remove it
			$this->em->remove($bookmark);
			$this->em->flush();
			return null;
		}
		$newBookmark = new Bookmark(array(
			'folder' => $folder,
			'text' => $text,
			'user' => $this->user,
		));
		$this->user->addBookmark($newBookmark);

		$this->em->persist($folder);
		$this->em->persist($newBookmark);
		$this->em->persist($this->user);
		$this->em->flush();
		return $newBookmark;
	}

	/** @return BookmarkFolderRepository */
	protected function getBookmarkFolderRepository() {
		return $this->em->getRepository('App:BookmarkFolder');
	}

	/** @return BookmarkRepository */
	protected function getBookmarkRepository() {
		return $this->em->getRepository('App:Bookmark');
	}
}
