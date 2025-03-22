<?php

namespace Miraheze\WikiDiscover\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiPageSet;
use MediaWiki\Api\ApiQuery;
use MediaWiki\Api\ApiQueryGeneratorBase;
use MediaWiki\Api\ApiResult;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Registration\ExtensionRegistry;
use Miraheze\CreateWiki\Services\CreateWikiDatabaseUtils;
use Miraheze\CreateWiki\Services\CreateWikiValidator;
use Miraheze\ManageWiki\Helpers\ManageWikiSettings;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\IReadableDatabase;

class ApiQueryWikiDiscover extends ApiQueryGeneratorBase {

	public function __construct(
		ApiQuery $query,
		string $moduleName,
		private readonly CreateWikiDatabaseUtils $databaseUtils,
		private readonly CreateWikiValidator $validator,
		private readonly ExtensionRegistry $extensionRegistry,
		private readonly LanguageNameUtils $languageNameUtils
	) {
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
		return $this->databaseUtils->getGlobalReplicaDB();
	}

	private function run( ?ApiPageSet $resultPageSet ): void {
		$params = $this->extractRequestParams();
		$result = $this->getResult();

		$category = $params['category'];
		$language = $params['language'];
		$siteprop = $params['siteprop'];
		$state = $params['state'];
		$limit = $params['limit'];
		$offset = $params['offset'];
		$wikis = $params['wikis'];

		$this->addTables( 'cw_wikis' );

		if ( $category ) {
			$this->addWhereFld( 'wiki_category', $category );
		}

		if ( $language ) {
			foreach ( $language as $code ) {
				if ( !$this->languageNameUtils->isSupportedLanguage( $code ) ) {
					$encodedLanguage = $this->encodeParamName( 'language' );
					$this->dieWithError(
						[ 'apierror-invalidlang', $encodedLanguage ],
						'invalidlanguage'
					);
				}
			}

			$this->addWhereFld( 'wiki_language', $language );
		}

		if ( $state !== 'all' ) {
			if ( in_array( 'closed', $state ) ) {
				$this->addWhereFld( 'wiki_closed', 1 );
			}

			if ( in_array( 'inactive', $state ) ) {
				$this->addWhereFld( 'wiki_inactive', 1 );
			}

			if ( in_array( 'exempt', $state ) ) {
				$this->addWhereFld( 'wiki_inactive_exempt', 1 );
			}

			if ( in_array( 'active', $state ) ) {
				$this->addWhere( [
					'wiki_closed' => 0,
					'wiki_deleted' => 0,
					'wiki_inactive' => 0,
				] );
			}

			if ( in_array( 'open', $state ) ) {
				$this->addWhere( [
					'wiki_closed' => 0,
					'wiki_deleted' => 0,
				] );
			}

			if ( in_array( 'locked', $state ) ) {
				$this->addWhereFld( 'wiki_locked', 1 );
			}

			if ( in_array( 'unlocked', $state ) ) {
				$this->addWhereFld( 'wiki_locked', 0 );
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

		$this->addWhereIf(
			$this->getDB()->expr( 'wiki_url', '!=', null ),
			$params['customurl']
		);

		$this->addFieldsIf( 'wiki_category', in_array( 'category', $siteprop ) );
		$this->addFieldsIf( 'wiki_creation', in_array( 'creation', $siteprop ) );
		$this->addFieldsIf( 'wiki_language', in_array( 'languagecode', $siteprop ) );
		$this->addFieldsIf( 'wiki_sitename', in_array( 'sitename', $siteprop ) );
		$this->addFieldsIf( 'wiki_url', in_array( 'url', $siteprop ) );

		$this->addFieldsIf( 'wiki_closed_timestamp', in_array( 'closure', $siteprop ) );
		$this->addFieldsIf( 'wiki_deleted_timestamp', in_array( 'deletion', $siteprop ) );

		$this->addFieldsIf( 'wiki_inactive_exempt_reason', in_array( 'exemptreason', $siteprop ) );

		$this->addFields( [
			'wiki_closed',
			'wiki_dbname',
			'wiki_deleted',
			'wiki_inactive',
			'wiki_inactive_exempt',
			'wiki_locked',
			'wiki_private',
		] );

		$this->addOption( 'LIMIT', $limit );
		$this->addOption( 'OFFSET', $offset );

		if ( $wikis ) {
			$this->addWhereFld( 'wiki_dbname', $wikis );
		}

		$res = $this->select( __METHOD__ );

		$count = 0;
		$data = [];
		foreach ( $res as $row ) {
			if ( ++$count > $limit ) {
				$this->setContinueEnumParameter( 'offset', $offset + $limit );
				break;
			}

			$wiki = [];

			if ( in_array( 'url', $siteprop ) ) {
				$wiki['url'] = $row->wiki_url ?:
					$this->validator->getValidUrl( $row->wiki_dbname );
			}

			$wiki['dbname'] = $row->wiki_dbname;
			if ( in_array( 'description', $siteprop ) ) {
				if ( $this->extensionRegistry->isLoaded( 'ManageWiki' ) ) {
					$manageWikiSettings = new ManageWikiSettings( $wiki['dbname'] );
					$wiki['description'] = $manageWikiSettings->list( 'wgWikiDiscoverDescription' );
				}
			}

			if ( in_array( 'sitename', $siteprop ) ) {
				$wiki['sitename'] = $row->wiki_sitename;
			}

			if ( in_array( 'category', $siteprop ) ) {
				$wiki['category'] = $row->wiki_category;
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
					if ( in_array( 'deletion', $siteprop ) ) {
						$wiki['deletion'] = wfTimestamp( TS_ISO_8601, $row->wiki_deleted_timestamp );
					}
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

				case $row->wiki_inactive_exempt:
					$wiki['inactive'] = 'exempt';
					if ( in_array( 'exemptreason', $siteprop ) ) {
						$reason = $row->wiki_inactive_exempt_reason;
						if ( $this->extensionRegistry->isLoaded( 'ManageWiki' ) ) {
							$options = $this->getConfig()->get( 'ManageWikiInactiveExemptReasonOptions' );
							$reason = array_flip( $options )[$reason] ?? $reason;
						}
						$wiki['exempt-reason'] = $reason;
					}
					break;

				default:
					$wiki['active'] = true;
			}

			if ( $row->wiki_locked ) {
				$wiki['locked'] = true;
			}

			$fit = $result->addValue( [ 'query', $this->getModuleName(), 'wikis' ], $row->wiki_dbname, $wiki );
			if ( !$fit ) {
				$this->setContinueEnumParameter( 'offset', $offset + $count - 1 );
				break;
			}
		}

		$result->addValue(
			[ 'query', $this->getModuleName() ], 'count', $count,
			ApiResult::ADD_ON_TOP | ApiResult::NO_SIZE_CHECK
		);
	}

	/** @inheritDoc */
	protected function getAllowedParams(): array {
		return [
			'category' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => $this->getConfig()->get( 'CreateWikiCategories' ),
			],
			'customurl' => [
				ParamValidator::PARAM_DEFAULT => false,
				ParamValidator::PARAM_TYPE => 'boolean',
			],
			'language' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'state' => [
				ParamValidator::PARAM_DEFAULT => 'all',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'all',
					'active',
					'closed',
					'deleted',
					'exempt',
					'inactive',
					'locked',
					'open',
					'private',
					'public',
					'unlocked',
				],
			],
			'siteprop' => [
				ParamValidator::PARAM_DEFAULT => 'category|languagecode|sitename|url',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'category',
					'closure',
					'creation',
					'description',
					'deletion',
					'exemptreason',
					'languagecode',
					'sitename',
					'url',
				],
			],
			'limit' => [
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => self::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => self::LIMIT_BIG2,
				ParamValidator::PARAM_DEFAULT => self::LIMIT_BIG1,
				ParamValidator::PARAM_TYPE => 'limit',
			],
			'offset' => [
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
				ParamValidator::PARAM_DEFAULT => 0,
			],
			'wikis' => [
				ParamValidator::PARAM_ISMULTI => true,
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
