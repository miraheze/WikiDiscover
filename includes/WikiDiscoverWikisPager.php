<?php

namespace Miraheze\WikiDiscover;

use MediaWiki\Context\RequestContext;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Pager\TablePager;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\ManageWiki\Helpers\ManageWikiSettings;
use Wikimedia\Rdbms\IReadableDatabase;

class WikiDiscoverWikisPager extends TablePager {
	/** @var string */
	private $language;

	/** @var string */
	private $category;

	/** @var string */
	private $state;

	/** @var string */
	private $visibility;

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

		parent::__construct( $page->getContext(), $page->getLinkRenderer() );
	}

	/**
	 * @return IReadableDatabase
	 */
	public static function getCreateWikiDatabase() {
		$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
		return $connectionProvider->getReplicaDatabase( 'virtual-createwiki', 'cw_wikis' );
	}

	/** @inheritDoc */
	protected function getFieldNames() {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		static $headers = null;

		$headers = [
			'wiki_dbname' => 'wikidiscover-table-wiki',
			'wiki_language' => 'wikidiscover-table-language',
		];

		if ( $config->get( 'CreateWikiUseClosedWikis' ) ) {
			$headers['wiki_closed'] = 'wikidiscover-table-state';
		}

		if ( $config->get( 'CreateWikiUsePrivateWikis' ) ) {
			$headers['wiki_private'] = 'wikidiscover-table-visibility';
		}

		$headers += [
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
	public function formatRow( $row ) {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'ManageWiki' ) ) {
			$manageWikiSettings = new ManageWikiSettings( $row->wiki_dbname );
			if ( $manageWikiSettings->list( 'wgWikiDiscoverExclude' ) ) {
				return '';
			}
		}

		return parent::formatRow( $row );
	}

	/**
	 * Safely HTML-escapes $value
	 *
	 * @param string $value
	 * @return string
	 */
	private static function escape( $value ) {
		return htmlspecialchars( $value, ENT_QUOTES );
	}

	/** @inheritDoc */
	public function formatValue( $name, $value ) {
		$row = $this->mCurrentRow;

		switch ( $name ) {
			case 'wiki_dbname':
				$url = $row->wiki_url;
				if ( !$url ) {
					$domain = $this->getConfig()->get( 'CreateWikiSubdomain' );
					$subdomain = substr(
						$row->wiki_dbname, 0,
						-strlen( $this->getConfig()->get( 'CreateWikiDatabaseSuffix' ) )
					);
					$url = "https://$subdomain.$domain";
				}
				$name = $row->wiki_sitename;
				$formatted = Html::element( 'a', [ 'href' => $url ], $name );
				break;
			case 'wiki_language':
				$formatted = $this->escape(
					MediaWikiServices::getInstance()->getLanguageNameUtils()->getLanguageName(
						$row->wiki_language
					)
				);
				break;
			case 'wiki_closed':
				if ( $row->wiki_deleted ) {
					$formatted = 'Deleted';
				} elseif ( $row->wiki_locked ) {
					$formatted = 'Locked';
				} elseif ( $row->wiki_closed ) {
					$formatted = 'Closed';
				} elseif ( $row->wiki_inactive ) {
					$formatted = 'Inactive';
				} else {
					$formatted = 'Open';
				}
				break;
			case 'wiki_private':
				if ( $row->wiki_private ) {
					$formatted = 'Private';
				} else {
					$formatted = 'Public';
				}
				break;
			case 'wiki_category':
				$wikiCategories = array_flip( $this->getConfig()->get( 'CreateWikiCategories' ) );
				$formatted = $this->escape( $wikiCategories[$row->wiki_category] ?? 'Uncategorised' );
				break;
			case 'wiki_creation':
				$lang = RequestContext::getMain()->getLanguage();

				$formatted = $this->escape( $lang->date( wfTimestamp( TS_MW, strtotime( $row->wiki_creation ) ) ) );
				break;
			case 'wiki_description':
				$manageWikiSettings = new ManageWikiSettings( $row->wiki_dbname );

				$value = $manageWikiSettings->list( 'wgWikiDiscoverDescription' );

				$formatted = $this->escape( $value ?? '' );
				break;
			default:
				$formatted = $this->escape( "Unable to format $name" );
				break;
		}

		return $formatted;
	}

	/** @inheritDoc */
	public function getQueryInfo() {
		$config = MediaWikiServices::getInstance()->getMainConfig();

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

		$info = [
			'tables' => [ 'cw_wikis' ],
			'fields' => array_merge( [ 'wiki_dbname', 'wiki_language', 'wiki_deleted', 'wiki_locked', 'wiki_category', 'wiki_creation', 'wiki_sitename', 'wiki_url' ], $fields ),
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
			} elseif ( $this->state === 'locked' ) {
				$info['conds']['wiki_locked'] = 1;
			} elseif ( $config->get( 'CreateWikiUseClosedWikis' ) && $this->state === 'closed' ) {
				$info['conds']['wiki_closed'] = 1;
				$info['conds']['wiki_deleted'] = 0;
			} elseif ( $config->get( 'CreateWikiUseInactiveWikis' ) && $this->state === 'inactive' ) {
				$info['conds']['wiki_deleted'] = 0;
				$info['conds']['wiki_inactive'] = 1;
			} elseif ( $this->state === 'active' ) {
				$info['conds']['wiki_deleted'] = 0;
				if ( $config->get( 'CreateWikiUseClosedWikis' ) ) {
					$info['conds']['wiki_closed'] = 0;
				}

				if ( $config->get( 'CreateWikiUseInactiveWikis' ) ) {
					$info['conds']['wiki_inactive'] = 0;
				}
			}
		}

		if ( $config->get( 'CreateWikiUsePrivateWikis' ) && $this->visibility && $this->visibility !== 'any' ) {
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
