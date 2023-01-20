<?php

/**
 * @group WikiDiscover
 * @group Database
 * @group medium
 * @coversDefaultClass ApiQueryWikiDiscover
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
				'state' => 'active',
				'siteprop' => 'url|sitename'
			]
		);

		$this->assertArrayHasKey( 'query', $data );
		$this->assertArrayHasKey( 'wikidiscover', $data['query'] );
		$this->assertGreaterThan( 0, count( $data['query']['wikidiscover'][0] ) );
		foreach ( $data['query']['wikidiscover'][0] as $wiki ) {
			$this->assertArrayHasKey( 'url', $wiki );
			$this->assertArrayHasKey( 'sitename', $wiki );
		}
	}
}
