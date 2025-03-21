<?php

namespace Miraheze\WikiDiscover\Tests\Api;

use MediaWiki\Tests\Api\ApiTestCase;

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
		[ $data ] = $this->doApiRequest( [
			'action' => 'query',
			'list' => 'wikidiscover',
			'wdstate' => 'active',
			'wdsiteprop' => 'dbname|sitename'
		] );

		var_dump( $data );

		$this->assertArrayHasKey( 'query', $data );
		$this->assertArrayHasKey( 'wikidiscover', $data['query'] );
		$this->assertNotCount( 0, $data['query']['wikidiscover'], 'wikidiscover API response should not be empty' );
		foreach ( $data['query']['wikidiscover'] as $wiki ) {
			$this->assertArrayHasKey( 'dbname', $wiki );
			$this->assertArrayHasKey( 'sitename', $wiki );
		}
	}
}
