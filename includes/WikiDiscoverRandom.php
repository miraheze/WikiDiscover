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
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$conditions = [];

		if ( $category ) {
			$conditions['wiki_category'] = $category;
		}

		if ( $language ) {
			$conditions['wiki_language'] = $language;
		}

		if ( $config->get( 'CreateWikiUseInactiveWikis' ) && $state === 'inactive' ) {
			$conditions['wiki_inactive'] = 1;
		} elseif ( $config->get( 'CreateWikiUseInactiveWikis' ) && $state === 'active' ) {
			$conditions['wiki_inactive'] = 0;
		}

		/* Never randomly offer closed or private wikis */
		if ( $config->get( 'CreateWikiUseClosedWikis' ) ) {
			$conditions['wiki_closed'] = 0;
		}
		if ( $config->get( 'CreateWikiUsePrivateWikis' ) ) {
			$conditions['wiki_private'] = 0;
		}

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

		/* MySQL is ever the outlier */
		$random_function = $config->get( 'DBtype' ) === 'mysql' ? 'RAND()' : 'random()';

		return $dbr->selectRow(
			'cw_wikis',
			[ 'wiki_dbname', 'wiki_url' ],
			__METHOD__,
			[ 'ORDER BY' => $random_function, 'LIMIT' => 1 ]
		);

	}
}
