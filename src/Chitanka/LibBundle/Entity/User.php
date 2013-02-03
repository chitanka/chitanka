<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
#use FOS\UserBundle\Entity\User as BaseUser;
use Chitanka\LibBundle\Legacy\Setup;
use Chitanka\LibBundle\Legacy\Legacy;

/**
 * @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\UserRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="user",
 *	indexes={
 *		@ORM\Index(name="realname_idx", columns={"realname"}),
 *		@ORM\Index(name="email_idx", columns={"email"})}
 * )
 * @UniqueEntity(fields="username")
 */
class User /*extends BaseUser*/ implements UserInterface
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="Chitanka\LibBundle\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	* @var string $username
	* @ORM\Column(type="string", length=100, unique=true)
	*/
	private $username = '~anon';

	/**
	* @var string $realname
	* @ORM\Column(type="string", length=120, nullable=true)
	*/
	private $realname;

	/**
	* @var string $password
	* @ORM\Column(type="string", length=40)
	*/
	private $password;

	/**
	* @var string
	* @ORM\Column(type="string", length=100, nullable=true)
	*/
	private $algorithm;

	/**
	* @var string $newpassword
	* @ORM\Column(type="string", length=40, nullable=true)
	*/
	private $newpassword;

	/**
	* @var string $email
	* @ORM\Column(type="string", length=100, nullable=true)
	*/
	private $email;

	/**
	* @var boolean $allowemail
	* @ORM\Column(type="boolean")
	*/
	private $allowemail = false;

	/**
	* @var array
	* @ORM\Column(type="array")
	*/
	private $groups = array();
	static private $groupList = array(
		'user',
		'workroom-supervisor',
		'workroom-admin',
		'admin',
		'god',
	);

	/**
	* @var boolean $news
	* @ORM\Column(type="boolean")
	*/
	private $news = false;

	/**
	* @var array $opts
	* @ORM\Column(type="array")
	*/
	private $opts = array();

	/**
	* @var integer $login_tries
	* @ORM\Column(type="smallint")
	*/
	private $login_tries = 0;

	/**
	* @var datetime $registration
	* @ORM\Column(type="datetime")
	*/
	private $registration;

	/**
	* @var datetime $touched
	* @ORM\Column(type="datetime")
	*/
	private $touched;

	/**
	* Token used to access private user lists, e.g. read texts
	*
	* @var string
	* @ORM\Column(type="string", length=40, unique=true)
	*/
	private $token;

	/** FIXME doctrine:schema:create does not allow this relation
	* @var array
	* @ORM\ManyToMany(targetEntity="Text", mappedBy="readers")
	* @ORM\JoinTable(name="user_text_read",
	*	joinColumns={@ORM\JoinColumn(name="user_id")},
	*	inverseJoinColumns={@ORM\JoinColumn(name="text_id")})
	*/
	private $readTexts;

	/**
	* @ORM\OneToMany(targetEntity="Bookmark", mappedBy="user")
	*/
	private $bookmarks;


	public function __construct()
	{
		$this->touch();
	}

	public function getId() { return $this->id; }

	public function setUsername($username) { $this->username = $username; }
	public function getUsername() { return $this->username; }

	public function setRealname($realname) { $this->realname = $realname; }
	public function getRealname() { return $this->realname; }

	public function getName()
	{
		return $this->getRealname() ?: $this->getUsername();
	}

	public function setPassword($password, $plain = true)
	{
		$this->password = $plain ? $this->encodePasswordDB($password) : $password;
		$this->algorithm = null;
	}
	public function getPassword() { return $this->password; }
	public function getSalt() { return $this->username; }

	public function setNewpassword($password, $plain = true)
	{
		$this->newpassword = $plain ? $this->encodePasswordDB($password) : $password;
	}
	public function getNewpassword() { return $this->newpassword; }

	public function setAlgorithm($algorithm) { $this->algorithm = $algorithm; }
	public function getAlgorithm() { return $this->algorithm; }

	public function setEmail($email) { $this->email = $email; }
	public function getEmail() { return $this->email; }

	public function setAllowemail($allowemail) { $this->allowemail = $allowemail; }
	public function getAllowemail() { return $this->allowemail; }
	public function allowsEmail() { return $this->allowemail; }

	public function setGroups($groups) { $this->groups = $groups; }
	public function getGroups() { return $this->groups; }
	public function addGroup($group) { $this->groups[] = $group; }
	public function removeGroup($groupToRemove)
	{
		foreach ($this->groups as $i => $group) {
			if ($group->getId() == $groupToRemove->getId()) {
				unset($this->groups[$i]);
			}
		}
	}
	public function inGroup($group)
	{
		foreach ((array) $group as $g) {
			if (in_array($g, $this->groups)) {
				return true;
			}
		}

		return false;
	}

	public function setNews($news) { $this->news = $news; }
	public function getNews() { return $this->news; }

	public function setOpts($opts) { $this->opts = $opts; }
	public function getOpts() { return $this->opts; }

	public function setLoginTries($loginTries) { $this->login_tries = $loginTries; }
	public function getLoginTries() { return $this->login_tries; }
	public function incLoginTries()
	{
		$this->login_tries++;
	}

	public function setRegistration($registration) { $this->registration = $registration; }
	public function getRegistration() { return $this->registration; }

	public function setTouched($touched) { $this->touched = $touched; }
	public function getTouched() { return $this->touched; }

	public function setToken($token) { $this->token = $token; }
	public function getToken() { return $this->token; }

	public function addBookmark($bookmark) { $this->bookmarks[] = $bookmark; }


	public function getExtraStylesheets()
	{
		return isset($this->opts['css']) ? $this->opts['css'] : array();
	}

	public function getExtraJavascripts()
	{
		return isset($this->opts['js']) ? $this->opts['js'] : array();
	}

	public function __toString()
	{
		return $this->getUsername();
	}

	public function getRoles()
	{
		#return array();
		return array_map(function($group){
			return 'ROLE_' . strtoupper($group);
		}, $this->getGroups());
	}

	public function eraseCredentials()
	{
		$this->id = -1;
		$this->username = '~anon';
		$this->password = null;
		$this->logout();
	}

	public function equals(UserInterface $account)
	{
		return $account->getUsername() === $this->username;
	}


	public function isAnonymous()
	{
		return is_null($this->id);
	}

	public function isAuthenticated()
	{
		return ! $this->isAnonymous();
	}


	public function toArray()
	{
		return array(
			'id' => $this->id,
			'username' => $this->username,
			'realname' => $this->realname,
			'password' => $this->password,
			'algorithm' => $this->algorithm,
			'newpassword' => $this->newpassword,
			'email' => $this->email,
			'allowemail' => $this->allowemail,
			'groups' => $this->groups,
			'news' => $this->news,
			'opts' => $this->opts,
			'login_tries' => $this->login_tries,
			'registration' => $this->registration,
			'touched' => $this->touched,
			'token' => $this->token,
		);
	}

	/** @ORM\PrePersist */
	public function preInsert()
	{
		$this->registration = new \DateTime;
		$this->token = $this->generateToken();
		$this->groups[] = 'user';
	}

	/** @ORM\PreUpdate */
	public function preUpdate()
	{
		if (empty($this->email)) {
			$this->allowemail = false;
		}

		if (empty($this->opts['css']['custom'])) {
			unset($this->opts['css']['custom']);
		}
		if (empty($this->opts['js']['custom'])) {
			unset($this->opts['js']['custom']);
		}
	}


	static public $defOptions = array(
		'skin' => 'orange',
		'nav' => 'right', // navigation position
		'css' => array(),
		'js' => array(),
		'news' => false, // receive montly newsletter
		'allowemail' => true, // allow email from other users
		'dlformat' => 'txt.zip', // default format for batch downloading
	);

	protected
		$rights = array(), $options = array(),
		$dlTexts = array(),
		$isHuman = false;







	/** Cookie name for the user ID */
	const UID_COOKIE = 'mli';
	/** Cookie name for the encrypted user password */
	const TOKEN_COOKIE = 'mlt';
	/** Cookie name for the user options */
	const OPTS_COOKIE = 'mlo';
	/** Session key for the User object */
	const U_SESSION = 'user';


	static public function initUser($repo)
	{
		if ( self::isSetSession() ) {
			$user = self::newFromSession();
		} else if ( self::isSetCookie() ) {
			$user = self::newFromCookie($repo);
			$_SESSION[self::U_SESSION] = $user->toArray();
		} else {
			$user = new User;
		}

		return $user;
	}

	/** @return bool */
	static protected function isSetSession() {
		return isset($_SESSION[self::U_SESSION]);
	}


	/** @return bool */
	static protected function isSetCookie() {
		return isset($_COOKIE[self::UID_COOKIE]) && isset($_COOKIE[self::TOKEN_COOKIE]);
	}

	static protected function newFromArray($data)
	{
		$user = new User;
		foreach ($data as $field => $value) {
			$user->$field = $value;
		}

		return $user;
	}

	/** @return User */
	static protected function newFromSession() {
		return self::newFromArray($_SESSION[self::U_SESSION]);
	}

	/** @return User */
	static protected function newFromCookie($repo) {
		$user = $repo->find($_COOKIE[self::UID_COOKIE]);
		if ( $user->validateToken($_COOKIE[self::TOKEN_COOKIE], $user->getPassword()) ) {
			$user->touch();
			$repo->persist($user);

			return $user;
		}

		return new User;
	}





	static public function randomPassword($passLength = 16) {
		$chars = 'abcdefghijkmnopqrstuvwxyz123456789';
		$max = strlen($chars) - 1;
		$password = '';
		for ($i = 0; $i < $passLength; $i++) {
			$password .= $chars{mt_rand(0, $max)};
		}

		return $password;
	}


	/**
		Check a user name for invalid chars.

		@param string $username
		@return mixed true if the user name is ok, or the invalid character
	*/
	static public function isValidUsername($username) {
		$forbidden = '/+#"(){}[]<>!?|~*$&%=\\';
		$len = strlen($forbidden);
		for ($i=0; $i < $len; $i++) {
			if ( strpos($username, $forbidden{$i}) !== false ) {
				return $forbidden{$i};
			}
		}
		return true;
	}



	static public function getDataByName($username) {
		return self::getData( array('username' => $username) );
	}

	static public function getDataById($userId) {
		return self::getData( array('id' => $userId) );
	}

	static public function getData($dbkey) {
		$db = Setup::db();
		$res = $db->select(DBT_USER, $dbkey);
		if ( $db->numRows($res) ==  0) return array();
		return $db->fetchAssoc($res);
	}

	static public function getGroupList()
	{
		return self::$groupList;
	}


	public function showName() {
		return empty($this->realname) ? $this->username : $this->realname;
	}
	public function userName() {
		return $this->username;
	}

	public function set($field, $val) {
		if ( !isset($this->$field) ) return;
		$this->$field = $val;
	}


	public function options() {
		return $this->opts;
	}

	public function option($opt, $default = '') {
		return isset($this->opts[$opt]) ? $this->opts[$opt] : $default;
	}

	public function setOption($name, $val) {
		$this->opts[$name] = $val;
	}


	public function canExecute($action) {
		return true;
	}

	public function isGod() {
		return $this->inGroup('god');
	}

	public function isHuman() {
		return $this->isHuman;
	}

	public function setIsHuman($isHuman) {
		$this->isHuman = $isHuman;
	}



	/**
	* Encode a password in order to save it in the database.
	*
	* @param string $password
	* @return string Encoded password
	*/
	public function encodePasswordDB($plainPassword)
	{
		return sha1(str_repeat($plainPassword . $this->username, 2));
	}


	/**
	* Encode a password in order to save it in a cookie.
	*
	* @param string $password
	* @param bool $plainpass Is this a real password or one already stored
	*                        encoded in the database
	* @return string Encoded password
	*/
	public function encodePasswordCookie($password, $plainpass = true)
	{
		if ($plainpass) {
			$password = $this->encodePasswordDB($password);
		}

		return Legacy::sha1_loop(str_repeat($password, 10), 10);
	}


	/**
	* Validate an entered password.
	* Encodes an entered password and compares it to the password from the database.
	*
	* @param string $inputPass The password from the input
	* @param string $dbPass The password stored in the database
	* @return bool
	*/
	public function validatePassword($inputPass)
	{
		if (empty($this->algorithm)) {
			$encodedPass = $this->encodePasswordDB($inputPass);
		} else {
			eval('$encodedPass = ' . preg_replace('/\$\w+/', "'$inputPass'", $this->algorithm) . ';');
		}

		return strcmp($encodedPass, $this->password) === 0;
	}

	public function validateNewPassword($inputPass)
	{
		return strcmp($this->encodePasswordDB($inputPass), $this->newpassword) === 0;
	}

	/**
	* Validate a token from a cookie.
	* Properly encodes the password from the database and compares it to the token.
	*
	* @param string $cookieToken The token from the cookie
	* @param string $dbPass The password stored in the database
	* @return bool
	*/
	public function validateToken($cookieToken, $dbPass) {
		$enc = $this->encodePasswordCookie($dbPass, false);

		return strcmp($enc, $cookieToken) === 0;
	}


	public function activateNewPassword()
	{
		$this->setPassword($this->getNewpassword(), true);
	}



	public function generateToken()
	{
		return strtoupper(sha1(str_repeat(uniqid() . $this->username, 2)));
	}

	public function login($remember = false)
	{
		// delete a previously generated new password, login_tries
		$this->setNewpassword(null, false);
		$this->setLoginTries(0);
		$this->touch();
		$_COOKIE[self::UID_COOKIE] = $this->getId();
		$_COOKIE[self::TOKEN_COOKIE] = $this->encodePasswordCookie($this->getPassword(), false);

		$cookieExpire = $remember ? null /* default */ : 0 /* end of session */;
		$request = Setup::request();
		$request->setCookie(self::UID_COOKIE, $_COOKIE[self::UID_COOKIE], $cookieExpire);
		$request->setCookie(self::TOKEN_COOKIE, $_COOKIE[self::TOKEN_COOKIE], $cookieExpire);

		return $_SESSION[self::U_SESSION] = $this->toArray();
	}


	public function logout() {
		unset($_SESSION[self::U_SESSION]);
		unset($_COOKIE[self::UID_COOKIE]);
		unset($_COOKIE[self::TOKEN_COOKIE]);
		$request = Setup::request();
		$request->deleteCookie(self::UID_COOKIE);
		$request->deleteCookie(self::TOKEN_COOKIE);
	}


	public function touch()
	{
		$this->setTouched(new \DateTime);
	}

	public function updateSession() {
		$_SESSION[self::U_SESSION] = $this->toArray();
	}


	static public function packOptions( $options ) {
		return serialize($options);
	}


	static public function unpackOptions( $opts_data ) {
		if ( ! empty($opts_data) ) {
			return unserialize($opts_data);
		}

		return array();
	}


	public function getSkinPreference()
	{
		$skin = $this->option('skin', 'orange');
		$nav = $this->option('nav', 'right');

		return "$skin,$nav";
	}
}
