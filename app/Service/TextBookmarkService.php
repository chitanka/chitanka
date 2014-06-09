<?php namespace App\Service;

use App\Entity\Text;
use App\Entity\User;
use App\Entity\Bookmark;
use App\Entity\BookmarkRepository;
use App\Entity\BookmarkFolderRepository;

class TextBookmarkService {

	private $bookmarkRepo;
	private $bookmarkFolderRepo;
	private $user;

	public function __construct(BookmarkRepository $bookmarkRepo, BookmarkFolderRepository $bookmarkFolderRepo, User $user) {
		$this->bookmarkRepo = $bookmarkRepo;
		$this->bookmarkFolderRepo = $bookmarkFolderRepo;
		$this->user = $user;
	}

	/**
	 *
	 * @param Text $text
	 * @param string $folder
	 * @return Bookmark|null
	 */
	public function addBookmark(Text $text, $folder = 'favorities') {
		$folder = $this->bookmarkFolderRepo->getOrCreateForUser($this->user, $folder);
		$bookmark = $this->bookmarkRepo->findOneBy(array(
			'folder' => $folder->getId(),
			'text' => $text->getId(),
			'user' => $this->user->getId(),
		));
		if ($bookmark) { // an existing bookmark, remove it
			$this->bookmarkRepo->delete($bookmark);
			return null;
		}
		$newBookmark = new Bookmark(array(
			'folder' => $folder,
			'text' => $text,
			'user' => $this->user,
		));
		$this->user->addBookmark($newBookmark);

		$this->bookmarkFolderRepo->save($folder);
		$this->bookmarkRepo->save($newBookmark);
		$this->bookmarkRepo->save($this->user); // check if this is necessary
		return $newBookmark;
	}

}
