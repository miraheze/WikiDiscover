<?php

namespace Miraheze\WikiDiscover\Specials;

use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\CreateWiki\Services\CreateWikiDatabaseUtils;
use Miraheze\WikiDiscover\DeletedWikisPager;

class SpecialDeletedWikis extends SpecialPage {

	public function __construct(
		private readonly ExtensionRegistry $extensionRegistry,
		private readonly CreateWikiDatabaseUtils $databaseUtils
	) {
		parent::__construct( 'DeletedWikis' );
	}

	/**
	 * @param ?string $par
	 */
	public function execute( $par ): void {
		$this->setHeaders();
		$this->outputHeader();

		$pager = new DeletedWikisPager(
			$this->extensionRegistry,
			$this->databaseUtils,
			$this->getContext(),
			$this->getLinkRenderer()
		);

		$this->getOutput()->addParserOutputContent( $pager->getFullOutput() );
	}
}
