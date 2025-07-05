<?php

namespace Miraheze\WikiDiscover\HookHandlers;

use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Hook\GetMagicVariableIDsHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\ParserGetVariableValueSwitchHook;
use MediaWiki\MainConfigNames;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use Miraheze\CreateWiki\Services\CreateWikiDatabaseUtils;

class Main implements
	GetMagicVariableIDsHook,
	ParserFirstCallInitHook,
	ParserGetVariableValueSwitchHook
{

	public function __construct(
		private readonly CreateWikiDatabaseUtils $databaseUtils,
		private readonly Config $config
	) {
	}

	/** @inheritDoc */
	public function onGetMagicVariableIDs( &$variableIDs ) {
		$variableIDs[] = 'numberofwikis';
		$variableIDs[] = 'numberoftotalwikis';
		$variableIDs[] = 'numberoflockedwikis';
		$variableIDs[] = 'numberofdeletedwikis';
		$variableIDs[] = 'numberofcustomdomains';
		$variableIDs[] = 'wikicreationdate';

		if ( $this->config->get( 'CreateWikiUseClosedWikis' ) ) {
			$variableIDs[] = 'numberofclosedwikis';
		}

		if ( $this->config->get( 'CreateWikiUseInactiveWikis' ) ) {
			$variableIDs[] = 'numberofactivewikis';
			$variableIDs[] = 'numberofinactivewikis';
			$variableIDs[] = 'numberofinactivityexemptwikis';
		}

		if ( $this->config->get( 'CreateWikiUsePrivateWikis' ) ) {
			$variableIDs[] = 'numberofprivatewikis';
			$variableIDs[] = 'numberofpublicwikis';
		}
	}

	/** @inheritDoc */
	public function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook(
			'numberofwikisincategory',
			[ $this, 'getNumberOfWikisInCategory' ],
			Parser::SFH_NO_HASH
		);

		$parser->setFunctionHook(
			'numberofwikisinlanguage',
			[ $this, 'getNumberOfWikisInLanguage' ],
			Parser::SFH_NO_HASH
		);

		$parser->setFunctionHook(
			'numberofwikisbysetting',
			[ $this, 'getNumberOfWikisBySetting' ],
			Parser::SFH_NO_HASH
		);

		$parser->setFunctionHook(
			'wikicreationdate',
			[ $this, 'getWikiCreationDate' ],
			Parser::SFH_NO_HASH
		);
	}

	/**
	 * @inheritDoc
	 * @param PPFrame $frame @phan-unused-param
	 */
	public function onParserGetVariableValueSwitch(
		$parser,
		&$variableCache,
		$magicWordId,
		&$ret,
		$frame
	) {
		switch ( $magicWordId ) {
			case 'numberofwikis':
				$dbr = $this->databaseUtils->getGlobalReplicaDB();
				$ret = $dbr->newSelectQueryBuilder()
					->select( '*' )
					->from( 'cw_wikis' )
					->where( [ 'wiki_deleted' => 0 ] )
					->caller( __METHOD__ )
					->fetchRowCount();
				break;
			case 'numberoftotalwikis':
				$dbr = $this->databaseUtils->getGlobalReplicaDB();
				$ret = $dbr->newSelectQueryBuilder()
					->select( '*' )
					->from( 'cw_wikis' )
					->caller( __METHOD__ )
					->fetchRowCount();
				break;
			case 'numberofprivatewikis':
				if ( !$this->config->get( 'CreateWikiUsePrivateWikis' ) ) {
					break;
				}

				$dbr = $this->databaseUtils->getGlobalReplicaDB();
				$ret = $dbr->newSelectQueryBuilder()
					->select( '*' )
					->from( 'cw_wikis' )
					->where( [
						'wiki_deleted' => 0,
						'wiki_private' => 1,
					] )
					->caller( __METHOD__ )
					->fetchRowCount();
				break;
			case 'numberofpublicwikis':
				if ( !$this->config->get( 'CreateWikiUsePrivateWikis' ) ) {
					break;
				}

				$dbr = $this->databaseUtils->getGlobalReplicaDB();
				$ret = $dbr->newSelectQueryBuilder()
					->select( '*' )
					->from( 'cw_wikis' )
					->where( [
						'wiki_deleted' => 0,
						'wiki_private' => 0,
					] )
					->caller( __METHOD__ )
					->fetchRowCount();
				break;
			case 'numberofactivewikis':
				if ( !$this->config->get( 'CreateWikiUseInactiveWikis' ) ) {
					break;
				}

				$dbr = $this->databaseUtils->getGlobalReplicaDB();
				$ret = $dbr->newSelectQueryBuilder()
					->select( '*' )
					->from( 'cw_wikis' )
					->where( [
						'wiki_closed' => 0,
						'wiki_deleted' => 0,
						'wiki_inactive' => 0,
					] )
					->caller( __METHOD__ )
					->fetchRowCount();
				break;
			case 'numberofinactivewikis':
				if ( !$this->config->get( 'CreateWikiUseInactiveWikis' ) ) {
					break;
				}

				$dbr = $this->databaseUtils->getGlobalReplicaDB();
				$ret = $dbr->newSelectQueryBuilder()
					->select( '*' )
					->from( 'cw_wikis' )
					->where( [
						'wiki_deleted' => 0,
						'wiki_inactive' => 1,
					] )
					->caller( __METHOD__ )
					->fetchRowCount();
				break;
			case 'numberofclosedwikis':
				if ( !$this->config->get( 'CreateWikiUseClosedWikis' ) ) {
					break;
				}

				$dbr = $this->databaseUtils->getGlobalReplicaDB();
				$ret = $dbr->newSelectQueryBuilder()
					->select( '*' )
					->from( 'cw_wikis' )
					->where( [
						'wiki_closed' => 1,
						'wiki_deleted' => 0,
					] )
					->caller( __METHOD__ )
					->fetchRowCount();
				break;
			case 'numberoflockedwikis':
				$dbr = $this->databaseUtils->getGlobalReplicaDB();
				$ret = $dbr->newSelectQueryBuilder()
					->select( '*' )
					->from( 'cw_wikis' )
					->where( [
						'wiki_deleted' => 0,
						'wiki_locked' => 1,
					] )
					->caller( __METHOD__ )
					->fetchRowCount();
				break;
			case 'numberofdeletedwikis':
				$dbr = $this->databaseUtils->getGlobalReplicaDB();
				$ret = $dbr->newSelectQueryBuilder()
					->select( '*' )
					->from( 'cw_wikis' )
					->where( [ 'wiki_deleted' => 1 ] )
					->caller( __METHOD__ )
					->fetchRowCount();
				break;
			case 'numberofinactivityexemptwikis':
				if ( !$this->config->get( 'CreateWikiUseInactiveWikis' ) ) {
					break;
				}

				$dbr = $this->databaseUtils->getGlobalReplicaDB();
				$ret = $dbr->newSelectQueryBuilder()
					->select( '*' )
					->from( 'cw_wikis' )
					->where( [
						'wiki_deleted' => 0,
						'wiki_inactive_exempt' => 1,
					] )
					->caller( __METHOD__ )
					->fetchRowCount();
				break;
			case 'numberofcustomdomains':
				$dbr = $this->databaseUtils->getGlobalReplicaDB();
				$ret = $dbr->newSelectQueryBuilder()
					->select( 'wiki_url' )
					->from( 'cw_wikis' )
					->where( [ 'wiki_deleted' => 0 ] )
					->caller( __METHOD__ )
					->fetchRowCount();
				break;
			case 'wikicreationdate':
				$ret = $this->getWikiCreationDate( $parser );
				break;
		}
	}

	/** @param Parser $parser @phan-unused-param */
	public function getNumberOfWikisInCategory(
		Parser $parser,
		string $category = 'uncategorised'
	): int {
		$dbr = $this->databaseUtils->getGlobalReplicaDB();
		return $dbr->newSelectQueryBuilder()
			->select( '*' )
			->from( 'cw_wikis' )
			->where( [
				'wiki_category' => strtolower( $category ),
				'wiki_deleted' => 0,
			] )
			->caller( __METHOD__ )
			->fetchRowCount();
	}

	/** @param Parser $parser @phan-unused-param */
	public function getNumberOfWikisInLanguage(
		Parser $parser,
		string $language = 'en'
	): int {
		$dbr = $this->databaseUtils->getGlobalReplicaDB();
		return $dbr->newSelectQueryBuilder()
			->select( '*' )
			->from( 'cw_wikis' )
			->where( [
				'wiki_deleted' => 0,
				'wiki_language' => strtolower( $language ),
			] )
			->caller( __METHOD__ )
			->fetchRowCount();
	}

	/** @param Parser $parser @phan-unused-param */
	public function getNumberOfWikisBySetting(
		Parser $parser,
		mixed $setting = null,
		mixed $value = null
	): string|int {
		if ( !$setting && !$value ) {
			return 'Error: no input specified.';
		}

		$dbr = $this->databaseUtils->getGlobalReplicaDB();
		$extList = array_keys( $this->config->get( 'ManageWikiExtensions' ) );

		if ( !$value && !in_array( $setting, $extList ) ) {
			return 0;
		}

		if ( in_array( $setting, $extList ) ) {
			$fieldValues = $dbr->newSelectQueryBuilder()
				->select( 's_extensions' )
				->from( 'mw_settings' )
				->caller( __METHOD__ )
				->fetchFieldValues();

			$selectExtensions = implode( ',', $fieldValues );
			return substr_count( $selectExtensions, '"' . $setting . '"' );
		}

		$settingUsageCount = 0;
		$selectSettings = $dbr->newSelectQueryBuilder()
			->select( 's_settings' )
			->from( 'mw_settings' )
			->caller( __METHOD__ )
			->fetchFieldValues();

		foreach ( $selectSettings as $key ) {
			if ( !is_bool( array_search( $value, (array)( json_decode( $key, true )[$setting] ?? [] ) ) ) ) {
				$settingUsageCount++;
			}
		}

		return $settingUsageCount;
	}

	/** @param Parser $parser @phan-unused-param */
	public function getWikiCreationDate(
		Parser $parser,
		?string $database = null
	): string {
		$lang = RequestContext::getMain()->getLanguage();
		$dbr = $this->databaseUtils->getGlobalReplicaDB();

		$wikiDatabase = $database ?? $this->config->get( MainConfigNames::DBname );
		$creationDate = $dbr->newSelectQueryBuilder()
			->select( 'wiki_creation' )
			->from( 'cw_wikis' )
			->where( [ 'wiki_dbname' => $wikiDatabase ] )
			->caller( __METHOD__ )
			->fetchField();

		return $lang->date( wfTimestamp( TS_MW, strtotime( $creationDate ) ) );
	}
}
