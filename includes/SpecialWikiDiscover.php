<?php

class SpecialWikiDiscover extends SpecialPage {

	public function __construct() {
		parent::__construct( 'WikiDiscover' );
	}

	public function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

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

		$context = new DerivativeContext( $this->getContext() );
		$context->setTitle( $this->getPageTitle() );

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $context );
		$htmlForm
			->setMethod( 'get' )
			->prepareForm()
			->displayForm( false );

		$pager = new WikiDiscoverWikisPager( $this, $language, $category, $state, $visibility );

		$this->getOutput()->addParserOutputContent( $pager->getFullOutput() );
	}

	protected function getGroupName() {
		return 'wikimanage';
	}
}
