<?php

use MediaWiki\MediaWikiServices;
use Miraheze\ManageWiki\Helpers\ManageWikiSettings;
use Wikimedia\Rdbms\IReadableDatabase;

class MyWikisWikisPager extends TablePager {
	/** @var string */
	private $language;

	/** @var string */
	private $category;

	/** @var string */
	private $state;

	/** @var WikiDiscover */
	private $wikiDiscover;

	/**
	 * @param SpecialPage $page
	 * @param string $language
	 * @param string $category
	 * @param string $state
	 */
	public function __construct( $page, $language, $category, $state ) {
		$this->mDb = self::getCreateWikiDatabase();

		$this->language = $language;
		$this->category = $category;

		$this->state = $state;

		$this->wikiDiscover = new WikiDiscover();

		parent::__construct( $page->getContext(), $page->getLinkRenderer() );
	}

	/**
	 * @return IReadableDatabase
	 */
	public static function getCreateWikiDatabase() {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$factory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$lb = $factory->getMainLB( $config->get( 'CreateWikiDatabase' ) );

		return $lb->getMaintenanceConnectionRef( DB_REPLICA, 'cw_requests', $config->get( 'CreateWikiDatabase' ) );
	}

	/** @inheritDoc */
	protected function getFieldNames() {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		static $headers = null;

		$headers = [
			'cw_dbname' => 'wikidiscover-table-wiki',
			'cw_language' => 'wikidiscover-table-language',
		];

		$headers += [
			'cw_category' => 'wikidiscover-table-category',
			'cw_timestamp' => 'wikidiscover-table-established',
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
	public function formatRow( $row ) {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'ManageWiki' ) ) {
			$manageWikiSettings = new ManageWikiSettings( $row->wiki_dbname );
			if ( $manageWikiSettings->list( 'wgWikiDiscoverExclude' ) ) {
				return '';
			}
		}

		return parent::formatRow( $row );
	}

	/** @inheritDoc */
	public function formatValue( $name, $value ) {
		$row = $this->mCurrentRow;

		$wikiDiscover = $this->wikiDiscover;

		$wiki = $row->cw_dbname;

		switch ( $name ) {
			case 'cw_dbname':
				$url = $wikiDiscover->getUrl( $wiki );
				$name = $wikiDiscover->getSitename( $wiki );
				$formatted = "<a href=\"{$url}\">{$name}</a>";
				break;
			case 'cw_language':
				$formatted = $wikiDiscover->getLanguage( $wiki );
				break;
			case 'cw_private':
				if ( $row->cw_category === 1 ) {
					$formatted = 'Private';
				} else {
					$formatted = 'Public';
				}
				break;
			case 'cw_category':
				$wikiCategories = array_flip( $this->getConfig()->get( 'CreateWikiCategories' ) );
				$formatted = $wikiCategories[$row->cw_category] ?? 'Uncategorised';
				break;
			case 'cw_timestamp':
				$lang = RequestContext::getMain()->getLanguage();

				$formatted = htmlspecialchars( $lang->date( wfTimestamp( TS_MW, strtotime( $row->cw_timestamp ) ) ) );
				break;
			default:
				$formatted = "Unable to format $name";
				break;
		}

		return $formatted;
	}

	/** @inheritDoc */
	public function getQueryInfo() {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$fields = [];

		$userID = $this->userFactory->newFromName( $userName )->getId();

		$info = [
			'tables' => [ 'cw_requests' ],
			'fields' => array_merge( [ 'cw_dbname', 'cw_language', 'cw_category', 'cw_timestamp' ], $fields ),
			'conds' => [
				'cw_user' => $userID
			],
			'joins_conds' => [],
		];

		if ( $this->language && $this->language !== 'any' ) {
			$info['conds']['cw_language'] = $this->language;
		}

		if ( $this->category && $this->category !== 'any' ) {
			$info['conds']['cw_category'] = $this->category;
		}

		return $info;
	}

	/** @inheritDoc */
	public function getDefaultSort() {
		return 'cw_timestamp';
	}

	/** @inheritDoc */
	protected function isFieldSortable( $field ) {
		return $field !== 'wiki_description';
	}
}
