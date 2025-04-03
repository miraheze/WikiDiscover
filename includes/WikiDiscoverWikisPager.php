<?php

namespace Miraheze\WikiDiscover;

use MediaWiki\Context\IContextSource;
use MediaWiki\Html\Html;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Pager\TablePager;
use MediaWiki\Registration\ExtensionRegistry;
use Miraheze\CreateWiki\Services\CreateWikiDatabaseUtils;
use Miraheze\CreateWiki\Services\CreateWikiValidator;
use Miraheze\ManageWiki\Helpers\ManageWikiSettings;

class WikiDiscoverWikisPager extends TablePager {

	public function __construct(
		IContextSource $context,
		CreateWikiDatabaseUtils $databaseUtils,
		LinkRenderer $linkRenderer,
		private readonly CreateWikiValidator $validator,
		private readonly ExtensionRegistry $extensionRegistry,
		private readonly LanguageNameUtils $languageNameUtils,
		private readonly string $category,
		private readonly string $language,
		private readonly string $state,
		private readonly string $visibility
	) {
		$this->mDb = $databaseUtils->getGlobalReplicaDB();
		parent::__construct( $context, $linkRenderer );
	}

	/** @inheritDoc */
	protected function getFieldNames(): array {
		$headers = [
			'wiki_dbname' => $this->msg( 'wikidiscover-table-wiki' )->text(),
			'wiki_language' => $this->msg( 'wikidiscover-table-language' )->text(),
		];

		if ( $this->getConfig()->get( 'CreateWikiUseClosedWikis' ) ) {
			$headers['wiki_closed'] = $this->msg( 'wikidiscover-table-state' )->text();
		}

		if ( $this->getConfig()->get( 'CreateWikiUsePrivateWikis' ) ) {
			$headers['wiki_private'] = $this->msg( 'wikidiscover-table-visibility' )->text();
		}

		$headers += [
			'wiki_category' => $this->msg( 'wikidiscover-table-category' )->text(),
			'wiki_creation' => $this->msg( 'wikidiscover-table-established' )->text(),
		];

		if (
			$this->extensionRegistry->isLoaded( 'ManageWiki' ) &&
			$this->getConfig()->get( 'WikiDiscoverUseDescriptions' )
		) {
			$headers['wiki_description'] = $this->msg( 'wikidiscover-table-description' )->text();
		}

		return $headers;
	}

	/** @inheritDoc */
	public function formatRow( $row ): string {
		if ( $this->extensionRegistry->isLoaded( 'ManageWiki' ) ) {
			$manageWikiSettings = new ManageWikiSettings( $row->wiki_dbname );
			if ( $manageWikiSettings->list( 'wgWikiDiscoverExclude' ) ) {
				return '';
			}
		}

		return parent::formatRow( $row );
	}

	/** @inheritDoc */
	public function formatValue( $name, $value ): string {
		$row = $this->getCurrentRow();

		switch ( $name ) {
			case 'wiki_dbname':
				$url = $row->wiki_url ?: $this->validator->getValidUrl( $row->wiki_dbname );
				$name = $row->wiki_sitename;
				$formatted = Html::element( 'a', [ 'href' => $url ], $name );
				break;
			case 'wiki_language':
				$formatted = $this->languageNameUtils->getLanguageName(
					$row->wiki_language,
					$this->getLanguage()->getCode()
				);
				break;
			case 'wiki_closed':
				$formatted = match ( true ) {
					(bool)$row->wiki_deleted => $this->msg( 'wikidiscover-label-deleted' )->escaped(),
					(bool)$row->wiki_locked => $this->msg( 'wikidiscover-label-locked' )->escaped(),
					(bool)$row->wiki_closed => $this->msg( 'wikidiscover-label-closed' )->escaped(),
					(bool)$row->wiki_inactive => $this->msg( 'wikidiscover-label-inactive' )->escaped(),
					default => $this->msg( 'wikidiscover-label-open' )->escaped(),
				};
				break;
			case 'wiki_private':
				if ( $row->wiki_private ) {
					$formatted = $this->msg( 'wikidiscover-label-private' )->escaped();
				} else {
					$formatted = $this->msg( 'wikidiscover-label-public' )->escaped();
				}
				break;
			case 'wiki_category':
				$wikiCategories = array_flip( $this->getConfig()->get( 'CreateWikiCategories' ) );
				$formatted = $this->escape( $wikiCategories[$row->wiki_category] ?? 'uncategorised' );
				break;
			case 'wiki_creation':
				$formatted = $this->escape( $this->getLanguage()->userTimeAndDate(
					$row->wiki_creation, $this->getUser()
				) );
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

	/**
	 * Safely HTML-escapes $value
	 */
	private function escape( string $value ): string {
		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8', false );
	}

	/** @inheritDoc */
	public function getQueryInfo(): array {
		$fields = [];
		if ( $this->getConfig()->get( 'CreateWikiUseClosedWikis' ) ) {
			$fields[] = 'wiki_closed';
		}

		if ( $this->getConfig()->get( 'CreateWikiUseInactiveWikis' ) ) {
			$fields[] = 'wiki_inactive';
		}

		if ( $this->getConfig()->get( 'CreateWikiUsePrivateWikis' ) ) {
			$fields[] = 'wiki_private';
		}

		$info = [
			'tables' => [ 'cw_wikis' ],
			'fields' => array_merge( [
				'wiki_dbname',
				'wiki_language',
				'wiki_deleted',
				'wiki_locked',
				'wiki_category',
				'wiki_creation',
				'wiki_sitename',
				'wiki_url',
			], $fields ),
			'conds' => [],
			'joins_conds' => [],
		];

		if ( $this->language && $this->language !== '*' ) {
			$info['conds']['wiki_language'] = $this->language;
		}

		if ( $this->category && $this->category !== '*' ) {
			$info['conds']['wiki_category'] = $this->category;
		}

		if ( $this->state && $this->state !== '*' ) {
			if ( $this->state === 'deleted' ) {
				$info['conds']['wiki_deleted'] = 1;
			} elseif ( $this->state === 'locked' ) {
				$info['conds']['wiki_locked'] = 1;
			} elseif (
				$this->getConfig()->get( 'CreateWikiUseClosedWikis' ) &&
				$this->state === 'closed'
			) {
				$info['conds']['wiki_closed'] = 1;
				$info['conds']['wiki_deleted'] = 0;
			} elseif (
				$this->getConfig()->get( 'CreateWikiUseInactiveWikis' ) &&
				$this->state === 'inactive'
			) {
				$info['conds']['wiki_deleted'] = 0;
				$info['conds']['wiki_inactive'] = 1;
			} elseif ( $this->state === 'active' ) {
				$info['conds']['wiki_deleted'] = 0;
				if ( $this->getConfig()->get( 'CreateWikiUseClosedWikis' ) ) {
					$info['conds']['wiki_closed'] = 0;
				}

				if ( $this->getConfig()->get( 'CreateWikiUseInactiveWikis' ) ) {
					$info['conds']['wiki_inactive'] = 0;
				}
			}
		}

		if (
			$this->getConfig()->get( 'CreateWikiUsePrivateWikis' ) &&
			$this->visibility &&
			$this->visibility !== '*'
		) {
			if ( $this->visibility === 'public' ) {
				$info['conds']['wiki_private'] = 0;
			} elseif ( $this->visibility === 'private' ) {
				$info['conds']['wiki_private'] = 1;
			}
		}

		return $info;
	}

	/** @inheritDoc */
	public function getDefaultSort(): string {
		return 'wiki_creation';
	}

	/** @inheritDoc */
	protected function isFieldSortable( $field ): bool {
		return $field !== 'wiki_description';
	}
}
