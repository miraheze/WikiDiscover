<?php

namespace Miraheze\WikiDiscover\Specials;

use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\CreateWiki\Services\CreateWikiValidator;
use Miraheze\WikiDiscover\WikiDiscoverRandom;

class SpecialRandomWiki extends SpecialPage {

	public function __construct(
		private readonly CreateWikiValidator $validator
	) {
		parent::__construct( 'RandomWiki' );
	}

	/** @inheritDoc */
	public function execute( $par ): void {
		$this->setHeaders();
		$this->outputHeader();

		$formDescriptor = [
			'intro' => [
				'type' => 'info',
				'default' => $this->msg( 'randomwiki-header' )->text(),
			],
			'language' => [
				'type' => 'language',
				'name' => 'language',
				'label-message' => 'wikidiscover-table-language',
				'default' => 'en',
			],
			'category' => [
				'type' => 'select',
				'name' => 'category',
				'label-message' => 'wikidiscover-table-category',
				'options' => $this->getConfig()->get( 'CreateWikiCategories' ),
				'default' => 'uncategorised',
			],
		];

		if ( $this->getConfig()->get( 'CreateWikiUseInactiveWikis' ) ) {
			$formDescriptor['state'] = [
				'type' => 'select',
				'name' => 'inactive',
				'label-message' => 'wikidiscover-table-state',
				'options' => [
					'(any)' => 'any',
					'active' => 'active',
					'inactive' => 'inactive',
				],
				'default' => 'any',
			];
		}

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm
			->setSubmitCallback( [ $this, 'redirectWiki' ] )
			->setMethod( 'post' )
			->setWrapperLegendMsg( 'randomwiki-parameters' )
			->setSubmitTextMsg( 'search' )
			->prepareForm()
			->show();
	}

	public function redirectWiki( array $formData ): void {
		$randomWiki = WikiDiscoverRandom::randomWiki(
			$formData['state'] ?? '',
			$formData['category'],
			$formData['language']
		);

		$url = $randomWiki->wiki_url ?:
			$this->validator->getValidUrl( $randomWiki->wiki_dbname );
		$this->getOutput()->redirect( $url );
	}

	/** @inheritDoc */
	protected function getGroupName(): string {
		return 'wikimanage';
	}
}
