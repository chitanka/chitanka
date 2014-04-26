<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Entity\SiteNoticeRepository")
 * @ORM\Table(indexes={
 *   @ORM\Index(columns={"is_active"}),
 *   @ORM\Index(columns={"is_for_front_page"})}
 * )
 */
class SiteNotice extends Entity {

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/** @ORM\Column(type="string", length=60) */
	private $name;

	/** @ORM\Column(type="text") */
	private $content;

	/**
	 * Is the site notice active, i.e. should it be shown to the users
	 * @ORM\Column(type="boolean")
	 */
	private $isActive = true;

	/**
	 * Should the site notice be shown only on front page.
	 * If not, it will be visible on all site pages, except the front page.
	 * @ORM\Column(type="boolean")
	 */
	private $isForFrontPage = false;

	/**
	 * A custom CSS styling, e.g. 'font-size: 200%'
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $style;

	public function getId() { return $this->id; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function getContent() { return $this->content; }
	public function setContent($content) { $this->content = $content; }

	public function setIsActive($isActive) { $this->isActive = $isActive; }
	public function isActive() { return $this->isActive; }
	public function getIsActive() { return $this->isActive(); }

	public function setIsForFrontPage($isForFrontPage) { $this->isForFrontPage = $isForFrontPage; }
	public function isForFrontPage() { return $this->isForFrontPage; }
	public function getIsForFrontPage() { return $this->isForFrontPage(); }

	public function setStyle($style) { $this->style = $style; }
	public function getStyle() { return $this->style; }

	public function __toString() {
		return $this->getName();
	}
}
