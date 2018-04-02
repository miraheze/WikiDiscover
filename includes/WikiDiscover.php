<?php

class WikiDiscover {
	protected $closed;

	protected $inactive;

	protected $count;

	protected $wikis;

	public function getCount() {
		global $wgLocalDatabases;

		return count( $wgLocalDatabases );
	}


	public function getWikis() {
		global $wgLocalDatabases;

		foreach ( $wgLocalDatabases as $db ) {
			if ( preg_match( "/(.*)wiki\$/", $db, $a ) ) {
				$wiki = $a[1];
				$wikis = [ $wiki, 'wiki' ];
			}
		}

		return $wikis;
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
		global $wgWikiDiscoverClosedList;

		$closed = $this->extractDBList( $wgWikiDiscoverClosedList );

		return in_array( $database, $closed );
	}

	public function isInactive( $database ) {
		global $wgWikiDiscoverInactiveList;

		$inactive = $this->extractDBList( $wgWikiDiscoverInactiveList );

		return in_array( $database, $inactive );
	}

	public function isPrivate( $database ) {
		global $wgWikiDiscoverPrivateList;

		$private = $this->extractDBList( $wgWikiDiscoverPrivateList );

		return in_array( $database, $private );
	}

	private function extractDBList( $dblist ) {
		return array_map( 'trim', file( $dblist ) );
	}
}
