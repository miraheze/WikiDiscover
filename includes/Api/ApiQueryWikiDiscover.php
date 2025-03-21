<?php

namespace Miraheze\WikiDiscover\Api;

use MediaWiki\Api\ApiPageSet;
use MediaWiki\Api\ApiQuery;
use MediaWiki\Api\ApiQueryGeneratorBase;
use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\IReadableDatabase;

class ApiQueryWikiDiscover extends ApiQueryGeneratorBase {

	public function __construct( ApiQuery $query, string $moduleName ) {
		parent::__construct( $query, $moduleName, 'wd' );
	}

	public function execute(): void {
		$this->run( null );
	}

	/** @inheritDoc */
	public function getCacheMode( $params ): string {
		return 'public';
	}

	/** @inheritDoc */
	public function executeGenerator( $resultPageSet ): void {
		$this->run( $resultPageSet );
	}

	protected function getDB(): IReadableDatabase {
		$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
		return $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );
	}

	private function run( ?ApiPageSet $resultPageSet ): void {
		$params = $this->extractRequestParams();

		$state = $params['state'];
		$siteprop = $params['siteprop'];
		$limit = $params['limit'];
		$wikislist = $params['wikislist'];

		$this->addTables( 'cw_wikis' );

		if ( $state !== 'all' ) {
			if ( in_array( 'closed', $state ) ) {
				$this->addWhereFld( 'wiki_closed', 1 );
			}

			if ( in_array( 'inactive', $state ) ) {
				$this->addWhereFld( 'wiki_inactive', 1 );
			}

			if ( in_array( 'active', $state ) ) {
				$this->addWhere(
					'wiki_closed = 0 AND wiki_deleted = 0 AND wiki_inactive = 0'
				);
			}

			if ( in_array( 'private', $state ) ) {
				$this->addWhereFld( 'wiki_private', 1 );
			}

			if ( in_array( 'public', $state ) ) {
				$this->addWhereFld( 'wiki_private', 0 );
			}

			if ( in_array( 'deleted', $state ) ) {
				$this->addWhereFld( 'wiki_deleted', 1 );
			}
		}

		$this->addFieldsIf( 'wiki_url', in_array( 'url', $siteprop ) );
		$this->addFieldsIf( 'wiki_dbname', in_array( 'dbname', $siteprop ) );
		$this->addFieldsIf( 'wiki_sitename', in_array( 'sitename', $siteprop ) );
		$this->addFieldsIf( 'wiki_language', in_array( 'languagecode', $siteprop ) );
		$this->addFieldsIf( 'wiki_creation', in_array( 'creation', $siteprop ) );
		$this->addFieldsIf( 'wiki_closed_timestamp', in_array( 'closure', $siteprop ) );

		$this->addFields(
			[
				'wiki_closed',
				'wiki_deleted',
				'wiki_inactive',
				'wiki_locked',
				'wiki_private',
			]
		);

		$this->addOption( 'LIMIT', $limit );

		if ( $wikislist ) {
			$this->addWhereFld( 'wiki_dbname', explode( ',', $wikislist ) );
		}

		$res = $this->select( __METHOD__ );

		$data = [];
		foreach ( $res as $row ) {
			$wiki = [];

			if ( in_array( 'url', $siteprop ) ) {
				$url = $row->wiki_url;
				if ( !$url ) {
					$domain = $this->getConfig()->get( 'CreateWikiSubdomain' );
					$subdomain = substr(
						$row->wiki_dbname, 0,
						-strlen( $this->getConfig()->get( 'CreateWikiDatabaseSuffix' ) )
					);
					$url = "https://$subdomain.$domain";
				}

				$wiki['url'] = $url;
			}

			if ( in_array( 'dbname', $siteprop ) ) {
				$wiki['dbname'] = $row->wiki_dbname;
			}

			if ( in_array( 'sitename', $siteprop ) ) {
				$wiki['sitename'] = $row->wiki_sitename;
			}

			if ( in_array( 'languagecode', $siteprop ) ) {
				$wiki['languagecode'] = $row->wiki_language;
			}

			if ( in_array( 'creation', $siteprop ) ) {
				$wiki['creation'] = wfTimestamp( TS_ISO_8601, $row->wiki_creation );
			}

			$wikiState = match ( true ) {
				(bool)$row->wiki_private => 'private',
				default	=> 'public',
			};

			$wiki[$wikiState] = true;

			switch ( true ) {
				case $row->wiki_deleted:
					$wiki['deleted'] = true;
					break;

				case $row->wiki_closed:
					$wiki['closed'] = true;
					if ( in_array( 'closure', $siteprop ) ) {
						$wiki['closure'] = wfTimestamp( TS_ISO_8601, $row->wiki_closed_timestamp );
					}
					break;

				case $row->wiki_inactive:
					$wiki['inactive'] = true;
					break;

				default:
					$wiki['active'] = true;
			}

			if ( $row->wiki_locked ) {
				$wiki['locked'] = true;
			}

			$data[] = $wiki;
		}

		$result = $this->getResult();
		$result->addValue( [ 'query', $this->getModuleName() ], null, $data );
	}

	/** @inheritDoc */
	protected function getAllowedParams(): array {
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
					'deleted',
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
					'creation',
					'closure',
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
	protected function getExamplesMessages(): array {
		return [
			'action=query&list=wikidiscover' => 'apihelp-query+wikidiscover-example'
		];
	}
}
