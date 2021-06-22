<?php

use MediaWiki\MediaWikiServices;

class WikiDiscoverWikisPager extends TablePager {
	function __construct( $language, $category, $state, $visibility ) {
		$this->mDb = self::getCreateWikiDatabase();

		$this->language = $language;
		$this->category = $category;

		$this->state = $state;
		$this->visibility = $visibility;

		$this->wikiDiscover = new WikiDiscover();

		parent::__construct( $this->getContext() );
	}

	static function getCreateWikiDatabase() {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$factory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$lb = $factory->getMainLB( $config->get( 'CreateWikiDatabase' ) );

		return $lb->getConnectionRef( DB_REPLICA, 'cw_wikis', $config->get( 'CreateWikiDatabase' ) );
	}

	function getFieldNames() {
		static $headers = null;

		$headers = [
			'wiki_dbname' => 'wikidiscover-table-wiki',
			'wiki_language' => 'wikidiscover-table-language',
			'wiki_closed' => 'wikidiscover-table-state',
			'wiki_private' => 'wikidiscover-table-visibility',
			'wiki_category' => 'wikidiscover-table-category',
			'wiki_creation' => 'wikidiscover-table-established',
		];

		foreach ( $headers as &$msg ) {
			$msg = $this->msg( $msg )->text();
		}

		return $headers;
	}

	function formatValue( $name, $value ) {
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
				if ( $wikidiscover->isDeleted( $wiki ) ) {
					$formatted = 'Deleted';
				} elseif ( $wikidiscover->isClosed( $wiki ) ) {
					$formatted = 'Closed';
				} elseif ( $wikidiscover->isInactive( $wiki ) ) {
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
				$wikicategories = array_flip( $this->getConfig()->get( 'CreateWikiCategories' ) );
				$formatted = $wikicategories[$row->wiki_category] ?? 'Uncategorised';
				break;
			case 'wiki_creation':
				$lang = RequestContext::getMain()->getLanguage();

				$formatted = $lang->date( wfTimestamp( TS_MW, strtotime( $row->wiki_creation ) ) );
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
			'fields' => [ 'wiki_dbname', 'wiki_language', 'wiki_private', 'wiki_closed', 'wiki_inactive', 'wiki_deleted', 'wiki_category', 'wiki_creation' ],
			'conds' => [],
			'joins_conds' => [],
		];

		if ( $this->language && $this->language !== 'any' ) {
			$info['conds']['wiki_language'] = $this->language;
		}

		if ( $this->category && $this->category !== 'any' ) {
			$info['conds']['wiki_category'] = $this->category;
		}

		if ( $this->state && $this->state !== 'any' ) {
			if ( $this->state === 'deleted' ) {
				$info['conds']['wiki_deleted'] = 1;
			} elseif ( $this->state === 'closed' ) {
				$info['conds']['wiki_closed'] = 1;
				$info['conds']['wiki_deleted'] = 0;
			} elseif ( $this->state === 'inactive' ) {
				$info['conds']['wiki_deleted'] = 0;
				$info['conds']['wiki_inactive'] = 1;
			} elseif ( $this->state === 'active' ) {
				$info['conds']['wiki_closed'] = 0;
				$info['conds']['wiki_deleted'] = 0;
				$info['conds']['wiki_inactive'] = 0;
			}
		}

		if ( $this->visibility && $this->visibility !== 'any' ) {
			if ( $this->visibility === 'public' ) {
				$info['conds']['wiki_private'] = 0;
			} elseif ( $this->visibility === 'private' ) {
				$info['conds']['wiki_private'] = 1;
			}
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
