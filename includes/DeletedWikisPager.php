<?php

namespace Miraheze\WikiDiscover;

use MediaWiki\Context\IContextSource;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Pager\TablePager;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\CreateWiki\Services\CreateWikiDatabaseUtils;

class DeletedWikisPager extends TablePager {

	public function __construct(
		private readonly ExtensionRegistry $extensionRegistry,
		CreateWikiDatabaseUtils $databaseUtils,
		IContextSource $context,
		LinkRenderer $linkRenderer
	) {
		parent::__construct( $context, $linkRenderer );
		$this->mDb = $databaseUtils->getGlobalReplicaDB();
	}

	/** @inheritDoc */
	public function getFieldNames(): array {
		$headers = [
			'wiki_dbname' => $this->msg( 'wikidiscover-label-dbname' )->text(),
			'wiki_creation' => $this->msg( 'wikidiscover-label-creationdate' )->text(),
			'wiki_deleted_timestamp' => $this->msg( 'wikidiscover-label-deletiondate' )->text(),
		];

		if ( $this->extensionRegistry->isLoaded( 'ManageWiki' ) ) {
			$headers['wiki_deleted'] = $this->msg( 'wikidiscover-label-undeletewiki' )->text();
		}

		return $headers;
	}

	/** @inheritDoc */
	public function formatValue( $name, $value ): string {
		$row = $this->getCurrentRow();

		switch ( $name ) {
			case 'wiki_dbname':
				$formatted = $this->escape( $row->wiki_dbname );
				break;
			case 'wiki_creation':
				$formatted = $this->escape( $this->getLanguage()->userTimeAndDate(
					$row->wiki_creation, $this->getUser()
				) );
				break;
			case 'wiki_deleted_timestamp':
				$formatted = $this->escape( $this->getLanguage()->userTimeAndDate(
					$row->wiki_deleted_timestamp, $this->getUser()
				) );
				break;
			case 'wiki_deleted':
				$formatted = $this->getLinkRenderer()->makeExternalLink(
					SpecialPage::getTitleFor( 'ManageWiki', 'core/' . $row->wiki_dbname )->getFullURL(),
					$this->msg( 'wikidiscover-label-goto-managewiki' )->text(),
					SpecialPage::getTitleFor( 'ManageWiki', 'core' )
				);
				break;
			default:
				$formatted = $this->escape( "Unable to format $name" );
		}

		return $formatted;
	}

	/**
	 * Safely HTML-escape $value
	 */
	private static function escape( string $value ): string {
		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8', false );
	}

	/** @inheritDoc */
	public function getQueryInfo(): array {
		return [
			'tables' => [
				'cw_wikis',
			],
			'fields' => [
				'wiki_dbname',
				'wiki_creation',
				'wiki_deleted',
				'wiki_deleted_timestamp',
			],
			'conds' => [
				'wiki_deleted' => 1,
			],
			'joins_conds' => [],
		];
	}

	/** @inheritDoc */
	public function getDefaultSort(): string {
		return 'wiki_dbname';
	}

	/** @inheritDoc */
	public function isFieldSortable( $name ): bool {
		return true;
	}
}
