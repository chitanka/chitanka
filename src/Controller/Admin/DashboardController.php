<?php namespace App\Controller\Admin;

use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Country;
use App\Entity\ExternalSite;
use App\Entity\Label;
use App\Entity\Language;
use App\Entity\License;
use App\Entity\Person;
use App\Entity\Question;
use App\Entity\Sequence;
use App\Entity\Series;
use App\Entity\SiteNotice;
use App\Entity\Text;
use App\Entity\TextComment;
use App\Entity\TextType;
use App\Entity\WikiSite;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardController extends AbstractDashboardController {

	/** @var TranslatorInterface */
	private $translator;

	public function __construct(TranslatorInterface $translator) {
		$this->translator = $translator;
	}

	/**
	 * @Route("/admin", name="admin")
	 */
	public function index(): Response {
		return $this->render('Admin/dashboard.html.twig');
	}

	public function configureDashboard(): Dashboard {
		return Dashboard::new()
			->setTitle($this->translator->trans('title.dashboard', [], 'admin'))
			->setTranslationDomain('admin')
		;
	}

	public function configureMenuItems(): iterable {
		yield MenuItem::linkToRoute('Home page', 'fas fa-home', 'homepage');
		yield MenuItem::section('title.main');
		yield MenuItem::linkToCrud('Persons', 'fas fa-user-edit', Person::class);
		yield MenuItem::linkToCrud('Books', 'fas fa-book', Book::class);
		yield MenuItem::linkToCrud('Texts', 'fas fa-book-open', Text::class);
		yield MenuItem::linkToCrud('Sequences', 'fa fa-list-ol', Sequence::class);
		yield MenuItem::linkToCrud('Seriess', 'fas fa-list-ul', Series::class);
		yield MenuItem::linkToCrud('Categories', 'fas fa-folder', Category::class);
		yield MenuItem::linkToCrud('Labels', 'fas fa-tag', Label::class);
		yield MenuItem::linkToCrud('TextComments', 'fas fa-comments', TextComment::class);
		yield MenuItem::section('title.secondary');
		yield MenuItem::linkToCrud('SiteNotices', 'fas fa-bullhorn', SiteNotice::class);
		yield MenuItem::linkToCrud('ExternalSites', 'fa fa-external-link', ExternalSite::class);
		yield MenuItem::linkToCrud('WikiSites', 'fa fa-passport', WikiSite::class);
		yield MenuItem::linkToCrud('Countries', 'fas fa-globe', Country::class);
		yield MenuItem::linkToCrud('Languages', 'fas fa-language', Language::class);
		yield MenuItem::linkToCrud('TextTypes', 'fas fa-font', TextType::class);
		yield MenuItem::linkToCrud('Licenses', 'fas fa-file-contract', License::class);
		yield MenuItem::linkToCrud('Questions', 'fa fa-question', Question::class);
	}

}
