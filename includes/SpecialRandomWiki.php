<?php

class SpecialRandomWiki extends SpecialPage {
	/** @var Config */
	private $config;

	public function __construct() {
		$this->config = $this->getConfig();

		parent::__construct( 'RandomWiki' );
	}

	/** @inheritDoc */
	public function execute( $subPage ) {
		$this->setHeaders();
		$this->outputHeader();

		$out = $this->getOutput();

		$out->addWikiMsg( 'randomwiki-header' );

		$formDescriptor = [
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
				'options' => $this->config->get( 'CreateWikiCategories' ),
				'default' => 'uncategorised',
			],
		];

        if ( $this->config->get( 'CreateWikiUseInactiveWikis' ) ) {
            $formDescriptor['inactive'] = [
				'type' => 'select',
				'name' => 'inactive',
				'label-message' => 'wikidiscover-table-state',
				'options' => [ '(any)' => 'any', 'active' => 'active', 'inactive' => 'inactive' ],
				'default' => 'any',
			];
		}

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm->setSubmitCallback( [ $this, 'redirectWiki' ] )->setMethod( 'post' )->prepareForm()->show();
	}

	/** @inheritDoc */
	protected function getGroupName() {
		return 'wikimanage';
	}

	/**
	 * @param array $formData
	 * @return bool
	 */
	public function redirectWiki( $formData ) {
		$randomwiki = WikiDiscoverRandom::randomWiki( $formData['inactive'], $formData['category'], $formData['language'] );

		if ( $randomwiki->wiki_url ) {
			header( "Location: https://" . $randomwiki->wiki_url . "/" );
		} else {
			header( "Location: https://" . substr( $randomwiki->wiki_dbname, 0, -4 ) . ".{$this->config->get( 'CreateWikiSubdomain' )}/" );
		}

		return true;
	}
}
