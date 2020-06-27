<?php

class SpecialRandomWiki extends SpecialPage {

	function __construct() {
		parent::__construct( 'RandomWiki' );
	}

	function execute( $par ) {
		global $wgCreateWikiCategories;

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
				'options' => $wgCreateWikiCategories,
				'default' => 'uncategorised',
			],
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm->setSubmitCallback( [ $this, 'redirectWiki' ] )->setMethod( 'post' )->prepareForm()->show();

	}

	function redirectWiki( $formData ) {
		global $wgCreateWikiSubdomain;

		$randomwiki = WikiDiscoverRandom::randomWiki( 0, $category = $formData['category'], $formData['language'] );

		header( "Location: https://" . substr( $randomwiki->wiki_dbname, 0, -4 ) . ".{$wgCreateWikiSubdomain}/" );

		return true;
	}

	protected function getGroupName() {
		return 'wikimanage';
	}
}
