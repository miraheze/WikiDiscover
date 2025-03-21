<?php

namespace Miraheze\WikiDiscover;

use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;

class Hooks {

	/**
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'numberofwikisincategory', [ __CLASS__, 'numberOfWikisInCategory' ], Parser::SFH_NO_HASH );
		$parser->setFunctionHook( 'numberofwikisinlanguage', [ __CLASS__, 'numberOfWikisInLanguage' ], Parser::SFH_NO_HASH );
		$parser->setFunctionHook( 'numberofwikisbysetting', [ __CLASS__, 'numberOfWikisBySetting' ], Parser::SFH_NO_HASH );
		$parser->setFunctionHook( 'wikicreationdate', [ __CLASS__, 'wikiCreationDate' ], Parser::SFH_NO_HASH );
	}

	/**
	 * @param Parser $parser
	 * @param string $category Default to 'uncategorised'
	 * @return int
	 */
	public static function numberOfWikisInCategory( Parser $parser, string $category = 'uncategorised' ) {
		$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
		$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

		return $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_category' => strtolower( $category ) ], __METHOD__ );
	}

	/**
	 * @param Parser $parser
	 * @param string $language Default to 'en'
	 * @return int
	 */
	public static function numberOfWikisInLanguage( Parser $parser, string $language = 'en' ) {
		$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
		$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

		return $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_language' => strtolower( $language ) ], __METHOD__ );
	}

	/**
	 * @param Parser $parser
	 * @param mixed $setting Default to null
	 * @param mixed $value Default to null
	 * @return string|int
	 */
	public static function numberOfWikisBySetting( Parser $parser, $setting = null, $value = null ) {
		if ( !$setting && !$value ) {
			return 'Error: no input specified.';
		}

		$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
		$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

		$config = MediaWikiServices::getInstance()->getMainConfig();
		$extList = array_keys( $config->get( 'ManageWikiExtensions' ) );

		if ( !$value && !in_array( $setting, $extList ) ) {
			return 0;
		}

		if ( in_array( $setting, $extList ) ) {
			$selectExtensions = implode( ',', $dbr->selectFieldValues( 'mw_settings', 's_extensions', '', __METHOD__ ) );

			return substr_count( $selectExtensions, '"' . $setting . '"' );
		}

		$selectSettings = $dbr->selectFieldValues( 'mw_settings', 's_settings', '', __METHOD__ );
		$settingUsageCount = 0;

		foreach ( $selectSettings as $key ) {
			if ( !is_bool( array_search( $value, (array)( json_decode( $key, true )[$setting] ?? [] ) ) ) ) {
				$settingUsageCount++;
			}
		}

		return $settingUsageCount;
	}

	/**
	 * @param Parser $parser
	 * @param ?string $database
	 * @return string
	 */
	public static function wikiCreationDate( Parser $parser, ?string $database = null ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$lang = RequestContext::getMain()->getLanguage();

		$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
		$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

		$wikiDatabase = $database ?? $config->get( 'DBname' );

		$creationDate = $dbr->selectField( 'cw_wikis', 'wiki_creation', [ 'wiki_dbname' => $wikiDatabase ], __METHOD__ );

		return $lang->date( wfTimestamp( TS_MW, strtotime( $creationDate ) ) );
	}

	/**
	 * @param Parser $parser
	 * @param array &$cache
	 * @param string $magicWordId
	 * @param string &$ret
	 */
	public static function onParserGetVariableValueSwitch(
		Parser $parser,
		&$cache,
		$magicWordId,
		&$ret ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		switch ( $magicWordId ) {
			case 'numberofwikis':
				$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
				$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0 ], __METHOD__ );
				break;
			case 'numberoftotalwikis':
				$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
				$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', '', __METHOD__ );
				break;
			case 'numberofprivatewikis':
				if ( !$config->get( 'CreateWikiUsePrivateWikis' ) ) {
					break;
				}

				$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
				$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_private' => 1 ], __METHOD__ );
				break;
			case 'numberofpublicwikis':
				if ( !$config->get( 'CreateWikiUsePrivateWikis' ) ) {
					break;
				}

				$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
				$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_private' => 0 ], __METHOD__ );
				break;
			case 'numberofactivewikis':
				if ( !$config->get( 'CreateWikiUseInactiveWikis' ) ) {
					break;
				}

				$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
				$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_closed' => 0, 'wiki_deleted' => 0, 'wiki_inactive' => 0 ], __METHOD__ );
				break;
			case 'numberofinactivewikis':
				if ( !$config->get( 'CreateWikiUseInactiveWikis' ) ) {
					break;
				}

				$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
				$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_inactive' => 1 ], __METHOD__ );
				break;
			case 'numberofclosedwikis':
				if ( !$config->get( 'CreateWikiUseClosedWikis' ) ) {
					break;
				}

				$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
				$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_closed' => 1 ], __METHOD__ );
				break;
			case 'numberoflockedwikis':
				$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
				$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_locked' => 1 ], __METHOD__ );
				break;
			case 'numberofdeletedwikis':
				$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
				$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 1 ], __METHOD__ );
				break;
			case 'numberofinactivityexemptwikis':
				if ( !$config->get( 'CreateWikiUseInactiveWikis' ) ) {
					break;
				}

				$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
				$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_inactive_exempt' => 1 ], __METHOD__ );
				break;
			case 'numberofcustomdomains':
				$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
				$dbr = $connectionProvider->getReplicaDatabase( 'virtual-createwiki' );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', 'wiki_url', [ 'wiki_deleted' => 0 ], __METHOD__ );
				break;
			case 'wikicreationdate':
				$ret = $cache[$magicWordId] = self::wikiCreationDate( $parser );
				break;
		}
	}

	/**
	 * @param array &$variableIDs
	 */
	public static function onGetMagicVariableIDs( &$variableIDs ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$variableIDs[] = 'numberofwikis';
		$variableIDs[] = 'numberoftotalwikis';
		$variableIDs[] = 'numberoflockedwikis';
		$variableIDs[] = 'numberofdeletedwikis';
		$variableIDs[] = 'numberofcustomdomains';
		$variableIDs[] = 'wikicreationdate';

		if ( $config->get( 'CreateWikiUseClosedWikis' ) ) {
			$variableIDs[] = 'numberofclosedwikis';
		}

		if ( $config->get( 'CreateWikiUseInactiveWikis' ) ) {
			$variableIDs[] = 'numberofactivewikis';
			$variableIDs[] = 'numberofinactivewikis';
			$variableIDs[] = 'numberofinactivityexemptwikis';
		}

		if ( $config->get( 'CreateWikiUsePrivateWikis' ) ) {
			$variableIDs[] = 'numberofprivatewikis';
			$variableIDs[] = 'numberofpublicwikis';
		}
	}
}
