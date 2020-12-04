<?php

use MediaWiki\MediaWikiServices;

class WikiDiscover {
	private $closed = [];
	private $inactive = [];
	private $private = [];
	private $langCodes = [];

	function __construct() {
		$this->config = MediaWikiServices::getInstance()->getMainConfig();

		$dbw = wfGetDB( DB_MASTER, [], $this->config->get( 'CreateWikiDatabase' ) );
 		$res = $dbw->select(
 			'cw_wikis',
 			[
 			    'wiki_dbname',
 			    'wiki_language',
			    'wiki_private',
			    'wiki_closed',
			    'wiki_inactive'
 			],
 			[],
 			__METHOD__
 		);
		
		if ( $res ) {
			foreach ( $res as $row ) {
				$this->langCodes[$row->wiki_dbname] = $row->wiki_language;

				if ( $row->wiki_private ) {
					$this->private[] = $row->wiki_dbname;
				}

				if ( $row->wiki_closed ) {
					$this->closed[] = $row->wiki_dbname;
				}

				if ( $row->wiki_inactive ) {
					$this->inactive[] = $row->wiki_dbname;
				}
			}
		}
	}

	public function getCount() {
		return count( $this->config->get( 'LocalDatabases' ) );
	}

	public function getWikis( $dbname ) {
		$wikis = [];

		$wikiList = $dbname ? explode( ',', $dbname ) : $this->config->get( 'LocalDatabases' );

		foreach ( $wikiList as $db ) {
			if ( preg_match( "/(.*)wiki\$/", $db, $a ) ) {
				$wiki = $a[1];
				$wikis[] = [ $wiki, 'wiki' ];
			}
		}

		return $wikis;
	}


	public function getWikiPrefixes( $dbname ) {
		$wikiprefixes = [];

		$wikiList = $dbname ? explode( ',', $dbname ) : $this->config->get( 'LocalDatabases' );

		foreach ( $wikiList as $db ) {
			if ( preg_match( "/(.*)wiki\$/", $db, $a ) ) {
				$wikiprefixes[] = $a[1];
			}
		}

		return $wikiprefixes;
	}

	public function getUrl( $database ) {
		return $this->config->get( 'Conf' )->get( 'wgServer', $database );
	}

	public function getSitename( $database ) {
		return $this->config->get( 'Conf' )->get( 'wgSitename', $database );
	}

	public function getLanguageCode( $database ) {
		return $this->langCodes[$database];
	}

	public function getLanguage( $database ) {
		$languagecode = $this->getLanguageCode( $database );

		return MediaWikiServices::getInstance()->getLanguageNameUtils()->getLanguageName( $languagecode );
	}

	public function isClosed( $database ) {
		return in_array( $database, $this->closed );
	}

	public function isInactive( $database ) {
		return in_array( $database, $this->inactive );
	}

	public function isPrivate( $database ) {
		return in_array( $database, $this->private );
	}
	
	/**
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'numberofwikisincategory', [ __CLASS__, 'numberOfWikisInCategory' ], Parser::SFH_NO_HASH );
		$parser->setFunctionHook( 'numberofwikisinlanguage', [ __CLASS__, 'numberOfWikisInLanguage' ], Parser::SFH_NO_HASH );
		$parser->setFunctionHook( 'numberofwikisbysetting', [ __CLASS__, 'numberOfWikisBySetting' ], Parser::SFH_NO_HASH );
	}
	
	/**
	 * @param Parser $parser
	 * @param string $category|null
	 * @return integer
	 */
	public static function numberOfWikisInCategory( Parser $parser, String $category = null ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$dbw = wfGetDB( DB_MASTER, [], $config->get( 'CreateWikiDatabase' ) );

		return $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_category' => strtolower( $category ) ] );;
	}

	/**
	 * @param Parser $parser
	 * @param string $language|null
	 * @return integer
	 */
	public static function numberOfWikisInLanguage( Parser $parser, String $language = null ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$dbw = wfGetDB( DB_MASTER, [], $config->get( 'CreateWikiDatabase' ) );

		return $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_language' => strtolower( $language ) ] );;
	}
	
	/**
	 * @param Parser $parser
	 * @param mixed $setting|null
	 * @param mixed $value|null
	 * @return string|integer
	 */
	public static function numberOfWikisBySetting( $parser, $setting = null, $value = null ) {
		if ( !$setting && !$value ) {
			return 'Error: no input specified.';
		}

		$config = MediaWikiServices::getInstance()->getMainConfig();
		$dbr = wfGetDB( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

		$extList = array_keys( $config->get( 'ManageWikiExtensions' ) );
        
		if ( !$value && !in_array( $setting, $extList ) ) {
			return 0;
		}
        
		if ( in_array( $setting, $extList ) ) {
			$selectExtensions = implode( ',', $dbr->selectFieldValues( 'mw_settings', 's_extensions' ) );

			return substr_count( $selectExtensions, '"' . $setting . '"' );
		}

		function is_not_empty( $val ){
			return !empty( $val ) || $val === 0;
		}

		$selectSettings = $dbr->selectFieldValues( 'mw_settings', 's_settings' );
		$settingUsageCount = [];
		
		foreach( $selectSettings as $key ) {
			$settingUsageCount[] = array_search( $value, (array)json_decode( $key, true )[$setting] );
		}

		return count( array_filter( $settingUsageCount, 'is_not_empty' ) );
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
				$ret = $cache[$magicWordId] = count( $config->get( 'LocalDatabases' ) );
				break;
			case 'numberofprivatewikis':
				$dbw = wfGetDB( DB_MASTER, [], $config->get( 'CreateWikiDatabase' ) );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_private' => 1 ] );
				break;
			case 'numberofpublicwikis':
				$dbw = wfGetDB( DB_MASTER, [], $config->get( 'CreateWikiDatabase' ) );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_private' => 0 ] );
				break;
			case 'numberofactivewikis':
				$dbw = wfGetDB( DB_MASTER, [], $config->get( 'CreateWikiDatabase' ) );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_closed' => 0, 'wiki_deleted' => 0, 'wiki_inactive' => 0 ] );
				break;
			case 'numberofinactivewikis':
				$dbw = wfGetDB( DB_MASTER, [], $config->get( 'CreateWikiDatabase' ) );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_inactive' => 1 ] );
				break;
			case 'numberofclosedwikis':
				$dbw = wfGetDB( DB_MASTER, [], $config->get( 'CreateWikiDatabase' ) );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_closed' => 1 ] );
				break;
			case 'numberoflockedwikis':
				$dbw = wfGetDB( DB_MASTER, [], $config->get( 'CreateWikiDatabase' ) );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_locked' => 1 ] );
				break;
			case 'numberofdeletedwikis':
				$dbw = wfGetDB( DB_MASTER, [], $config->get( 'CreateWikiDatabase' ) );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 1 ] );
				break;
			case 'numberofinactivityexemptwikis':
				$dbw = wfGetDB( DB_MASTER, [], $config->get( 'CreateWikiDatabase' ) );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_inactive_exempt' => 1 ] );
				break;
			case 'numberofcustomdomains':
				$dbw = wfGetDB( DB_MASTER, [], $config->get( 'CreateWikiDatabase' ) );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', 'wiki_url', [ 'wiki_deleted' => 0 ] );
				break;
		}
	}

	/**
	 * @param array &$variableIDs
	 */
	public static function onGetMagicVariableIDs( &$variableIDs ) {
		$variableIDs[] = 'numberofwikis';
		$variableIDs[] = 'numberofprivatewikis';
		$variableIDs[] = 'numberofpublicwikis';
		$variableIDs[] = 'numberofactivewikis';
		$variableIDs[] = 'numberofinactivewikis';
		$variableIDs[] = 'numberofclosedwikis';
		$variableIDs[] = 'numberoflockedwikis';
		$variableIDs[] = 'numberofdeletedwikis';
		$variableIDs[] = 'numberofinactivityexemptwikis';
		$variableIDs[] = 'numberofcustomdomains';
	}
}
