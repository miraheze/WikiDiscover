<?php
class WikiDiscoverRandom {
	public static function randomWiki( $state = false, $category = false, $language = false ) {
		$conditions = array();

		if ( $category ) {
			$conditions['wiki_category'] = $category;
		}

		if ( $language ) {
			$conditions['wiki_language'] = $language;
		}

		if ( $state == "inactive" ) {
			$conditions['wiki_inactive'] = 1;
		} elseif ( $state == "closed" ) {
			$conditions['wiki_closed'] = 1;
		} elseif ( $state == "open" ) {
			$conditions['wiki_closed'] = 0;
		}

		return self::randFromConds( $conditions );
	}

	protected static function randFromConds( $conds ) {
		$dbr = wfGetDB( DB_REPLICA, [], 'metawiki' );

		$nopw = $dbr->selectRowCount( 'cw_wikis', '*', $conds, __METHOD__ );

		$randnum = rand( 0, $nopw );

		$possiblewikis = $dbr->selectFieldValues( 'cw_wikis', 'wiki_dbname', $conds, __METHOD__ );

		$randwiki = $possiblewikis[$randnum];

		return $dbr->selectRow( 'cw_wikis', array( 'wiki_dbname', 'wiki_sitename', 'wiki_language', 'wiki_private', 'wiki_closed', 'wiki_inactive', 'wiki_category' ), array( 'wiki_dbname' => $randwiki ), __METHOD__ );
	}
}
