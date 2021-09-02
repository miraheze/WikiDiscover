<?php

class SpecialWikiDiscover extends SpecialPage {

	public function __construct() {
		parent::__construct( 'WikiDiscover' );
	}

	public function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$out = $this->getOutput();

		$language = $this->getRequest()->getText( 'language' );
		$category = $this->getRequest()->getText( 'category' );
		$state = $this->getRequest()->getText( 'state' );
		$visibility = $this->getRequest()->getText( 'visibility' );

		$formDescriptor = [
			'language' => [
				'type' => 'language',
				'options' => [ '(any)' => 'any' ],
				'name' => 'language',
				'label-message' => 'wikidiscover-table-language',
				'default' => $language ?: 'any',
			],
			'category' => [
				'type' => 'select',
				'name' => 'category',
				'label-message' => 'wikidiscover-table-category',
				'options' => [ '(any)' => 'any' ] + $this->getConfig()->get( 'CreateWikiCategories' ),
				'default' => $category ?: 'any',
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
					'Inactive' => 'inactive'
				],
				'default' => $state ?: 'any',
			],
			'visibility' => [
				'type' => 'select',
				'name' => 'visibility',
				'label-message' => 'wikidiscover-table-visibility',
				'options' => [
					'(any)' => 'any',
					'Public' => 'public',
					'Private' => 'private'
				],
				'default' => $visibility ?: 'any',
			],
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm->setSubmitCallback( [ $this, 'dummyProcess' ] )->setMethod( 'get' )->prepareForm()->show();

		$pager = new WikiDiscoverWikisPager( $language, $category, $state, $visibility );

		$this->getOutput()->addParserOutputContent( $pager->getFullOutput() );
	}

	protected function getGroupName() {
		return 'wikimanage';
	}

	private static function dummyProcess( $formData ) {
		// Because we need a submission callback but we don't!
		return false;
	}
}
