<?php

class ApiWikiDiscover extends ApiBase {
	public function __construct( ApiMain $main, $modulename ) {
		parent::__construct( $main, $modulename, 'wd' );
	}

	public function execute() {
		$result = $this->getResult();

		$wikidiscover = new WikiDiscover();

		$params = $this->extractRequestParams();
		$state = array_flip( $params['state'] );
		$siteprop = array_flip( $params['siteprop'] );
		$limit = $params['limit'];

		$wikis = [];

		$wikislist = null;
		if ( isset( $params['wikislist'] ) && $params['wikislist'] ) {
			$wikislist = $params['wikislist'];
		}

		foreach ( $wikidiscover->getWikiPrefixes( $wikislist ) as $wiki ) {
			$dbName = $wiki . 'wiki';

			$data = [];
			$data['url'] = $wikidiscover->getUrl( $dbName );
			$data['dbname'] = $dbName;
			$data['sitename'] = $wikidiscover->getSitename( $dbName );
			$data['languagecode'] = $wikidiscover->getLanguageCode( $dbName );

			$skip = true;
			if ( isset( $state['all'] ) ) {
				$skip = false;
			}

			if ( $wikidiscover->isPrivate( $dbName ) ) {
				$data['private'] = true;

				if ( isset( $state['private'] ) ) {
					$skip = false;
				}
			} else {
				$data['public'] = true;

				if ( isset( $state['public'] ) ) {
					$skip = false;
				}
			}

			if ( $wikidiscover->isDeleted( $dbName ) ) {
				$data['deleted'] = true;

				if ( isset( $state['deleted'] ) ) {
					$skip = false;
				}
			} elseif ( $wikidiscover->isClosed( $dbName ) ) {
				$data['closed'] = true;

				if ( isset( $state['closed'] ) ) {
					$skip = false;
				}
			} elseif ( $wikidiscover->isInactive( $dbName ) ) {
				$data['inactive'] = true;

				if ( isset( $state['inactive'] ) ) {
					$skip = false;
				}
			} else {
				$data['active'] = true;

				if ( isset( $state['active'] ) ) {
					$skip = false;
				}
			}

			if ( $wikidiscover->isLocked( $dbName ) ) {
				$data['locked'] = true;
				$skip = false; // Always include a locked wiki state
			}

			if ( $skip ) {
				continue;
			}

			$wikis[] = $data;
		}

		$result->addValue( null, "wikidiscover", array_slice( $wikis, 0, $limit ) );
	}

	protected function getAllowedParams() {
		return [
			'state' => [
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => [
					'all',
					'closed',
					'inactive',
					'active',
					'private',
					'public',
					'deleted'
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
		return [
			'action=wikidiscover' => 'apihelp-wikidiscover-example'
		];
	}
}
