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
				'options' => $this->config->get( 'CreateWikiCategories' ),
				'default' => 'uncategorised',
			],
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm
			->setSubmitCallback( [ $this, 'redirectWiki' ] )
			->setMethod( 'post' )
			->setWrapperLegendMsg( 'randomwiki-parameters' )
			->setSubmitTextMsg( 'search' )
			->prepareForm()
			->show();
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
		$randomwiki = WikiDiscoverRandom::randomWiki( 0, $category = $formData['category'], $formData['language'] );

		header( "Location: https://" . substr( $randomwiki->wiki_dbname, 0, -4 ) . ".{$this->config->get( 'CreateWikiSubdomain' )}/" );

		return true;
	}
}
