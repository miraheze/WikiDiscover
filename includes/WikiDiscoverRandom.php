<?php

use MediaWiki\MediaWikiServices;

class WikiDiscoverRandom {
	/**
	 * @param int|string $state
	 * @param int|string $category
	 * @param int|string $language
	 * @return stdClass|bool
	 */
	public static function randomWiki( $state = 0, $category = 0, $language = 0 ) {
		$conditions = [];

		if ( $category ) {
			$conditions['wiki_category'] = $category;
		}

		if ( $language ) {
			$conditions['wiki_language'] = $language;
		}

		if ( $state === "inactive" ) {
			$conditions['wiki_inactive'] = 1;
		} elseif ( $state === "closed" ) {
			$conditions['wiki_closed'] = 1;
		} elseif ( $state === "open" ) {
			$conditions['wiki_closed'] = 0;
		}

		$conditions['wiki_private'] = 0;

		return self::randFromConds( $conditions );
	}

	/**
	 * @param string|array $conds The condition array.
	 * @return stdClass|bool
	 */
	protected static function randFromConds( $conds ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

		$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
			->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

		$possiblewikis = $dbr->selectFieldValues( 'cw_wikis', 'wiki_dbname', $conds, __METHOD__ );

		$randwiki = $possiblewikis[array_rand( $possiblewikis )];

		return $dbr->selectRow( 'cw_wikis', [ 'wiki_dbname', 'wiki_sitename', 'wiki_language', 'wiki_private', 'wiki_closed', 'wiki_inactive', 'wiki_category' ], [ 'wiki_dbname' => $randwiki ], __METHOD__ );
	}
}
