<?php

namespace Miraheze\WikiDiscover\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiQuery;
use MediaWiki\Api\ApiQueryBase;
use MediaWiki\Api\ApiResult;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Registration\ExtensionRegistry;
use Miraheze\CreateWiki\Services\CreateWikiDatabaseUtils;
use Miraheze\CreateWiki\Services\CreateWikiValidator;
use Miraheze\ManageWiki\Helpers\ManageWikiSettings;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\IReadableDatabase;

class ApiQueryWikiDiscover extends ApiQueryBase {

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
		$params = $this->extractRequestParams();
		$result = $this->getResult();

		$category = $params['category'];
		$language = $params['language'];
		$prop = $params['prop'];
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

		$this->addFieldsIf( 'wiki_category', in_array( 'category', $prop ) );
		$this->addFieldsIf( 'wiki_creation', in_array( 'creationdate', $prop ) );
		$this->addFieldsIf( 'wiki_language', in_array( 'languagecode', $prop ) );
		$this->addFieldsIf( 'wiki_sitename', in_array( 'sitename', $prop ) );
		$this->addFieldsIf( 'wiki_url', in_array( 'url', $prop ) );

		$this->addFieldsIf( 'wiki_closed_timestamp', in_array( 'closuredate', $prop ) );
		$this->addFieldsIf( 'wiki_deleted_timestamp', in_array( 'deletiondate', $prop ) );
		$this->addFieldsIf( 'wiki_inactive_timestamp', in_array( 'inactivedate', $prop ) );

		$this->addFieldsIf( 'wiki_inactive_exempt_reason', in_array( 'exemptreason', $prop ) );

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
			$wiki['dbname'] = $row->wiki_dbname;

			if ( in_array( 'url', $prop ) ) {
				$wiki['url'] = $row->wiki_url ?:
					$this->validator->getValidUrl( $row->wiki_dbname );
			}

			if ( in_array( 'sitename', $prop ) ) {
				$wiki['sitename'] = $row->wiki_sitename;
			}

			if ( in_array( 'category', $prop ) ) {
				$wiki['category'] = $row->wiki_category;
			}

			if ( in_array( 'languagecode', $prop ) ) {
				$wiki['languagecode'] = $row->wiki_language;
			}

			if ( in_array( 'description', $prop ) ) {
				if ( $this->extensionRegistry->isLoaded( 'ManageWiki' ) ) {
					$manageWikiSettings = new ManageWikiSettings( $wiki['dbname'] );
					$wiki['description'] = $manageWikiSettings->list( 'wgWikiDiscoverDescription' );
				}
			}

			if ( in_array( 'creationdate', $prop ) ) {
				$wiki['creationdate'] = wfTimestamp( TS_ISO_8601, $row->wiki_creation );
			}

			$wikiState = match ( true ) {
				(bool)$row->wiki_private => 'private',
				default	=> 'public',
			};

			$wiki[$wikiState] = true;

			switch ( true ) {
				case $row->wiki_deleted:
					$wiki['deleted'] = true;
					if ( in_array( 'deletiondate', $prop ) ) {
						$wiki['deletiondate'] = wfTimestamp( TS_ISO_8601, $row->wiki_deleted_timestamp );
					}
					break;

				case $row->wiki_closed:
					$wiki['closed'] = true;
					if ( in_array( 'closuredate', $prop ) ) {
						$wiki['closuredate'] = wfTimestamp( TS_ISO_8601, $row->wiki_closed_timestamp );
					}
					break;

				case $row->wiki_inactive:
					$wiki['inactive'] = true;
					if ( in_array( 'inactivedate', $prop ) ) {
						$wiki['inactivedate'] = wfTimestamp( TS_ISO_8601, $row->wiki_inactive_timestamp );
					}
					break;

				case $row->wiki_inactive_exempt:
					$wiki['inactive'] = 'exempt';
					if ( in_array( 'exemptreason', $prop ) ) {
						$reason = $row->wiki_inactive_exempt_reason;
						if ( $this->extensionRegistry->isLoaded( 'ManageWiki' ) ) {
							$options = $this->getConfig()->get( 'ManageWikiInactiveExemptReasonOptions' );
							$reason = array_flip( $options )[$reason] ?? $reason;
						}
						$wiki['exemptreason'] = $reason;
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
	public function getCacheMode( $params ): string {
		return 'public';
	}

	protected function getDB(): IReadableDatabase {
		return $this->databaseUtils->getGlobalReplicaDB();
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
			'prop' => [
				ParamValidator::PARAM_DEFAULT => 'category|languagecode|sitename|url',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'category',
					'closuredate',
					'creationdate',
					'description',
					'deletiondate',
					'exemptreason',
					'inactivedate',
					'languagecode',
					'sitename',
					'url',
				],
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
