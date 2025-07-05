<?php

namespace Miraheze\WikiDiscover\Specials;

use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\CreateWiki\Services\CreateWikiDatabaseUtils;
use Miraheze\CreateWiki\Services\CreateWikiValidator;
use stdClass;

class SpecialRandomWiki extends SpecialPage {

	public function __construct(
		private readonly CreateWikiDatabaseUtils $databaseUtils,
		private readonly CreateWikiValidator $validator
	) {
		parent::__construct( 'RandomWiki' );
	}

	/**
	 * @param ?string $par @phan-unused-param
	 */
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
				'options-messages' => [
					'wikidiscover-label-any' => '*',
					'wikidiscover-label-active' => 'active',
					'wikidiscover-label-inactive' => 'inactive',
				],
				'default' => '*',
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
		$randomWiki = $this->getRandomWiki(
			$formData['state'] ?? '',
			$formData['category'],
			$formData['language']
		);

		$url = $randomWiki->wiki_url ?:
			$this->validator->getValidUrl( $randomWiki->wiki_dbname );
		$this->getOutput()->redirect( $url );
	}

	private function getRandomWiki(
		string $state,
		string $category,
		string $language
	): stdClass|bool {
		$conditions = [];

		if ( $category ) {
			$conditions['wiki_category'] = $category;
		}

		if ( $language ) {
			$conditions['wiki_language'] = $language;
		}

		if ( $this->getConfig()->get( 'CreateWikiUseInactiveWikis' ) ) {
			if ( $state === 'inactive' ) {
				$conditions['wiki_inactive'] = 1;
			} elseif ( $state === 'active' ) {
				$conditions['wiki_inactive'] = 0;
			}
		}

		// Never randomly offer closed or private wikis
		if ( $this->getConfig()->get( 'CreateWikiUseClosedWikis' ) ) {
			$conditions['wiki_closed'] = 0;
		}

		if ( $this->getConfig()->get( 'CreateWikiUsePrivateWikis' ) ) {
			$conditions['wiki_private'] = 0;
		}

		$conditions['wiki_deleted'] = 0;

		return $this->randFromConds( $conditions );
	}

	private function randFromConds( array $conds ): stdClass|bool {
		$dbr = $this->databaseUtils->getGlobalReplicaDB();

		// MySQL is ever the outlier
		$random_function = $dbr->getType() === 'mysql' ? 'RAND()' : 'random()';

		return $dbr->newSelectQueryBuilder()
			->table( 'cw_wikis' )
			->fields( [ 'wiki_dbname', 'wiki_url' ] )
			->conds( $conds )
			->limit( 1 )
			->caller( __METHOD__ )
			->orderBy( $random_function )
			->fetchRow();
	}

	/** @inheritDoc */
	protected function getGroupName(): string {
		return 'wiki';
	}
}
