<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;

class ApiWikiDiscover extends ApiBase {

	/**
	 * @param ApiMain $main
	 * @param string $moduleName Name of this module
	 */
	public function __construct( ApiMain $main, $moduleName ) {
		parent::__construct( $main, $moduleName, 'wd' );
	}

	/** @inheritDoc */
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
			if ( isset( $siteprop['url'] ) ) {
				$data['url'] = $wikidiscover->getUrl( $dbName );
			}
			if ( isset( $siteprop['dbname'] ) ) {
				$data['dbname'] = $dbName;
			}
			if ( isset( $siteprop['sitename'] ) ) {
				$data['sitename'] = $wikidiscover->getSitename( $dbName );
			}
			if ( isset( $siteprop['languagecode'] ) ) {
				$data['languagecode'] = $wikidiscover->getLanguageCode( $dbName );
			}

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

				// Always include a locked wiki state
				$skip = false;
			}

			if ( $skip ) {
				continue;
			}

			$wikis[] = $data;
		}

		$result->addValue( null, "wikidiscover", array_slice( $wikis, 0, $limit ) );
	}

	/** @inheritDoc */
	protected function getAllowedParams() {
		return [
			'state' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'all',
					'closed',
					'inactive',
					'active',
					'private',
					'public',
					'deleted'
				],
				ParamValidator::PARAM_DEFAULT => 'all',
			],
			'siteprop' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'url',
					'dbname',
					'sitename',
					'languagecode',
				],
				ParamValidator::PARAM_DEFAULT => 'url|dbname|sitename|languagecode',
			],
			'limit' => [
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => 5000,
				IntegerDef::PARAM_MAX2 => 5000,
				ParamValidator::PARAM_DEFAULT => 5000,
			],
			'wikislist' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
		];
	}

	/** @inheritDoc */
	protected function getExamplesMessages() {
		return [
			'action=wikidiscover' => 'apihelp-wikidiscover-example'
		];
	}
}
