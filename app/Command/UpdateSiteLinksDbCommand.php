<?php namespace App\Command;

use App\Service\MediawikiClient;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSiteLinksDbCommand extends Command {

	public function getName() {
		return 'db:update-sites';
	}

	public function getDescription() {
		return 'Update links to external sites';
	}

	public function getHelp() {
		return 'The <info>%command.name%</info> command reads data from the wiki and updates the links to external sites.';
	}

	/** {@inheritdoc} */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->updateLinks($this->fetchWikiContent($output), $output);
		$output->writeln('Done.');
	}

	/**
	 * @param string $wikiContent
	 * @param OutputInterface $output
	 */
	private function updateLinks($wikiContent, OutputInterface $output) {
		$linksData = $this->extractLinkData($wikiContent);
		if (empty($linksData)) {
			return;
		}
		$output->writeln('Updating site links...');
		$repo = $this->getEntityManager()->getSiteRepository();
		foreach ($linksData as $linkData) {
			$site = $repo->findOneByUrlOrCreate($linkData[1]);
			$site->setName($linkData[2]);
			$site->setDescription(strip_tags($linkData[3]));
			$repo->save($site);
		}
	}

	private function fetchWikiContent(OutputInterface $output) {
		$output->writeln('Fetching wiki content...');
		$mwClient = new MediawikiClient($this->getContainer()->get('buzz'));
		return $mwClient->fetchContent('http://wiki.chitanka.info/Links', 0);
	}

	/**
	 * @param string $wikiContent
	 */
	private function extractLinkData($wikiContent) {
		if (preg_match_all('|class="external text" href="([^"]+)">([^<]+)</a>(.*)|', $wikiContent, $matches, PREG_SET_ORDER)) {
			return $matches;
		}

		return false;
	}
}
