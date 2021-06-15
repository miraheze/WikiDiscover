<?php

class SpecialWikiDiscover extends SpecialPage {

	function __construct() {
		parent::__construct( 'WikiDiscover' );
	}

	function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$out = $this->getOutput();

		$language = $this->getRequest()->getText( 'language' );
		$category = $this->getRequest()->getText( 'category' );
		$state = $this->getRequest()->getText( 'state' );

		$formDescriptor = [
			'language' => [
				'type' => 'language',
				'options' => [ '(any)' => 'any' ],
				'name' => 'language',
				'label-message' => 'wikidiscover-table-language',
				'default' => ( $language ) ? $language : 'any',
			],
			'category' => [
				'type' => 'select',
				'name' => 'category',
				'label-message' => 'wikidiscover-table-category',
				'options' => [ '(any)' => 'any' ] + $this->getConfig()->get( 'CreateWikiCategories' ),
				'default' => ( $category ) ? $category : 'any',
			],
			'state' => [
				'type' => 'select',
				'name' => 'state',
				'label-message' => 'wikidiscover-table-state',
				'options' => [
					'(any)' => 'any',
					'Active' => 'active',
					'Closed' => 'closed',
					'Deleted' => 'deleted',
					'Inactive' => 'Inactive'
				],
				'default' => ( $state ) ? $state : 'any',
			],
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm->setSubmitCallback( [ $this, 'dummyProcess' ] )->setMethod( 'get' )->prepareForm()->show();

		$pager = new WikiDiscoverWikisPager( $language, $category, $state );

		$this->getOutput()->addParserOutputContent( $pager->getFullOutput() );
	}

	static function dummyProcess( $formData ) {
		return false; // Because we need a submission callback but we don't!
	}

	protected function getGroupName() {
		return 'wikimanage';
	}
}
