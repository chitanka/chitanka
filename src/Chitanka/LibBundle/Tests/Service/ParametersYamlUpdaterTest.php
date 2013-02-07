<?php
namespace Chitanka\LibBundle\Tests\Service;

use Chitanka\LibBundle\Tests\TestCase;
use Chitanka\LibBundle\Service\ParametersYamlUpdater;

class ParametersYamlUpdaterTest extends TestCase
{
	public function testYamlUpdate()
	{
		$updater = new ParametersYamlUpdater;
		$distYaml = <<<YAML
    database_user:      root
    database_password:  ~
    database_port:      ~
    database_extra:     extra
    assets_base_urls:   ~
    extra:
        - extra1
        - extra2
YAML;
		$currentYaml = <<<YAML
    database_user:      dbuser
    database_password:  "dbpass "
    database_port:
    assets_base_urls:   http://static.chitanka.local
    extra:
        - extra1
YAML;
		$expectedYaml = <<<YAML
    database_user:      dbuser
    database_password:  "dbpass "
    database_port:
    database_extra:     extra
    assets_base_urls:   http://static.chitanka.local
    extra:
        - extra1
        - extra2
YAML;
		$this->assertEquals($expectedYaml, $updater->updateYaml($distYaml, $currentYaml));
	}
}
