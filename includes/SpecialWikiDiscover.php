<?php

class SpecialWikiDiscover extends SpecialPage {

	public function __construct() {
		parent::__construct( 'WikiDiscover' );
	}

	/** @inheritDoc */
	public function execute( $subPage ) {
		$this->setHeaders();
		$this->outputHeader();

		$language = $this->getRequest()->getText( 'language' );
		$category = $this->getRequest()->getText( 'category' );
		$state = $this->getRequest()->getText( 'state' );

		$stateOptions = [
			'(any)' => 'any',
			'Active' => 'active',
			'Locked' => 'locked',
		];

		if ( $this->getConfig()->get( 'CreateWikiUseClosedWikis' ) ) {
			$stateOptions['Closed'] = 'closed';
		}

		$stateOptions['Deleted'] = 'deleted';

		if ( $this->getConfig()->get( 'CreateWikiUseInactiveWikis' ) ) {
			$stateOptions['Inactive'] = 'inactive';
		}

		$formDescriptor = [
			'intro' => [
				'type' => 'info',
				'default' => $this->msg( 'wikidiscover-header-info' )->text(),
			],
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
				'options' => $stateOptions,
				'default' => $state ?: 'any',
			],
		];

		if (
			$this->getConfig()->get( 'CreateWikiUsePrivateWikis' ) &&
			$this->getConfig()->get( 'WikiDiscoverListPrivateWikis' )
		) {
			$visibility = $this->getRequest()->getText( 'visibility' );
			$formDescriptor['visibility'] = [
				'type' => 'select',
				'name' => 'visibility',
				'label-message' => 'wikidiscover-table-visibility',
				'options' => [
					'(any)' => 'any',
					'Public' => 'public',
					'Private' => 'private'
				],
				'default' => $visibility ?: 'any',
			];
		} else {
			$visibility = 'public';
		}

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm
			->setMethod( 'get' )
			->setWrapperLegendMsg( 'wikidiscover-header' )
			->setSubmitTextMsg( 'search' )
			->prepareForm()
			->displayForm( false );

		$pager = new WikiDiscoverWikisPager( $this, $language, $category, $state, $visibility );

		$this->getOutput()->addParserOutputContent( $pager->getFullOutput() );
	}

	/** @inheritDoc */
	protected function getGroupName() {
		return 'wikimanage';
	}
}
