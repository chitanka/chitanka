<?php

namespace Chitanka\LibBundle\Controller;

class TorrentController extends Controller
{
	public function indexAction()
	{
		return $this->display('index');
	}

}
