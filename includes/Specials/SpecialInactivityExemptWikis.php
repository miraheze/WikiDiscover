<?php

namespace Miraheze\WikiDiscover\Specials;

use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\CreateWiki\Services\CreateWikiDatabaseUtils;
use Miraheze\CreateWiki\Services\CreateWikiValidator;
use Miraheze\CreateWiki\Services\RemoteWikiFactory;
use Miraheze\WikiDiscover\WikiDiscoverExemptWikisPager;

class SpecialInactivityExemptWikis extends SpecialPage {

	public function __construct(
		private readonly CreateWikiDatabaseUtils $databaseUtils,
		private readonly CreateWikiValidator $validator,
		private readonly LanguageNameUtils $languageNameUtils,
		private readonly RemoteWikiFactory $remoteWikiFactory
	) {
		parent::__construct( 'InactivityExemptWikis' );
	}

	/**
	 * @param ?string $par @phan-unused-param
	 */
	public function execute( $par ): void {
		$this->setHeaders();
		$this->outputHeader();

		if ( !$this->getConfig()->get( 'CreateWikiUseInactiveWikis' ) ) {
			$this->getOutput()->addWikiMsg( 'wikidiscover-inactivityexempt-disabled' );
			return;
		}

		$category = $this->getRequest()->getText( 'category' );
		$language = $this->getRequest()->getText( 'language' );

		$formDescriptor = [
			'intro' => [
				'type' => 'info',
				'default' => $this->msg( 'wikidiscover-inactivityexempt-header-info' )->parse(),
				'raw' => true,
			],
			'language' => [
				'type' => 'language',
				'name' => 'language',
				'label-message' => 'wikidiscover-table-language',
				'default' => $this->getRequest()->getText( 'language', '*' ),
				'options' => [
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
				'default' => $this->getRequest()->getText( 'category', '*' ),
			],
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm
			->setMethod( 'get' )
			->setWrapperLegendMsg( 'wikidiscover-inactivityexempt-header' )
			->setSubmitTextMsg( 'search' )
			->prepareForm()
			->displayForm( false );

		$pager = new WikiDiscoverExemptWikisPager(
			$this->getContext(),
			$this->databaseUtils,
			$this->getLinkRenderer(),
			$this->validator,
			$this->languageNameUtils,
			$this->remoteWikiFactory,
			$category,
			$language
		);

		$table = $pager->getFullOutput();
		$parserOptions = ParserOptions::newFromContext( $this->getContext() );
		$this->getOutput()->addParserOutputContent( $table, $parserOptions );
	}

	/** @inheritDoc */
	protected function getGroupName(): string {
		return 'wiki';
	}
}
