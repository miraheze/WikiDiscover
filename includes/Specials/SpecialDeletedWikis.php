<?php

namespace Miraheze\WikiDiscover\Specials;

use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\WikiDiscover\DeletedWikisPager;

class SpecialDeletedWikis extends SpecialPage {

	public function __construct() {
		parent::__construct( 'DeletedWikis' );
	}

	public function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$pager = new DeletedWikisPager( $this );

		$this->getOutput()->addParserOutputContent( $pager->getFullOutput() );
	}
}
