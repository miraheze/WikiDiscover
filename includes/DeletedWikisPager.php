<?php

namespace Miraheze\WikiDiscover;

use ExtensionRegistry;
use MediaWiki\Linker\Linker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Pager\TablePager;
use MediaWiki\SpecialPage\SpecialPage;

class DeletedWikisPager extends TablePager {

	public function __construct( $page ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$this->mDb = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()
			->getMainLB( $config->get( 'CreateWikiDatabase' ) )
			->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

		parent::__construct( $page->getContext(), $page->getLinkRenderer() );
	}

	public function getFieldNames() {
		static $headers = null;

		$headers = [
			'wiki_dbname' => 'wikidiscover-label-dbname',
			'wiki_creation' => 'wikidiscover-label-creationdate',
			'wiki_deleted_timestamp' => 'wikidiscover-label-deletiondate',
		];

		if ( ExtensionRegistry::getInstance()->isLoaded( 'ManageWiki' ) ) {
			$headers['wiki_deleted'] = 'wikidiscover-label-undeletewiki';
		}

		foreach ( $headers as &$msg ) {
			$msg = $this->msg( $msg )->text();
		}

		return $headers;
	}

	/**
	 * Safely HTML-escape $value
	 *
	 * @param string $value
	 * @return string
	 */
	private static function escape( $value ) {
		return htmlspecialchars( $value, ENT_QUOTES );
	}

	public function formatValue( $name, $value ) {
		$row = $this->mCurrentRow;

		switch ( $name ) {
			case 'wiki_dbname':
				$formatted = $this->escape( $row->wiki_dbname );
				break;
			case 'wiki_creation':
				$formatted = $this->escape( wfTimestamp( TS_RFC2822, (int)$row->wiki_creation ) );
				break;
			case 'wiki_deleted_timestamp':
				$formatted = $this->escape( wfTimestamp( TS_RFC2822, (int)$row->wiki_deleted_timestamp ) );
				break;
			case 'wiki_deleted':
				$formatted = Linker::makeExternalLink( SpecialPage::getTitleFor( 'ManageWiki' )->getFullURL() . '/core/' . $row->wiki_dbname, $this->msg( 'managewiki-label-goto' )->text() );
				break;
			default:
				$formatted = $this->escape( "Unable to format $name" );
				break;
		}
		return $formatted;
	}

	public function getQueryInfo() {
		return [
			'tables' => [
				'cw_wikis'
			],
			'fields' => [
				'wiki_dbname',
				'wiki_creation',
				'wiki_deleted',
				'wiki_deleted_timestamp'
			],
			'conds' => [
				'wiki_deleted' => 1
			],
			'joins_conds' => [],
		];
	}

	public function getDefaultSort() {
		return 'wiki_dbname';
	}

	public function isFieldSortable( $name ) {
		return true;
	}
}
