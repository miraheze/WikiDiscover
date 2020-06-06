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

		$language = $this->getRequest()->getText( 'language' );
		$category = $this->getRequest()->getText( 'category' );

		$formDescriptor = [
			'language' => [
				'type' => 'language',
				'name' => 'language',
				'label-message' => 'wikidiscover-table-language',
				'default' => ( $language ) ? $language : 'en',
			],
			'category' => [
				'type' => 'select',
				'name' => 'category',
				'label-message' => 'wikidiscover-table-category',
				'options' => $wgCreateWikiCategories,
				'default' => ( $category ) ? $category : 'uncategorised',
			],
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm->setSubmitCallback( [ $this, 'dummyProcess' ] )->setMethod( 'get' )->prepareForm()->show();

		$pager = new WikiDiscoverWikisPager( $language, $category );
		$pager->getFullOutput();

		$this->getOutput()->addParserOutputContent( $pager );
	}

	static function dummyProcess( $formData ) {
		return false; // Because we need a submission callback but we don't!
	}

	protected function getGroupName() {
		return 'wikimanage';
	}
}
