<?php

use MediaWiki\MediaWikiServices;
use Miraheze\ManageWiki\Helpers\ManageWikiSettings;

class WikiDiscoverWikisPager extends TablePager {
	/** @var string */
	private $language;

	/** @var string */
	private $category;

	/** @var string */
	private $state;

	/** @var string */
	private $visibility;

	/** @var WikiDiscover */
	private $wikiDiscover;

	/**
	 * @param SpecialPage $page
	 * @param string $language
	 * @param string $category
	 * @param string $state
	 * @param string $visibility
	 */
	public function __construct( $page, $language, $category, $state, $visibility ) {
		$this->mDb = self::getCreateWikiDatabase();

		$this->language = $language;
		$this->category = $category;

		$this->state = $state;
		$this->visibility = $visibility;

		$this->wikiDiscover = new WikiDiscover();

		parent::__construct( $page->getContext(), $page->getLinkRenderer() );
	}

	/**
	 * @return DBConnRef
	 */
	public static function getCreateWikiDatabase() {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$factory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$lb = $factory->getMainLB( $config->get( 'CreateWikiDatabase' ) );

		return $lb->getMaintenanceConnectionRef( DB_REPLICA, 'cw_wikis', $config->get( 'CreateWikiDatabase' ) );
	}

	/** @inheritDoc */
	protected function getFieldNames() {
		static $headers = null;

		$headers = [
			'wiki_dbname' => 'wikidiscover-table-wiki',
			'wiki_language' => 'wikidiscover-table-language',
			'wiki_closed' => 'wikidiscover-table-state',
			'wiki_private' => 'wikidiscover-table-visibility',
			'wiki_category' => 'wikidiscover-table-category',
			'wiki_creation' => 'wikidiscover-table-established',
		];

		if ( ExtensionRegistry::getInstance()->isLoaded( 'ManageWiki' ) && $this->getConfig()->get( 'WikiDiscoverUseDescriptions' ) ) {
			$headers['wiki_description'] = 'wikidiscover-table-description';
		}

		foreach ( $headers as &$msg ) {
			$msg = $this->msg( $msg )->text();
		}

		return $headers;
	}

	/** @inheritDoc */
	public function formatValue( $name, $value ) {
		$row = $this->mCurrentRow;

		$wikiDiscover = $this->wikiDiscover;

		$wiki = $row->wiki_dbname;

		switch ( $name ) {
			case 'wiki_dbname':
				$url = $wikiDiscover->getUrl( $wiki );
				$name = $wikiDiscover->getSitename( $wiki );
				$formatted = "<a href=\"{$url}\">{$name}</a>";
				break;
			case 'wiki_language':
				$formatted = $wikiDiscover->getLanguage( $wiki );
				break;
			case 'wiki_closed':
				if ( $wikiDiscover->isDeleted( $wiki ) ) {
					$formatted = 'Deleted';
				} elseif ( $wikiDiscover->isClosed( $wiki ) ) {
					$formatted = 'Closed';
				} elseif ( $wikiDiscover->isInactive( $wiki ) ) {
					$formatted = 'Inactive';
				} else {
					$formatted = 'Open';
				}
				break;
			case 'wiki_private':
				if ( $wikiDiscover->isPrivate( $wiki ) === true ) {
					$formatted = 'Private';
				} else {
					$formatted = 'Public';
				}
				break;
			case 'wiki_category':
				$wikiCategories = array_flip( $this->getConfig()->get( 'CreateWikiCategories' ) );
				$formatted = $wikiCategories[$row->wiki_category] ?? 'Uncategorised';
				break;
			case 'wiki_creation':
				$lang = RequestContext::getMain()->getLanguage();

				$formatted = $lang->date( wfTimestamp( TS_MW, strtotime( $row->wiki_creation ) ) );
				break;
			case 'wiki_description':
				$manageWikiSettings = new ManageWikiSettings( $wiki );

				$value = $manageWikiSettings->list( 'wgWikiDiscoverDescription' );

				$formatted = $value ?? '';
				break;
			default:
				$formatted = "Unable to format $name";
				break;
		}

		return $formatted;
	}

	/** @inheritDoc */
	public function getQueryInfo() {
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

	/** @inheritDoc */
	public function getDefaultSort() {
		return 'wiki_creation';
	}

	/** @inheritDoc */
	protected function isFieldSortable( $field ) {
		return $field !== 'wiki_description';
	}
}
