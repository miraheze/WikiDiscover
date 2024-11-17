<?php

namespace Miraheze\WikiDiscover\Specials;

use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\WikiDiscover\DeletedWikisPager;

class SpecialDeletedWikis extends SpecialPage {

	public function __construct() {
		parent::__construct( 'DeletedWikis' );
	}

	/**
	 * @param ?string $par
	 */
	public function execute( $par ): void {
		$this->setHeaders();
		$this->outputHeader();

		$pager = new DeletedWikisPager(
			$this->getContext(),
			$this->getLinkRenderer()
		);

		$this->getOutput()->addParserOutputContent( $pager->getFullOutput() );
	}
}
