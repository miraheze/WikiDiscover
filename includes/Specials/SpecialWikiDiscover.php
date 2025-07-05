<?php

namespace Miraheze\WikiDiscover\Specials;

use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\CreateWiki\Services\CreateWikiDatabaseUtils;
use Miraheze\CreateWiki\Services\CreateWikiValidator;
use Miraheze\CreateWiki\Services\RemoteWikiFactory;
use Miraheze\WikiDiscover\WikiDiscoverWikisPager;

class SpecialWikiDiscover extends SpecialPage {

	public function __construct(
		private readonly CreateWikiDatabaseUtils $databaseUtils,
		private readonly CreateWikiValidator $validator,
		private readonly LanguageNameUtils $languageNameUtils,
		private readonly RemoteWikiFactory $remoteWikiFactory
	) {
		parent::__construct( 'WikiDiscover' );
	}

	/**
	 * @param ?string $par @phan-unused-param
	 */
	public function execute( $par ): void {
		$this->setHeaders();
		$this->outputHeader();

		$category = $this->getRequest()->getText( 'category' );
		$language = $this->getRequest()->getText( 'language' );
		$state = $this->getRequest()->getText( 'state' );

		$stateOptions = [
			'wikidiscover-label-any' => '*',
			'wikidiscover-label-active' => 'active',
			'wikidiscover-label-locked' => 'locked',
		];

		if ( $this->getConfig()->get( 'CreateWikiUseClosedWikis' ) ) {
			$stateOptions['wikidiscover-label-closed'] = 'closed';
		}

		$stateOptions['wikidiscover-label-deleted'] = 'deleted';

		if ( $this->getConfig()->get( 'CreateWikiUseInactiveWikis' ) ) {
			$stateOptions['wikidiscover-label-inactive'] = 'inactive';
		}

		$formDescriptor = [
			'intro' => [
				'type' => 'info',
				'default' => $this->msg( 'wikidiscover-header-info' )->text(),
			],
			'language' => [
				'type' => 'language',
				'name' => 'language',
				'label-message' => 'wikidiscover-table-language',
				'default' => $language ?: '*',
				'options' => [
					// We cannot use options-messages here as otherwise
					// it overrides all language options.
					$this->msg( 'wikidiscover-label-any' )->text() => '*',
				],
			],
			'category' => [
				'type' => 'select',
				'name' => 'category',
				'label-message' => 'wikidiscover-table-category',
				'options' => [
					$this->msg( 'wikidiscover-label-any' )->text() => '*',
				] + $this->getConfig()->get( 'CreateWikiCategories' ),
				'default' => $category ?: '*',
			],
			'state' => [
				'type' => 'select',
				'name' => 'state',
				'label-message' => 'wikidiscover-table-state',
				'options-messages' => $stateOptions,
				'default' => $state ?: '*',
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
				'options-messages' => [
					'wikidiscover-label-any' => '*',
					'wikidiscover-label-public' => 'public',
					'wikidiscover-label-private' => 'private',
				],
				'default' => $visibility ?: '*',
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

		$pager = new WikiDiscoverWikisPager(
			$this->getContext(),
			$this->databaseUtils,
			$this->getLinkRenderer(),
			$this->validator,
			$this->languageNameUtils,
			$this->remoteWikiFactory,
			$category,
			$language,
			$state,
			$visibility
		);

		$table = $pager->getFullOutput();
		$this->getOutput()->addParserOutputContent( $table );
	}

	/** @inheritDoc */
	protected function getGroupName(): string {
		return 'wiki';
	}
}
