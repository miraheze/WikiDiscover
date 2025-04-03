<?php

namespace Miraheze\WikiDiscover\Specials;

use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\CreateWiki\Services\CreateWikiDatabaseUtils;
use Miraheze\CreateWiki\Services\CreateWikiValidator;
use Miraheze\WikiDiscover\WikiDiscoverWikisPager;

class SpecialWikiDiscover extends SpecialPage {

	public function __construct(
		private readonly CreateWikiDatabaseUtils $databaseUtils,
		private readonly CreateWikiValidator $validator,
		private readonly ExtensionRegistry $extensionRegistry,
		private readonly LanguageNameUtils $languageNameUtils
	) {
		parent::__construct( 'WikiDiscover' );
	}

	/** @inheritDoc */
	public function execute( $par ): void {
		$this->setHeaders();
		$this->outputHeader();

		$category = $this->getRequest()->getText( 'category' );
		$language = $this->getRequest()->getText( 'language' );
		$state = $this->getRequest()->getText( 'state' );

		$stateOptions = [
			'wikidiscover-label-any' => 'any',
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
				'options' => [
					// We cannot use options-messages here as otherwise
					// it overrides all language options.
					$this->msg( 'wikidiscover-label-any' )->text() => 'any',
				],
				'name' => 'language',
				'label-message' => 'wikidiscover-table-language',
				'default' => $language ?: 'any',
			],
			'category' => [
				'type' => 'select',
				'name' => 'category',
				'label-message' => 'wikidiscover-table-category',
				'options' => [
					$this->msg( 'wikidiscover-label-any' )->text() => 'any',
				] + $this->getConfig()->get( 'CreateWikiCategories' ),
				'default' => $category ?: 'any',
			],
			'state' => [
				'type' => 'select',
				'name' => 'state',
				'label-message' => 'wikidiscover-table-state',
				'options-messages' => $stateOptions,
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
				'options-messages' => [
					'wikidiscover-label-any' => 'any',
					'wikidiscover-label-public' => 'public',
					'wikidiscover-label-private' => 'private',
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

		$pager = new WikiDiscoverWikisPager(
			$this->getContext(),
			$this->databaseUtils,
			$this->getLinkRenderer(),
			$this->validator,
			$this->extensionRegistry,
			$this->languageNameUtils,
			$category,
			$language,
			$state,
			$visibility
		);

		$this->getOutput()->addParserOutputContent( $pager->getFullOutput() );
	}

	/** @inheritDoc */
	protected function getGroupName(): string {
		return 'wikimanage';
	}
}
