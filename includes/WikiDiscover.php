<?php

use MediaWiki\MediaWikiServices;

class WikiDiscover {
	private $closed = [];
	private $inactive = [];
	private $private = [];
	private $langCodes = [];

	function __construct() {
		global $wgCreateWikiDatabase;

		$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
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
		global $wgLocalDatabases;

		return count( $wgLocalDatabases );
	}

	public function getWikis( $dbname ) {
		global $wgLocalDatabases;

		$wikis = [];

		$wikiList = $dbname ? explode( ',', $dbname ) : $wgLocalDatabases;

		foreach ( $wikiList as $db ) {
			if ( preg_match( "/(.*)wiki\$/", $db, $a ) ) {
				$wiki = $a[1];
				$wikis[] = [ $wiki, 'wiki' ];
			}
		}

		return $wikis;
	}


	public function getWikiPrefixes( $dbname ) {
		global $wgLocalDatabases;

		$wikiprefixes = [];

		$wikiList = $dbname ? explode( ',', $dbname ) : $wgLocalDatabases;

		foreach ( $wikiList as $db ) {
			if ( preg_match( "/(.*)wiki\$/", $db, $a ) ) {
				$wikiprefixes[] = $a[1];
			}
		}

		return $wikiprefixes;
	}

	public function getUrl( $database ) {
		global $wgConf;

		return $wgConf->get( 'wgServer', $database );
	}

	public function getSitename( $database ) {
		global $wgConf;

		return $wgConf->get( 'wgSitename', $database );
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
	 * @param array &$cache
	 * @param string $magicWordId
	 * @param string &$ret
	 * @return bool true
	 */
	public static function onParserGetVariableValueSwitch(
		Parser $parser,
		&$cache,
		$magicWordId,
		&$ret ) {
		$parser->setFunctionHook( 'numberofwikisincategory', [ __CLASS__, 'numberOfWikisInCategory' ], Parser::SFH_NO_HASH );
		$parser->setFunctionHook( 'numberofwikisinlanguage', [ __CLASS__, 'numberOfWikisInLanguage' ], Parser::SFH_NO_HASH );

		switch ( $magicWordId ) {
			case 'numberofwikis':
				global $wgLocalDatabases;
				$ret = $cache[$magicWordId] = count( $wgLocalDatabases );
				break;
			case 'numberofprivatewikis':
				global $wgCreateWikiDatabase;
				$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_private' => 1 ] );
				break;
			case 'numberofpublicwikis':
				global $wgCreateWikiDatabase;
				$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_private' => 0 ] );
				break;
			case 'numberofactivewikis':
				global $wgCreateWikiDatabase;
				$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_closed' => 0, 'wiki_deleted' => 0, 'wiki_inactive' => 0 ] );
				break;
			case 'numberofinactivewikis':
				global $wgCreateWikiDatabase;
				$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_inactive' => 1 ] );
				break;
			case 'numberofclosedwikis':
				global $wgCreateWikiDatabase;
				$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_closed' => 1 ] );
				break;
			case 'numberoflockedwikis':
				global $wgCreateWikiDatabase;
				$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_locked' => 1 ] );
				break;
			case 'numberofdeletedwikis':
				global $wgCreateWikiDatabase;
				$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 1 ] );
				break;
			case 'numberofinactivityexemptwikis':
				global $wgCreateWikiDatabase;
				$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
				$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_inactive_exempt' => 1 ] );
				break;
		}
		
		return true;
	}

	public static function numberOfWikisInCategory( Parser $parser, String $category = '' ) {
		global $wgCreateWikiDatabase;
		$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
		$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_category' => strtolower( $category ) ] );

		return $ret;
	}

	public static function numberOfWikisInLanguage( Parser $parser, String $language = '' ) {
		global $wgCreateWikiDatabase;
		$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
		$ret = $cache[$magicWordId] = $dbw->selectRowCount( 'cw_wikis', '*', [ 'wiki_language' => strtolower( $language ) ] );

		return $ret;
	}

	/**
	 * @param array &$customVariableIds
	 * @return bool true
	 */
	public static function onMagicWordwgVariableIDs( &$customVariableIds ) {
		$customVariableIds[] = 'numberofwikis';
		$customVariableIds[] = 'numberofprivatewikis';
		$customVariableIds[] = 'numberofpublicwikis';
		$customVariableIds[] = 'numberofactivewikis';
		$customVariableIds[] = 'numberofinactivewikis';
		$customVariableIds[] = 'numberofclosedwikis';
		$customVariableIds[] = 'numberoflockedwikis';
		$customVariableIds[] = 'numberofdeletedwikis';
		$customVariableIds[] = 'numberofinactivityexemptwikis';
		$customVariableIds[] = 'numberofwikisincategory';
		$customVariableIds[] = 'numberofwikisinlanguage';

		return true;
	}
}
