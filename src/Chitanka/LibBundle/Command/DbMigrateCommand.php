<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Doctrine\ORM\Query\ResultSetMapping;
use Chitanka\LibBundle\Legacy\Legacy;
use Chitanka\LibBundle\Util\String;

class DbMigrateCommand extends Command
{

	protected function configure()
	{
		parent::configure();

		$this
			->setName('db:migrate')
			->setDescription('Migrate old database')
			->addOption('old-db', '', InputOption::VALUE_REQUIRED, 'Old database')
			->setHelp(<<<EOT
The <info>db:migrate</info> command migrates the old database from mylib to the new schema.
EOT
        );
		;
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface  $input  An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 *
	 * @return integer 0 if everything went fine, or an error code
	 *
	 * @throws \LogicException When this abstract class is not implemented
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$options = $input->getOptions();

		$em = $this->container->get('doctrine.orm.default_entity_manager');
		$this->migrateDb($output, $em, $options['old-db']);
		$output->writeln('Done.');
	}


	protected function migrateDb(OutputInterface $output, $em, $olddb)
	{
		$this->prepareOldDatabase($output, $em, $olddb);
		$this->copyTables($output, $em, $olddb);
		$this->convertBooleanColumns($output, $em);
		$this->convertTextSize($output, $em);
		$this->fillTextCountByLabels($output, $em);
		$this->fillCommentCountByTexts($output, $em);
		$this->fillBookCountByCategories($output, $em);
		$this->fillSlugFields($output, $em);
		$this->convertPersonInfoField($output, $em);
		$this->convertUserOptions($output, $em, $olddb);
		$this->fillHasCoverByBooks($output, $em);
		$this->insertBookPersonRelations($output, $em);
		$this->fillUserTextContribDates($output, $em);
	}


	protected function copyTables(OutputInterface $output, $em, $olddb)
	{
		$output->writeln('Copying database tables');
		$conn = $em->getConnection();
		foreach ($this->getRawCopyQueries() as $query) {
			$query = strtr($query, array('%olddb%' => $olddb));
			$output->writeln($query);
			$conn->executeUpdate($query);
		}
	}


	protected function convertBooleanColumns(OutputInterface $output, $em)
	{
		$output->writeln('Converting boolean columns');

		$data = array(
			'text' => array('has_anno'),
			'text_comment' => array('is_shown'),
			'book_text' => array('share_info'),
			'license' => array('free', 'copyright'),
			'work_entry' => array('is_frozen'),
			'work_contrib' => array('is_frozen'),
			'user' => array('allowemail', 'news'),
		);
		$queries = array();
		$conn = $em->getConnection();
		foreach ($data as $model => $fields) {
			foreach ($fields as $field) {
				$conn->executeUpdate(sprintf('UPDATE %s SET %s = 0 WHERE %s = 1', $model, $field, $field));
				$conn->executeUpdate(sprintf('UPDATE %s SET %s = 1 WHERE %s = 2', $model, $field, $field));
			}
		}
	}


	protected function convertTextSize(OutputInterface $output, $em)
	{
		$output->writeln('Converting text size to kibibytes');

		$queries = array();
		$conn = $em->getConnection();
		$sql = 'SELECT id, size, zsize FROM text';
		foreach ($conn->fetchAll($sql) as $text) {
			$queries[] = sprintf('UPDATE text SET size = %d, zsize = %d WHERE id = %d',
				Legacy::int_b2k($text['size']),
				Legacy::int_b2k($text['zsize']),
				$text['id']);
		}

		$this->executeUpdates($queries, $conn);
	}


	protected function fillTextCountByLabels(OutputInterface $output, $em)
	{
		$output->writeln('Calculating text counts by labels');

		$queries = array();
		$conn = $em->getConnection();
		$sql = 'SELECT label_id, COUNT(text_id) count FROM text_label GROUP BY label_id';
		foreach ($conn->fetchAll($sql) as $data) {
			$queries[] = sprintf('UPDATE label SET nr_of_texts = %d WHERE id = %d', $data['count'], $data['label_id']);
		}

		$this->executeUpdates($queries, $conn);
	}


	protected function fillCommentCountByTexts(OutputInterface $output, $em)
	{
		$output->writeln('Calculating comments count by texts');

		$queries = array();
		$conn = $em->getConnection();
		$sql = 'SELECT text_id, COUNT(text_id) count FROM text_comment GROUP BY text_id';
		foreach ($conn->fetchAll($sql) as $data) {
			$queries[] = sprintf('UPDATE text SET comment_count = %d WHERE id = %d', $data['count'], $data['text_id']);
		}

		$this->executeUpdates($queries, $conn);
	}


	protected function fillBookCountByCategories(OutputInterface $output, $em)
	{
		$output->writeln('Calculating book count by categories');

		$queries = array();
		$conn = $em->getConnection();
		$sql = 'SELECT category_id, COUNT(id) count FROM book GROUP BY category_id';
		foreach ($conn->fetchAll($sql) as $data) {
			$queries[] = sprintf('UPDATE category SET nr_of_books = %d WHERE id = %d', $data['count'], $data['category_id']);
		}

		$this->executeUpdates($queries, $conn);
	}

	protected function fillSlugFields(OutputInterface $output, $em)
	{
		$output->writeln('Filling slug fields');

		$queries = array();
		$conn = $em->getConnection();
		$tables = array(
			'book' => 'title',
			'person' => 'orig_name, name',
			'label' => 'name',
			'text' => 'title',
			'series' => 'name',
			'sequence' => 'name',
		);
		foreach ($tables as $table => $field) {
			$slugs = array();
			$sql = sprintf('SELECT id, %s AS name FROM %s', $field, $table);
			foreach ($conn->fetchAll($sql) as $row) {
				$name = $row['name'];
				if (isset($row['orig_name']) && preg_match('/[a-z]/', $row['orig_name'])) {
					$name = $row['orig_name'];
				}
				$slug = String::slugify($name);
				if ($field != 'title') {
					if (isset($slugs[$slug])) {
						$slugs[$slug]++;
						$slug .= $slugs[$slug];
					} else {
						$slugs[$slug] = 1;
					}
				}
				$queries[] = sprintf('UPDATE %s SET slug = "%s" WHERE id = %d', $table, $slug, $row['id']);
			}
		}

		$this->executeUpdates($queries, $conn);
	}


	protected function fillHasCoverByBooks(OutputInterface $output, $em)
	{
		$output->writeln('Setting the "has_cover" field by books');

		$coverDir = $this->container->getParameter('kernel.root_dir').'/../web/content/book-cover';
		$finder = new Finder();
		$finder->files()->name('*.jpg');
		$ids = array();
		foreach ($finder->in($coverDir) as $file) {
			if (preg_match('/(\d+)\.jpg/', $file->getFilename(), $m)) {
				$ids[] = $m[1];
			}
		}

		$query = 'UPDATE book SET has_cover = 1 WHERE id IN ('.implode(',', $ids).')';
		$em->getConnection()->executeUpdate($query);
	}


	protected function insertBookPersonRelations(OutputInterface $output, $em)
	{
		$output->writeln('Initializing missing book-author relations');

		$queries = array();
		$conn = $em->getConnection();
		$sql = 'SELECT id, title_author FROM book WHERE title_author <> ""';
		foreach ($conn->fetchAll($sql) as $book) {
			foreach (explode(',', $book['title_author']) as $name) {
				$sql = sprintf('SELECT id FROM person WHERE name = "%s"', trim($name));
				$person = $conn->fetchArray($sql);
				$queries[] = sprintf('INSERT IGNORE book_author SET book_id = %d, person_id = %d', $book['id'], $person[0]);
			}
		}

		$this->executeUpdates($queries, $conn);
	}


	protected function convertPersonInfoField(OutputInterface $output, $em)
	{
		$output->writeln('Converting info field by persons');
		$query = 'UPDATE person SET info = CONCAT(info, ":", name) WHERE info <> ""';
		$em->getConnection()->executeUpdate($query);
	}


	protected function convertUserOptions(OutputInterface $output, $em, $olddb)
	{
		$output->writeln('Converting user options');

		$queries = array();
		$conn = $em->getConnection();
		$sql = "SELECT id, opts FROM $olddb.user";
		foreach ($conn->fetchAll($sql) as $user) {
			if ( ! empty($user['opts']) ) {
				$opts = @gzinflate($user['opts']);
				if ($opts[0] != 'a') {
					$opts = '';
				}
				$queries[] = sprintf('UPDATE user SET opts = \'%s\' WHERE id = %d', $opts, $user['id']);
			}
		}

		$queries[] = 'UPDATE user SET opts = "a:0:{}" WHERE opts = ""';
		$groups = array(
			'a' => array('user', 'workroom-admin', 'admin'),
			'wa' => array('user', 'workroom-admin'),
			'nu' => array('user'),
		);
		foreach ($groups as $oldGroup => $newGroups) {
			$queries[] = sprintf('UPDATE user SET groups = \'%s\' WHERE groups = \'%s\'', serialize($newGroups), $oldGroup);
		}

		$this->executeUpdates($queries, $conn);
	}


	protected function fillUserTextContribDates(OutputInterface $output, $em)
	{
		$output->writeln('Filling dates by user_text_contrib');

		$queries = array();
		$conn = $em->getConnection();
		$sql = 'SELECT id, created_at FROM text';
		foreach ($conn->fetchAll($sql) as $data) {
			$queries[] = sprintf('UPDATE user_text_contrib SET date = \'%s\' WHERE text_id = %d', $data['created_at'], $data['id']);
		}

		$this->executeUpdates($queries, $conn);
	}


	protected function executeUpdates($updates, $connection)
	{
		$connection->beginTransaction();
		foreach ($updates as $update) {
			$connection->executeUpdate($update);
		}
		$connection->commit();
	}

	protected function prepareOldDatabase(OutputInterface $output, $em, $olddb)
	{
		$output->writeln('Preparing old database');
		$this->nullifyColumnsIfNeeded($output, $em, $olddb);
	}



	protected function nullifyColumnsIfNeeded(OutputInterface $output, $em, $olddb)
	{
		$queries = array(
			'ALTER TABLE `%olddb%`.`text`
				CHANGE `series` `series` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
				CHANGE `license_trans` `license_trans` SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
				CHANGE `year` `year` SMALLINT(4) NULL DEFAULT NULL,
				CHANGE `year2` `year2` SMALLINT(4) NULL DEFAULT NULL,
				CHANGE `trans_year` `trans_year` SMALLINT(4) NULL DEFAULT NULL,
				CHANGE `trans_year2` `trans_year2` SMALLINT(4) NULL DEFAULT NULL',
			'UPDATE `%olddb%`.`text` SET series = NULL WHERE series = 0',
			'UPDATE `%olddb%`.`text` SET license_trans = NULL WHERE license_trans = 0',
			'UPDATE `%olddb%`.`text` SET year = NULL WHERE year = 0',
			'UPDATE `%olddb%`.`text` SET year2 = NULL WHERE year2 = 0',
			'UPDATE `%olddb%`.`text` SET trans_year = NULL WHERE trans_year = 0',
			'UPDATE `%olddb%`.`text` SET trans_year2 = NULL WHERE trans_year2 = 0',
			'ALTER TABLE `%olddb%`.`comment`
				CHANGE `user` `user` INT(11) UNSIGNED NULL DEFAULT NULL,
				CHANGE `replyto` `replyto` INT(11) UNSIGNED NULL DEFAULT NULL',
			'UPDATE `%olddb%`.`comment` SET replyto = NULL WHERE replyto = 0',
			'UPDATE `%olddb%`.`comment` SET user = NULL WHERE user = 0',
			'ALTER TABLE `%olddb%`.`liternews`
				CHANGE `user` `user` INT(11) UNSIGNED NULL DEFAULT NULL',
			'UPDATE `%olddb%`.`liternews` SET user = NULL WHERE user = 0',
		);
		$conn = $em->getConnection();
		foreach ($queries as $query) {
			$query = strtr($query, array('%olddb%' => $olddb));
			$output->writeln($query);
			$conn->executeUpdate($query);
		}
	}

	protected function getRawCopyQueries()
	{
		return array(
			'INSERT INTO `user` (id, username, realname, password, newpassword, email, allowemail, groups, news, opts, login_tries, registration, touched) SELECT id, username, realname, password, newpassword, email, allowemail, `group`, news, opts, login_tries, registration, touched FROM `%olddb%`.`user`',
			'INSERT INTO `category` (`id`, `slug`, `name`) VALUES
				(1, "uncategorized", "Некатегоризирани"),
				(2, "razkazi_v_kartinki", "Разкази в картинки")',
			'INSERT INTO `book` (id, slug, title_author, title, subtitle, orig_title, lang, year, type, mode, category_id) SELECT id, id, title_author, title, subtitle, orig_title, lang, year, type, mode, 1 FROM `%olddb%`.`book`',
			'INSERT INTO `sequence` (id, slug, name) SELECT id, name, name FROM `%olddb%`.`pic_series`',
			'INSERT INTO `book` (slug, title, sequence_id, seqnr, year, trans_year, lang, orig_lang, created_at, type, has_cover, category_id) SELECT id, name, series, sernr, year, trans_year, lang, orig_lang, created_at, "pic", 1, 2 FROM `%olddb%`.`pic`',
			'INSERT INTO `series` (id, slug, name, orig_name, type) SELECT id, name, name, orig_name, type FROM `%olddb%`.`series`',
			'INSERT INTO `license` SELECT * FROM `%olddb%`.`license`',
			'ALTER TABLE text DROP FOREIGN KEY text_ibfk_4',
			'INSERT INTO `text` (id, title, subtitle, lang, trans_year, trans_year2, orig_title, orig_subtitle, orig_lang, year, year2, orig_license_id, trans_license_id, type, series_id, sernr, sernr2, headlevel, size, zsize, created_at, cur_rev_id, dl_count, read_count, comment_count, rating, votes, has_anno, mode) SELECT id, title, subtitle, lang, trans_year, trans_year2, orig_title, orig_subtitle, orig_lang, year, year2, license_orig, license_trans, type, series, sernr, floor((sernr - floor(sernr) ) * 10), headlevel, size, zsize, entrydate, lastedit, dl_count, read_count, comment_count, rating, votes, has_anno, mode FROM `%olddb%`.`text`',
			'INSERT INTO `text_revision` SELECT * FROM `%olddb%`.`edit_history`',
			'ALTER TABLE text ADD FOREIGN KEY (cur_rev_id) REFERENCES text_revision(id)',
			'INSERT INTO `person` (id, slug, name, orig_name, real_name, oreal_name, last_name, country, role, info) SELECT id, name, name, orig_name, real_name, oreal_name, last_name, country, role, info FROM `%olddb%`.`person`',
			'INSERT INTO `person` (slug, person_id, name, last_name, orig_name, type) SELECT CONCAT(name, id), person, name, last_name, orig_name, type FROM `%olddb%`.`person_alt`',
			'INSERT INTO `book_author` (book_id, person_id) SELECT book, author FROM `%olddb%`.`book_author`',
			'INSERT INTO `book_text` (book_id, text_id, pos, share_info) SELECT * FROM `%olddb%`.`book_text`',
			'ALTER TABLE text_comment DROP FOREIGN KEY text_comment_ibfk_3',
			'INSERT INTO `text_comment` (id, text_id, rname, user_id, content, contenthash, time, ip, replyto_id, is_shown) SELECT * FROM `%olddb%`.`comment`',
			'ALTER TABLE text_comment ADD FOREIGN KEY (replyto_id) REFERENCES text_comment(id)',
			'INSERT INTO `text_header` (text_id, nr, level, name, fpos, linecnt) SELECT * FROM `%olddb%`.`header`',
			'INSERT INTO `label` (id, slug, name) SELECT id, name, name FROM `%olddb%`.`label`',
			'INSERT INTO `label_log` SELECT * FROM `%olddb%`.`label_log`',
			'INSERT INTO `question` SELECT * FROM `%olddb%`.`question`',
			'INSERT INTO `series_author` (person_id, series_id) SELECT person, series FROM `%olddb%`.`ser_author_of`',
			'INSERT INTO `text_author` (person_id, text_id, pos, year) SELECT * FROM `%olddb%`.`author_of`',
			'INSERT INTO `text_label` (text_id, label_id) SELECT text, label FROM `%olddb%`.`text_label`',
			'INSERT INTO `text_rating` (text_id, user_id, rating, date) SELECT * FROM `%olddb%`.`text_rating`',
			'INSERT INTO `user_text_read` (user_id, text_id, created_at) SELECT * FROM `%olddb%`.`reader_of`',
			'INSERT INTO `text_translator` (person_id, text_id, pos, year) SELECT * FROM `%olddb%`.`translator_of`',
			'INSERT INTO `work_entry` (id, type, title, author, user_id, comment, date, status, progress, is_frozen, tmpfiles, tfsize, uplfile) SELECT * FROM `%olddb%`.`work`',
			'INSERT INTO `work_contrib` (id, entry_id, user_id, comment, progress, is_frozen, date, uplfile) SELECT * FROM `%olddb%`.`work_multi`',
			'INSERT INTO `user_text_contrib` (user_id, text_id, size, percent) SELECT * FROM `%olddb%`.`user_text`',
			'INSERT INTO wiki_site (code, name, url, intro) VALUES
				("w", "Уикипедия", "http://bg.wikipedia.org/wiki/$1", "По-долу е показана статията за $1 от свободната енциклопедия <a href=\"http://bg.wikipedia.org/\">Уикипедия</a>, която може да се допълва и подобрява от своите читатели. <small>Текстовото й съдържание се разпространява при условията на лиценза „<a href=\"http://creativecommons.org/licenses/by-sa/3.0/\">Криейтив Комънс Признание — Споделяне на споделеното 3.0</a>“</small>."),
				("f", "БГ-Фантастика", "http://bgf.zavinagi.org/index.php/$1", "По-долу е показана статията за $1 от свободната енциклопедия <a href=\"http://bgf.zavinagi.org/\">БГ-Фантастика</a>, която може да се допълва и подобрява от своите читатели. <small>Текстовото й съдържание се разпространява при условията на <a href=\"http://www.gnu.org/copyleft/fdl.html\">GNU Free Documentation License 1.2</a></small>."),
				("m", "Моята библиотека", "http://wiki.chitanka.info/Личност:$1", "")',
			'INSERT INTO book_site (name, url) VALUES
				("SFBG", "http://sfbg.us/book/BOOKID"),
				("ПУК!", "http://biblio.stage.bg/index.php?newsid=BOOKID")',
		);
	}
}
