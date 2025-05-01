<?php

namespace Miraheze\WikiDiscover\HookHandlers;

use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use Miraheze\ManageWiki\Helpers\Factories\ModuleFactory;
use Miraheze\ManageWiki\Hooks\ManageWikiCoreAddFormFieldsHook;
use Miraheze\ManageWiki\Hooks\ManageWikiCoreFormSubmissionHook;

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
		ModuleFactory $moduleFactory,
		string $dbname,
		bool $ceMW,
		array &$formDescriptor
	): void {
		if ( !$this->config->get( 'WikiDiscoverUseDescriptions' ) ) {
			return;
		}

		$mwCore = $moduleFactory->core( $dbname );
		$formDescriptor['description'] = [
			'label-message' => 'wikidiscover-label-description',
			'type' => 'text',
			'default' => $mwCore->getExtraFieldData( 'description', default: '' ),
			'maxlength' => $this->config->get( 'WikiDiscoverDescriptionsMaxLength' ),
			'disabled' => !$ceMW,
			'section' => 'main',
		];
	}

	public function onManageWikiCoreFormSubmission(
		IContextSource $context,
		ModuleFactory $moduleFactory,
		string $dbname,
		array $formData
	): void {
		if ( !isset( $formData['description'] ) ) {
			return;
		}

		$mwCore = $moduleFactory->core( $dbname );
		$mwCore->setExtraFieldData(
			'description', $formData['description'], default: ''
		);
	}
}
