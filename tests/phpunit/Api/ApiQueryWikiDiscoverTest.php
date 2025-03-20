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
		[ $data ] = $this->doApiRequest(
			[
				'action' => 'query',
				'list' => 'wikidiscover',
				'wdstate' => 'active',
				'wdsiteprop' => 'url|sitename'
			]
		);

		$this->assertArrayHasKey( 'query', $data );
		$this->assertArrayHasKey( 'wikidiscover', $data['query'] );
		$this->assertGreaterThan( 0, count( $data['query']['wikidiscover'] ) );
		foreach ( $data['query']['wikidiscover'] as $wiki ) {
			$this->assertArrayHasKey( 'url', $wiki );
			$this->assertArrayHasKey( 'sitename', $wiki );
		}
	}
}
