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

		$wikislist = null;
		if ( isset( $params['wikislist'] ) && $params['wikislist'] ) {
			$wikislist = $params['wikislist'];
		}

		foreach ( $wikidiscover->getWikiPrefixes( $wikislist ) as $wiki ) {
			$dbName = $wiki;
			$dbName .= 'wiki';
			$url = $wikidiscover->getUrl( $dbName );

			$data = [];
			$data['url'] = $url;
			$data['dbname'] = $dbName;
			$data['sitename'] = $wikidiscover->getSitename( $dbName );
			$data['languagecode'] = $wikidiscover->getLanguageCode( $dbName );

			$skip = true;
			if ( $all ) {
				$skip = false;
			}

			if ( $wikidiscover->isPrivate( $dbName ) ) {
				$data['private'] = true;

				if ( $private ) {
					$skip = false;
				}
			} else {
				$data['public'] = true;

				if ( $public ) {
					$skip = false;
				}
			}

			if ( $wikidiscover->isClosed( $dbName ) ) {
				$data['closed'] = true;

				if ( $closed ) {
					$skip = false;
				}
			} elseif ( $wikidiscover->isInactive( $dbName ) ) {
				$data['inactive'] = true;

				if ( $inactive ) {
					$skip = false;
				}
			} else {
				$data['active'] = true;

				if ( $active ) {
					$skip = false;
				}
			}

			if ( $skip ) {
				continue;
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
				],
				ApiBase::PARAM_DFLT => 'url|dbname|sitename|languagecode',
			],
			'limit' => [
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => 5000,
				ApiBase::PARAM_MAX2 => 5000,
				ApiBase::PARAM_DFLT => 5000,
			],
			'wikislist' => [
				ApiBase::PARAM_TYPE => 'string',
			],
		];
	}

	protected function getExamplesMessages() {
		return [ 'action=wikidiscover' => 'apihelp-wikidiscover-example' ];
	}
}
