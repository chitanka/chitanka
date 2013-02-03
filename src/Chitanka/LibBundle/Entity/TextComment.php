<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\TextCommentRepository")
* @ORM\Table(name="text_comment",
*	uniqueConstraints={@ORM\UniqueConstraint(name="user_comment_uniq", columns={"text_id", "rname", "contenthash"})},
*	indexes={
*		@ORM\Index(name="user_idx", columns={"user_id"}),
*		@ORM\Index(name="is_shown_idx", columns={"is_shown"}),
*		@ORM\Index(name="time_idx", columns={"time"})}
* )
*/
class TextComment extends Entity
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="Chitanka\LibBundle\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var integer $text
	 * @ORM\ManyToOne(targetEntity="Text")
	 */
	private $text;

	/**
	 * @var string $rname
	 * @ORM\Column(type="string", length=100)
	 */
	private $rname;

	/**
	 * @var integer $user
	 * @ORM\ManyToOne(targetEntity="User")
	 */
	private $user;

	/**
	 * @var text
	 * @ORM\Column(type="text")
	 */
	private $content;

	/**
	 * @var string $contenthash
	 * @ORM\Column(type="string", length=32)
	 */
	private $contenthash;

	/**
	 * @var datetime $time
	 * @ORM\Column(type="datetime")
	 */
	private $time;

	/**
	 * @var string $ip
	 * @ORM\Column(type="string", length=15)
	 */
	private $ip;

	/**
	 * @var integer $replyto
	 * @ORM\ManyToOne(targetEntity="TextComment")
	 */
	private $replyto;

	/**
	 * @var boolean $is_shown
	 * @ORM\Column(type="boolean")
	 */
	private $is_shown;

	public function getId() { return $this->id; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setRname($rname) { $this->rname = $rname; }
	public function getRname() { return $this->rname; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setContent($content) { $this->content = $content; }
	public function getContent() { return $this->content; }

	public function setContenthash($contenthash) { $this->contenthash = $contenthash; }
	public function getContenthash() { return $this->contenthash; }

	public function setTime($time) { $this->time = $time; }
	public function getTime() { return $this->time; }

	public function setIp($ip) { $this->ip = $ip; }
	public function getIp() { return $this->ip; }

	public function setReplyto($replyto) { $this->replyto = $replyto; }
	public function getReplyto() { return $this->replyto; }

	public function setIsShown($isShown) { $this->is_shown = $isShown; }
	public function getIsShown() { return $this->is_shown; }

}
