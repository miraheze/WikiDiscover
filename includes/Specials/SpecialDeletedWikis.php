<?php

namespace Miraheze\WikiDiscover\Specials;

use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\WikiDiscover\DeletedWikisPager;
use Wikimedia\Rdbms\IConnectionProvider;

class SpecialDeletedWikis extends SpecialPage {

	private IConnectionProvider $connectionProvider;

	public function __construct( IConnectionProvider $connectionProvider ) {
		parent::__construct( 'DeletedWikis' );

		$this->connectionProvider = $connectionProvider;
	}

	/**
	 * @param ?string $par
	 */
	public function execute( $par ): void {
		$this->setHeaders();
		$this->outputHeader();

		$pager = new DeletedWikisPager(
			$this->getConfig(),
			$this->getContext(),
			$this->connectionProvider,
			$this->getLinkRenderer()
		);

		$this->getOutput()->addParserOutputContent( $pager->getFullOutput() );
	}
}
