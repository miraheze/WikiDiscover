<?php

use MediaWiki\MediaWikiServices;

class WikiDiscover {
	protected $closed;

	protected $inactive;

	protected $private;

	protected $langCodes;

	protected $count;

	protected $wikis;

	protected $wikiprefixes;

	function __construct() {
		global $wgCreateWikiDatabase, $wgWikiDiscoverClosedList, $wgWikiDiscoverPrivateList,
			$wgWikiDiscoverInactiveList;

		$this->private = array_map( 'trim', file( $wgWikiDiscoverPrivateList ) );
		$this->closed = array_map( 'trim', file( $wgWikiDiscoverClosedList ) );
		$this->inactive = array_map( 'trim', file( $wgWikiDiscoverInactiveList ) );

		$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
 		$res = $dbw->select(
 			'cw_wikis',
 			[
 				'wiki_dbname',
 				'wiki_language',
 			],
 			[],
 			__METHOD__
 		);

		$wikis_lang = [];
		
		if ( $res ) {
			foreach ( $res as $row ) {
				$wikis_lang[$row->wiki_dbname] = $row->wiki_language;
			}
		}

		$this->langCodes = $wikis_lang;
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
		if ( $magicWordId == 'numberofwikis' ) {
			global $wgLocalDatabases;
			$ret = $cache[$magicWordId] = count( $wgLocalDatabases );
		}
		return true;
	}

	/**
	 * @param array &$customVariableIds
	 * @return bool true
	 */
	public static function onMagicWordwgVariableIDs( &$customVariableIds ) {
		$customVariableIds[] = 'numberofwikis';
		return true;
	}
}
