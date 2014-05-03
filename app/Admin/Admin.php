<?php namespace App\Admin;

use Sonata\AdminBundle\Admin\Admin as BaseAdmin;
use Symfony\Component\Form\FormEvent;

abstract class Admin extends BaseAdmin {

	protected $translationDomain = 'admin';

	public function fixNewLines(FormEvent $event) {
		$data = $event->getData();
		foreach ($data as $field => $value) {
			if (is_string($value)) {
				$data[$field] = str_replace("\r\n", "\n", $value);
			}
		}
		$event->setData($data);
	}
}
