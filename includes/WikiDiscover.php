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
