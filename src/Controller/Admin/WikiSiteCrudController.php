<?php namespace App\Controller\Admin;

use App\Entity\WikiSite;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class WikiSiteCrudController extends CrudController {
	protected static $sortField = 'name';
	protected static $entityClass = WikiSite::class;

	public function configureFields(string $pageName): iterable {
		yield $this->idFieldDisabledOnEdit('code', $pageName)->setHelp('help.wikisite.code');
		yield 'name';
		yield Field::new('url')->setHelp('help.wikisite.url');
		yield Field::new('intro')->hideOnIndex()->setHelp('help.wikisite.intro')->setRequired(false);
	}
}
