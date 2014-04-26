<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="download_cache_text",
*	indexes={@ORM\Index(name="text_idx", columns={"text_id"})}
* )
*/
class DownloadCacheText extends Entity {
	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="DownloadCache")
	 */
	private $dc;

	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Text")
	 */
	private $text;

	public function setDc($dc) { $this->dc = $dc; }
	public function getDc() { return $this->dc; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

}
