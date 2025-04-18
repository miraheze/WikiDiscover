<?php

namespace Miraheze\WikiDiscover\HookHandlers;

use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use Miraheze\CreateWiki\Services\RemoteWikiFactory;
use Miraheze\ManageWiki\Hooks\ManageWikiCoreAddFormFieldsHook;
use Miraheze\ManageWiki\Hooks\ManageWikiCoreFormSubmissionHook;
use Wikimedia\Rdbms\IDatabase;

class ManageWiki implements
	ManageWikiCoreAddFormFieldsHook,
	ManageWikiCoreFormSubmissionHook
{

	public function __construct(
		private readonly Config $config
	) {
	}

	public function onManageWikiCoreAddFormFields(
		IContextSource $context,
		RemoteWikiFactory $remoteWiki,
		string $dbName,
		bool $ceMW,
		array &$formDescriptor
	): void {
		if ( !$this->config->get( 'WikiDiscoverUseDescriptions' ) ) {
			return;
		}

		$formDescriptor['description'] = [
			'label-message' => 'wikidiscover-label-description',
			'type' => 'text',
			'default' => $remoteWiki->getExtraFieldData( 'description', default: '' ),
			'maxlength' => $this->config->get( 'WikiDiscoverDescriptionsMaxLength' ),
			'disabled' => !$ceMW,
			'section' => 'main',
		];
	}

	public function onManageWikiCoreFormSubmission(
		IContextSource $context,
		IDatabase $dbw,
		RemoteWikiFactory $remoteWiki,
		string $dbName,
		array $formData
	): void {
		if ( !isset( $formData['description'] ) ) {
			return;
		}

		$remoteWiki->setExtraFieldData(
			'description', $formData['description'], default: ''
		);
	}
}
