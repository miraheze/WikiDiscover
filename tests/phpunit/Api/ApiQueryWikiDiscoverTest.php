<?php

namespace Miraheze\WikiDiscover\Tests\Api;

use MediaWiki\MainConfigNames;
use MediaWiki\Tests\Api\ApiTestCase;
use MediaWiki\WikiMap\WikiMap;

/**
 * @group WikiDiscover
 * @group Database
 * @group medium
 * @coversDefaultClass \Miraheze\WikiDiscover\Api\ApiQueryWikiDiscover
 */
class ApiQueryWikiDiscoverTest extends ApiTestCase {

	/**
	 * @covers ::__construct
	 * @covers ::execute
	 * @covers ::run
	 */
	public function testQueryWikiDiscover() {
		$this->overrideConfigValue( MainConfigNames::VirtualDomainsMapping, [
			'virtual-createwiki' => [ 'db' => 'wikidb' ],
		] );

		[ $data ] = $this->doApiRequest( [
			'action' => 'query',
			'list' => 'wikidiscover',
		] );

		var_dump(
			$this->getServiceContainer()->get( 'CreateWikiDatabaseUtils' )
				->getGlobalReplicaDB()->getDomainID()
		);

		$this->assertArrayHasKey( 'query', $data );
		$this->assertArrayHasKey( 'wikidiscover', $data['query'] );
		$this->assertNotCount( 0, $data['query']['wikidiscover'], 'wikidiscover API response should not be empty' );
		foreach ( $data['query']['wikidiscover'] as $wiki ) {
			$this->assertArrayHasKey( 'dbname', $wiki );
			$this->assertArrayHasKey( 'sitename', $wiki );
		}
	}
}
