<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\TextCommentRepository")
* @orm:Table(name="text_comment",
*	uniqueConstraints={@orm:UniqueConstraint(name="user_comment_uniq", columns={"text_id", "rname", "contenthash"})},
*	indexes={
*		@orm:Index(name="user_idx", columns={"user_id"})}
* )
*/
class TextComment
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer $text
	* @orm:ManyToOne(targetEntity="Text", cascade={"ALL"})
	*/
	private $text;

	/**
	* @var string $rname
	* @orm:Column(type="string", length=100)
	*/
	private $rname;

	/**
	* @var integer $user
	* @orm:ManyToOne(targetEntity="User")
	*/
	private $user;

	/**
	* @var text
	* @orm:Column(type="text")
	*/
	private $content;

	/**
	* @var string $contenthash
	* @orm:Column(type="string", length=32)
	*/
	private $contenthash;

	/**
	* @var datetime $time
	* @orm:Column(type="datetime")
	*/
	private $time;

	/**
	* @var string $ip
	* @orm:Column(type="string", length=15)
	*/
	private $ip;

	/**
	* @var integer $replyto
	* @orm:ManyToOne(targetEntity="Comment")
	*/
	private $replyto;

	/**
	* @var boolean $is_shown
	* @orm:Column(type="boolean")
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
