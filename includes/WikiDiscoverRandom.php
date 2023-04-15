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
		} elseif ( $config->get( 'CreateWikiUseClosedWikis' ) && $state === 'closed' ) {
			$conditions['wiki_closed'] = 1;
		} elseif ( $config->get( 'CreateWikiUseClosedWikis' ) && $state === 'open' ) {
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

		$possiblewikis = $dbr->selectFieldValues( 'cw_wikis', 'wiki_dbname', $conds, __METHOD__ );

		$randwiki = $possiblewikis[array_rand( $possiblewikis )];

		$fields = [];
		if ( $config->get( 'CreateWikiUseClosedWikis' ) ) {
			$fields[] = 'wiki_closed';
		}

		if ( $config->get( 'CreateWikiUseInactiveWikis' ) ) {
			$fields[] = 'wiki_inactive';
		}

		if ( $config->get( 'CreateWikiUsePrivateWikis' ) ) {
			$fields[] = 'wiki_private';
		}

		return $dbr->selectRow( 'cw_wikis', array_merge( [ 'wiki_dbname', 'wiki_sitename', 'wiki_language', 'wiki_category' ], $fields ), [ 'wiki_dbname' => $randwiki ], __METHOD__ );
	}
}
