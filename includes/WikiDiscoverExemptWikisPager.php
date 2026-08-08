<?php

namespace Miraheze\WikiDiscover;

use MediaWiki\Context\IContextSource;
use MediaWiki\Html\Html;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Pager\TablePager;
use Miraheze\CreateWiki\Services\CreateWikiDatabaseUtils;
use Miraheze\CreateWiki\Services\CreateWikiValidator;
use Miraheze\CreateWiki\Services\RemoteWikiFactory;

class WikiDiscoverExemptWikisPager extends TablePager {

	public function __construct(
		IContextSource $context,
		CreateWikiDatabaseUtils $databaseUtils,
		LinkRenderer $linkRenderer,
		private readonly CreateWikiValidator $validator,
		private readonly LanguageNameUtils $languageNameUtils,
		private readonly RemoteWikiFactory $remoteWikiFactory,
		private readonly string $category,
		private readonly string $language
	) {
		$this->mDb = $databaseUtils->getGlobalReplicaDB();
		parent::__construct( $context, $linkRenderer );
	}

	/** @inheritDoc */
	protected function getFieldNames(): array {
		$headers = [
			'wiki_dbname' => $this->msg( 'wikidiscover-table-wiki' )->text(),
			'wiki_language' => $this->msg( 'wikidiscover-table-language' )->text(),
			'wiki_category' => $this->msg( 'wikidiscover-table-category' )->text(),
			'wiki_inactive_exempt_reason' => $this->msg( 'wikidiscover-inactivityexempt-table-reason' )->text(),
		];

		if ( $this->getConfig()->get( 'WikiDiscoverShowExemptActor' ) ) {
			$headers['wiki_inactive_exempt_actor'] = $this->msg( 'wikidiscover-inactivityexempt-table-actor' )->text();
		}

		return $headers;
	}

	/** @inheritDoc */
	public function formatValue( $field, $value ): string {
		$row = $this->getCurrentRow();
		$value ??= '';

		switch ( $field ) {
			case 'wiki_dbname':
				$url = $row->wiki_url ?: $this->validator->getValidUrl( $value );
				$name = $row->wiki_sitename;
				$formatted = Html::element( 'a', [ 'href' => $url ], $name );
				break;

			case 'wiki_language':
				$formatted = $this->escape(
					$this->languageNameUtils->getLanguageName(
						$row->wiki_language,
						$this->getLanguage()->getCode()
					)
				);
				break;

			case 'wiki_category':
				$wikiCategories = array_flip( $this->getConfig()->get( 'CreateWikiCategories' ) );
				$formatted = $this->escape( $wikiCategories[$value] ?? $value );
				break;

			case 'wiki_inactive_exempt_reason':
				$options = $this->getConfig()->get( 'CreateWikiInactiveExemptReasonOptions' );
				if ( is_array( $options ) ) {
					$label = array_flip( $options )[$value] ?? null;
					$formatted = $this->escape( $label ?? $value );
				} else {
					$formatted = $this->escape( $value );
				}
				break;

			case 'wiki_inactive_exempt_actor':
				$remoteWiki = $this->remoteWikiFactory->newInstance( $row->wiki_dbname );
				$actorId = (int)$remoteWiki->getExtraFieldData( 'inactive_exempt_actor', default: 0 );
				if ( $actorId ) {
					$actorName = $this->resolveActorName( $actorId );
					$formatted = $actorName !== null
						? $this->escape( $actorName )
						: $this->msg( 'wikidiscover-inactivityexempt-actor-unknown' )->escaped();
				} else {
					$formatted = $this->msg( 'wikidiscover-inactivityexempt-actor-unknown' )->escaped();
				}
				break;

			default:
				$formatted = $this->escape( "Unable to format $field" );
		}

		return $formatted;
	}

	private function resolveActorName( int $actorId ): ?string {
		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromActorId( $actorId );
		return $user ? $user->getName() : null;
	}

	private function escape( ?string $value ): string {
		return htmlspecialchars( $value ?? '', ENT_QUOTES );
	}

	/** @inheritDoc */
	public function getQueryInfo(): array {
		$info = [
			'tables' => [ 'cw_wikis' ],
			'fields' => [
				'wiki_dbname',
				'wiki_language',
				'wiki_category',
				'wiki_sitename',
				'wiki_url',
				'wiki_inactive_exempt_reason',
			],
			'conds' => [
				'wiki_inactive_exempt' => 1,
				'wiki_deleted' => 0,
			],
			'joins_conds' => [],
		];

		if ( $this->language && $this->language !== '*' ) {
			$info['conds']['wiki_language'] = $this->language;
		}

		if ( $this->category && $this->category !== '*' ) {
			$info['conds']['wiki_category'] = $this->category;
		}

		return $info;
	}

	/** @inheritDoc */
	public function getDefaultSort(): string {
		return 'wiki_dbname';
	}

	/** @inheritDoc */
	protected function isFieldSortable( $field ): bool {
		return $field !== 'wiki_inactive_exempt_actor';
	}
}
