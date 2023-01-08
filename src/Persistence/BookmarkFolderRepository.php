<?php namespace App\Persistence;

use App\Entity\BookmarkFolder;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class BookmarkFolderRepository extends EntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, BookmarkFolder::class);
	}

	/**
	 * @param User $user
	 * @param string $folderSlug
	 * @param string $folderName
	 */
	public function getOrCreateForUser($user, $folderSlug, $folderName = '') {
		$folder = $this->findOneBy(['slug' => $folderSlug, 'user' => $user->getId()]);
		if ( ! $folder) {
			$folder = new BookmarkFolder();
			$folder->setSlug($folderSlug);
			$folder->setName($folderName ? $folderName : $folderSlug);
			$folder->setUser($user);
		}

		return $folder;
	}

}
