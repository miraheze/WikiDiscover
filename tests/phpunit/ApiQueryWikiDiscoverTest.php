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
		$data = $this->doApiRequest(
			[
				'action' => 'query',
				'list' => 'wikidiscover',
				'state' => 'active',
				'siteprop' => 'url|sitename'
			]
		);

		$this->assertEquals( 'query', $data[0]['query'] );
		$this->assertArrayHasKey( 'wikidiscover', $data[0]['query'] );
		$this->assertGreaterThan( 0, count( $data[0]['query']['wikidiscover'] ) );
		foreach ( $data[0]['query']['wikidiscover'] as $wiki ) {
			$this->assertArrayHasKey( 'url', $wiki );
			$this->assertArrayHasKey( 'sitename', $wiki );
		}
	}
}
