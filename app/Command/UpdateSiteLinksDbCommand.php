<?php namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSiteLinksDbCommand extends Command {

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

	/** {@inheritdoc} */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->updateLinks($this->fetchWikiContent($output), $output);
		$output->writeln('Done.');
	}

	/**
	 * @param string $wikiContent
	 * @param OutputInterface $output
	 */
	protected function updateLinks($wikiContent, OutputInterface $output) {
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

	protected function fetchWikiContent(OutputInterface $output) {
		$output->writeln('Fetching wiki content...');
		$mwClient = new MediawikiClient($this->getContainer()->get('buzz'));
		return $mwClient->fetchContent('http://wiki.chitanka.info/Links', 0);
	}

	/**
	 * @param string $wikiContent
	 */
	protected function extractLinkData($wikiContent) {
		if (preg_match_all('|class="external text" href="([^"]+)">([^<]+)</a>(.*)|', $wikiContent, $matches, PREG_SET_ORDER)) {
			return $matches;
		}

		return false;
	}
}
