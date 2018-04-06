<?php

class SpecialWikiDiscover extends SpecialPage {

	function __construct() {
		parent::__construct( 'WikiDiscover' );
	}

	function execute( $par ) {
		global $wgCreateWikiCategories;

		$this->setHeaders();
		$this->outputHeader();

		$out = $this->getOutput();

		$formDescriptor = [
			'language' => [
				'type' => 'select',
				'name' => 'language',
				'label-message' => 'wikidiscover-table-language',
				'options' => $options,
				'default' => $language,
			],
			'category' => [
				'type' => 'select',
				'name' => 'category',
				'label-message' => 'wikidiscover-table-category',
				'options' => $wgCreateWikiCategories,
				'default' => $category,
			],
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm->setAction( $this->getTitle( 'Special:WikiDiscover' )->getLocalURL() )->setMethod( 'get' )->prepareForm();

		$pager = new WikiDiscoverWikisPager( $wiki, $language, $category );
		$table = $pager->getBody();

		$out->addHTML( $pager->getNavigationBar() . $table . $pager->getNavigationBar() );
	}

	protected function getGroupName() {
		return 'wikimanage';
	}
}
