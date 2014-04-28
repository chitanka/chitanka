<?php namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use App\Legacy\Legacy;

class UpdateSiteLinksDbCommand extends CommonDbCommand {

	protected function configure() {
		parent::configure();

		$this
			->setName('db:update-sites')
			->setDescription('Update links to external sites')
			->setHelp(<<<EOT
The <info>db:update-sites</info> command reads data from the wiki and updates the links to external sites.
EOT
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
		$this->updateLinks($this->fetchWikiContent($output), $output, $em);
		$output->writeln('Done.');
	}

	/**
	 * @param string $wikiContent
	 * @param OutputInterface $output
	 * @param EntityManager $em
	 */
	protected function updateLinks($wikiContent, OutputInterface $output, EntityManager $em) {
		$linksData = $this->extractLinkData($wikiContent);
		if (empty($linksData)) {
			return;
		}
		$output->writeln('Updating site links...');
		$repo = $em->getRepository('App:Site');
		foreach ($linksData as $linkData) {
			$site = $repo->findOneByUrlOrCreate($linkData[1]);
			$site->setName($linkData[2]);
			$site->setDescription(strip_tags($linkData[3]));
			$em->persist($site);
		}
		$em->flush();
	}

	protected function fetchWikiContent(OutputInterface $output) {
		$output->writeln('Fetching wiki content...');
		return Legacy::getMwContent('http://wiki.chitanka.info/Links', $this->getContainer()->get('buzz'), 0);
	}

	protected function extractLinkData($wikiContent) {
		if (preg_match_all('|class="external text" href="([^"]+)">([^<]+)</a>(.*)|', $wikiContent, $matches, PREG_SET_ORDER)) {
			return $matches;
		}

		return false;
	}
}
