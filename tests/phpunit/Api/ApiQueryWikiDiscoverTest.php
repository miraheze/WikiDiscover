<?php

namespace Miraheze\WikiDiscover\Tests\Api;

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
	 */
	public function testQueryWikiDiscover(): void {
		$this->insertWiki();
		[ $response ] = $this->doApiRequest( [
			'action' => 'query',
			'list' => 'wikidiscover',
		] );

		$this->assertArrayHasKey( 'query', $response );
		$this->assertArrayHasKey( 'wikidiscover', $response['query'] );
		$this->assertArrayHasKey( 'count', $response['query']['wikidiscover'] );
		$this->assertNotCount( 0, $response['query']['wikidiscover']['wikis'], 'wikidiscover API response should not be empty' );

		foreach ( $response['query']['wikidiscover']['wikis'] as $wiki => $data ) {
			$this->assertArrayHasKey( $wiki, $response['query']['wikidiscover']['wikis'] );
			$this->assertArrayHasKey( 'dbname', $data );
			$this->assertArrayHasKey( 'sitename', $data );
		}
	}

	private function insertWiki(): void {
		$databaseUtils = $this->getServiceContainer()->get( 'CreateWikiDatabaseUtils' );
		$dbw = $databaseUtils->getGlobalPrimaryDB();
		$dbw->newInsertQueryBuilder()
			->insertInto( 'cw_wikis' )
			->row( [
				'wiki_dbname' => WikiMap::getCurrentWikiId(),
				'wiki_dbcluster' => 'c1',
				'wiki_sitename' => 'Central Wiki',
				'wiki_language' => 'en',
				'wiki_private' => 0,
				'wiki_creation' => $dbw->timestamp(),
				'wiki_category' => 'uncategorised',
			] )
			->caller( __METHOD__ )
			->execute();
	}
}
