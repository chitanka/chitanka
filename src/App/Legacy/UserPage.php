<?php
namespace App\Legacy;

use App\Util\String;
use Sfblib_SfbToHtmlConverter as SfbToHtmlConverter;

class UserPage extends Page {

	protected
		$action = 'user',
		$contribLimit = 50, $defListLimit = 100, $maxListLimit = 400,
		$colCount = 4;

	public function __construct($fields) {
		parent::__construct($fields);

		$this->contentDir = $this->container->getParameter('kernel.root_dir') . '/../web/content/user';

		$this->username = $this->request->value('username', null, 1);
		$this->userpage = $this->request->value('userpage');
		$this->climit = $this->request->value('climit', 1);
		$this->q = $this->request->value(self::FF_QUERY, '');
		$this->initPaginationFields();

		$this->shown_user = $this->controller->getRepository('User')->findOneBy(array('username' => $this->username));
	}


	protected function buildContent() {
		if ( !$this->userExists() ) {
			$this->title = 'Няма такъв потребител';
			return;
		}
		$dataView = $this->makePublicUserDataView();
		if ( $this->user->inGroup('admin') ) {
			$dataView .= $this->makePrivateUserDataView();
		}
		$o = "<table class='content'>$dataView</table>"
			. $this->makeEditOwnPageLink()
			. $this->makeHTML()
			. $this->makeCurrentContribList()
			. $this->makeContribList()
			;

		if ($this->user->getId() == $this->shown_user->getId()) {
			$o .= $this->makeReadList();
			$o .= $this->makeBookmarksList();
		}

		return $o;
	}


	protected function userExists() {
		$key = array('username' => $this->username);
		$sel = array('id userId', 'username', 'realname', 'email', 'registration', 'touched');
		$res = $this->db->select(DBT_USER, $key, $sel);
		$data = $this->db->fetchAssoc($res);
		if ( empty($data) ) {
			$this->addMessage('Няма потребител с име <strong>' .String::myhtmlspecialchars($this->username) . '</strong>.', true);
			$this->userId = 0;
			$this->userpage = '';
			return false;
		}
		Legacy::extract2object($data, $this);
		$this->setDefaultTitle();
		$this->filename = $this->contentDir .'/'. $this->userId;

		return true;
	}


	protected function makeHTML() {
		if ( !file_exists($this->filename) ) {
			return '';
		}

		$converter = new SfbToHtmlConverter($this->filename);

		return $this->userpage = $converter->convert()->getContent();
	}


	protected function makePublicUserDataView() {
		$rcount = sprintf('<a href="%s">%s</a>',
			$this->controller->generateUrl('user_ratings', array('username' => $this->username)),
			$this->getRatingCount()
		);
		$ccount = sprintf('<a href="%s">%s</a>',
			$this->controller->generateUrl('user_comments', array('username' => $this->username)),
			$this->getCommentCount()
		);

		return <<<EOS

	<tr>
		<th>Дадени оценки</th>
		<td>$rcount</td>
	</tr>
	<tr>
		<th>Дадени коментари</th>
		<td>$ccount</td>
	</tr>
EOS;
	}


	protected function makePrivateUserDataView() {
		return <<<EOS

	<tr>
		<th>ID</th>
		<td>$this->userId</td>
	</tr>
	<tr>
		<th>Истинско име</th>
		<td>$this->realname</td>
	</tr>
	<tr>
		<th>Е-поща</th>
		<td>$this->email</td>
	</tr>
	<tr>
		<th>Регистрация</th>
		<td>$this->registration</td>
	</tr>
	<tr>
		<th>Последно влизане</th>
		<td>$this->touched</td>
	</tr>
EOS;
	}


	protected function makeContribList() {
		$repo = $this->controller->getRepository('UserTextContrib');
		$count = $repo->countByUser($this->shown_user);
		if ( ! $count) {
			return '';
		}
		$h = '<h2>Сканирани или обработени текстове</h2>';

		return $h . $this->controller->renderView('App:User:contribs_list.html.twig', array(
			'user' => $this->shown_user,
			'contribs' => $repo->getLatestByUser($this->shown_user, 20),
			'count' => $count
		))
		. sprintf('<p>Общо: <a href="%s">%d</a></p>', $this->controller->generateUrl('user_contribs', array('username' => $this->shown_user->getUsername())), $count);
	}


	protected function makeReadList() {
		$repo = $this->controller->getRepository('UserTextRead');
		$count = $repo->countByUser($this->shown_user);
		if ( ! $count) {
			return '';
		}
		$h = '<h2>Последни прочетени произведения</h2>';

		return $h . $this->controller->renderView('App:User:read_texts_list.html.twig', array(
			'user' => $this->shown_user,
			'is_owner' => true,
			'read_texts' => $repo->getLatestByUser($this->shown_user, 20),
		))
		. sprintf('<p class="more"><a href="%s">Всички</a></p>', $this->controller->generateUrl('user_read_list', array('username' => $this->shown_user->getUsername())));
	}


	protected function makeBookmarksList() {
		$repo = $this->controller->getRepository('Bookmark');
		$count = $repo->countByUser($this->shown_user);
		if ( ! $count) {
			return '';
		}
		$h = '<h2>Последни избрани произведения</h2>';

		return $h . $this->controller->renderView('App:User:bookmarks_list.html.twig', array(
			'user' => $this->shown_user,
			'is_owner' => true,
			'bookmarks' => $repo->getLatestByUser($this->shown_user, 20),
		))
		. sprintf('<p class="more"><a href="%s">Всички</a></p>', $this->controller->generateUrl('user_bookmarks', array('username' => $this->shown_user->getUsername())));
	}


	protected function makeCurrentContribList() {
		$listUrl = sprintf('%s/workroom/list.htmlx?user=%s', $this->container->getParameter('workroom_url'), $this->username);
		$response = $this->container->get('buzz')->get($listUrl);
		if ( !$response->isOk() || strpos($response->getContent(), 'emptylist') !== false ) {
			return '';
		}

		return '<h2>Подготвяни текстове</h2>'. $response->getContent();
	}


	protected function getContribCount() {
		return $this->db->getCount(DBT_USER_TEXT, $this->getDbKey());
	}


	protected function getRatingCount() {
		return $this->db->getCount(DBT_TEXT_RATING, $this->getDbKey());
	}


	protected function getCommentCount() {
		return $this->db->getCount(DBT_COMMENT, $this->getDbKey());
	}


	protected function getDbKey($field = 'user_id') {
		return array($field => $this->userId);
	}


	protected function makeEditOwnPageLink() {
		if ($this->username != $this->user->getUsername()) {
			return '';
		}

		$link = $this->controller->generateUrl('user_page_edit', array('username' => $this->username));

		return "<p style='font-size:small; text-align:right'>[<a href=\"$link\" title=\"Редактиране на личната страница\">редактиране</a>]</p>";
	}


	protected function setDefaultTitle() {
		$this->title = 'Лична страница на '. $this->username;
	}


	protected function getListDbKey() {
		if ( !empty($this->q) ) {
			return array('username' => array('>=', $this->q));
		}
		return array();
	}

}
