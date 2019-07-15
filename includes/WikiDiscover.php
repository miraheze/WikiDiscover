<?php

class WikiDiscover {
	protected $closed;

	protected $inactive;

	protected $private;

	protected $count;

	protected $wikis;

	protected $wikiprefixes;

	function __construct() {
		global $wgWikiDiscoverClosedList, $wgWikiDiscoverPrivateList, $wgWikiDiscoverInactiveList;

		$this->private = array_map( 'trim', file( $wgWikiDiscoverPrivateList ) );
		$this->closed = array_map( 'trim', file( $wgWikiDiscoverClosedList ) );
		$this->inactive = array_map( 'trim', file( $wgWikiDiscoverInactiveList ) );
	}

	public function getCount() {
		global $wgLocalDatabases;

		return count( $wgLocalDatabases );
	}

	public function getWikis() {
		global $wgLocalDatabases;

		$wikis = [];

		foreach ( $wgLocalDatabases as $db ) {
			if ( preg_match( "/(.*)wiki\$/", $db, $a ) ) {
				$wiki = $a[1];
				$wikis[] = [ $wiki, 'wiki' ];
			}
		}

		return $wikis;
	}


	public function getWikiPrefixes( $dbname ) {
		global $wgLocalDatabases;

		if ( $dbname ) {
			$dbname = $dbname;
		} else {
			$dbname = $wgLocalDatabases;
		}

		$wikiprefixes = [];

		foreach ( $dbname as $db ) {
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
		global $wgConf;

		return $wgConf->get( 'wgLanguageCode', $database );
	}

	public function getLanguage( $database ) {
		$languagecode = $this->getLanguageCode( $database );

		return Language::fetchLanguageName( $languagecode );
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
