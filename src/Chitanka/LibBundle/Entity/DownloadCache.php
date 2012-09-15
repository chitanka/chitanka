<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="download_cache")
*/
class DownloadCache extends Entity
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="bigint")
	*/
	private $id;

	/**
	* @var string $file
	* @ORM\Column(type="string", length=255)
	*/
	private $file;

	/**
	* Get id
	*
	* @return integer $id
	*/
	public function getId()
	{
		return $this->id;
	}

	/**
	* Set file
	*
	* @param string $file
	*/
	public function setFile($file)
	{
		$this->file = $file;
	}

	/**
	* Get file
	*
	* @return string $file
	*/
	public function getFile()
	{
		return $this->file;
	}
}
