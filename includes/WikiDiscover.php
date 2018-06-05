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


	public function getWikiPrefixes() {
		global $wgLocalDatabases;

		$wikiprefixes = [];

		foreach ( $wgLocalDatabases as $db ) {
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
}
