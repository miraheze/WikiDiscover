<?php

class ApiWikiDiscover extends ApiBase {
	public function __construct( ApiMain $main, $modulename ) {
		parent::__construct( $main, $modulename, 'wd' );
	}

	public function execute() {
		$result = $this->getResult();

		$wikidiscover = new WikiDiscover();

		$wikicount = [ 'count' => $wikidiscover->getCount() ];

		$params = $this->extractRequestParams();
		$state = array_flip( $params['state'] );
		$siteprop = array_flip( $params['siteprop'] );
		$limit = $params['limit'];

		$all = isset( $state['all'] );
		$closed = isset( $state['closed'] );
		$inactive = isset( $state['inactive'] );
		$active = isset( $state['active'] );
		$private = isset( $state['private'] );
		$public = isset( $state['public'] );

		$count = 0;
		$wikis = [];
		foreach ( $wikidiscover->getWikis() as $wiki ) {
			$dbName = $wiki;
			$dbName .= 'wiki';
			$url = $wikidiscover->getUrl( $dbName );

			$data = [];
			$data['url'] = $url;
			$data['dbname'] = $dbName;
			$data['sitename'] = $wikidiscover->getSitename( $dbName );
			$data['languagecode'] = $wikidiscover->getLanguageCode( $dbName );
			$data['language'] = $wikidiscover->getLanguage( $dbName );

			if ( $wikidiscover->isPrivate( $dbName ) ) {
				$data['private'] = true;
			} else {
				$data['public'] = true;
			}

			if ( $wikidiscover->isClosed( $dbName ) ) {
				$data['closed'] = true;
			} elseif ( $wikidiscover->isInactive( $dbName ) ) {
				$data['inactive'] = true;
			} else {
				$data['active'] = true;
			}

			$wikis[] = $data;
		}

		$result->addValue( null, "wikidiscover", $wikis );
	}

	public function getAllowedParams() {
		return [
			'state' => [
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => [
					'all',
					'closed',
					'inactive',
					'active',
					'private',
					'public'
				],
				ApiBase::PARAM_DFLT => 'all',
			],
			'siteprop' => [
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => [
					'url',
					'dbname',
					'sitename',
					'languagecode',
					'language',
				],
				ApiBase::PARAM_DFLT => 'url|dbname|sitename|languagecode|language',
			],
			'limit' => [
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => 5000,
				ApiBase::PARAM_DFLT => 5000,
			],
		];
	}

	protected function getExamplesMessages() {
		return [ 'action=wikidiscover' => 'apihelp-wikidiscover-example' ];
	}
}
