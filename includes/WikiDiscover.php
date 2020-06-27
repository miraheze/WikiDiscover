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
		global $wgCreateWikiDatabase;

		$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );
 		$res = $dbw->select(
 			'cw_wikis',
 			[
				'wiki_closed',
 				'wiki_dbname',
				'wiki_inactive',
 				'wiki_language',
				'wiki_private',
 			],
 			[],
 			__METHOD__
 		);

		$wikis_closed = [];
		$wikis_inactive = [];
		$wikis_lang = [];
		$wikis_private = [];

		if ( $res ) {
			foreach ( $res as $row ) {
				if ( $row->wiki_closed === "1" ) {
					$wikis_closed[$row->wiki_dbname];
					$wikis_closed[] = $row->wiki_dbname;
				}

				if ( $row->wiki_inactive === "1" ) {
					$wikis_inactive[] = $row->wiki_dbname;
				}

				$wikis_lang[$row->wiki_dbname] = $row->wiki_language;
				
				if ( $row->wiki_private === "1" ) {
					$wikis_private[] = $row->wiki_dbname;
				}
			}
		}

		$this->closed = $wikis_closed;
		$this->inactive = $wikis_inactive;
		$this->langCodes = $wikis_lang;
		$this->private = $wikis_private;
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
	 * @param Parser &$parser
	 * @param array &$cache
	 * @param string &$magicWordId
	 * @param string &$ret
	 * @param PPFrame|null $frame
	 * @return bool true
	 */
	public static function onParserGetVariableValueSwitch(
		Parser &$parser,
		&$cache,
		&$magicWordId,
		&$ret,
		$frame = null ) {
		if ( $magicWordId == 'numberofwikis' ) {
			global $wgLocalDatabases;
			$ret = count( $wgLocalDatabases );
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
