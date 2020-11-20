<?php

use MediaWiki\MediaWikiServices;

class WikiDiscoverWikisPager extends TablePager {
	function __construct( $language, $category ) {
		$this->mDb = self::getCreateWikiDatabase();
		$this->language = $language;
		$this->category = $category;
		$this->wikiDiscover = new WikiDiscover();
		parent::__construct( $this->getContext() );
	}

	static function getCreateWikiDatabase() {
		global $wgCreateWikiDatabase;

		$factory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$lb = $factory->getMainLB( $wgCreateWikiDatabase );

		return $lb->getConnectionRef( DB_REPLICA, 'cw_wikis', $wgCreateWikiDatabase );
	}

	function getFieldNames() {
		static $headers = null;

		$headers = [
			'wiki_dbname' => 'wikidiscover-table-wiki',
			'wiki_language' => 'wikidiscover-table-language',
			'wiki_closed' => 'wikidiscover-table-state',
			'wiki_private' => 'wikidiscover-table-visibility',
			'wiki_category' => 'wikidiscover-table-category',
			'wiki_closed_timestamp' => 'wikidiscover-table-deletable',
			'wiki_creation' => 'wikidiscover-table-established',
		];

		foreach ( $headers as &$msg ) {
			$msg = $this->msg( $msg )->text();
		}

		return $headers;
	}

	function formatValue( $name, $value ) {
		global $wgCreateWikiCategories;

		$row = $this->mCurrentRow;

		$wikidiscover = $this->wikiDiscover;

		$wiki = $row->wiki_dbname;

		switch ( $name ) {
			case 'wiki_dbname':
				$url = $wikidiscover->getUrl( $wiki );
				$name = $wikidiscover->getSitename( $wiki );
				$formatted = "<a href=\"{$url}\">{$name}</a>";
				break;
			case 'wiki_language':
				$formatted = $wikidiscover->getLanguage( $wiki );
				break;
			case 'wiki_closed':
				if ($wikidiscover->isClosed( $wiki ) === true ) {
					$formatted = 'Closed';
				} elseif ( $wikidiscover->isInactive( $wiki ) === true ) {
					$formatted = 'Inactive';
				} else {
					$formatted = 'Open';
				}
				break;
			case 'wiki_private':
				if ( $wikidiscover->isPrivate( $wiki ) === true ) {
					$formatted = 'Private';
				} else {
					$formatted = 'Public';
				}
				break;
			case 'wiki_category':
				$wikicategories = array_flip( $wgCreateWikiCategories );
				$formatted = $wikicategories[$row->wiki_category];
				break;
			case 'wiki_closed_timestamp':
				if ( isset( $row->wiki_closed_timestamp ) && $row->wiki_closed_timestamp < date( "YmdHis", strtotime( "-120 days" ) ) ) {
					$formatted = 'Yes';
				} else {
					$formatted = 'No';
				}
				break;
			case 'wiki_creation':
				$formatted = date( 'F j, Y', strtotime( $row->wiki_creation ) );
				break;
			default:
				$formatted = "Unable to format $name";
				break;
		}

		return $formatted;
	}

	function getQueryInfo() {
		$info = [
			'tables' => [ 'cw_wikis' ],
			'fields' => [ 'wiki_dbname', 'wiki_language', 'wiki_private', 'wiki_closed', 'wiki_closed_timestamp', 'wiki_category', 'wiki_creation' ],
			'conds' => [],
			'joins_conds' => [],
		];

		if ( $this->language ) {
			$info['conds']['wiki_language'] = $this->language;
		}

		if ( $this->category ) {
			$info['conds']['wiki_category'] = $this->category;
		}

		return $info;
	}

	function getDefaultSort() {
		return 'wiki_dbname';
	}

	function isFieldSortable( $name ) {
		return true;
	}
}
