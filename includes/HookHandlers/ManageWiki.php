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

	/**
	 * @inheritDoc
	 * @param IContextSource $context @phan-unused-param
	 */
	public function onManageWikiCoreAddFormFields(
		IContextSource $context,
		ModuleFactory $moduleFactory,
		string $dbname,
		bool $ceMW,
		array &$formDescriptor
	): void {
		if ( $this->config->get( 'WikiDiscoverUseDescriptions' ) ) {
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
	}

	/**
	 * @inheritDoc
	 * @param IContextSource $context @phan-unused-param
	 */
	public function onManageWikiCoreFormSubmission(
		IContextSource $context,
		ModuleFactory $moduleFactory,
		string $dbname,
		array $formData
	): void {
		$mwCore = $moduleFactory->core( $dbname );

		if ( isset( $formData['description'] ) ) {
			$mwCore->setExtraFieldData(
				'description', $formData['description'], default: ''
			);
		}

		if (
			$this->config->get( 'CreateWikiUseInactiveWikis' ) &&
			isset( $formData['inactive-exempt'] )
		) {
			if ( $formData['inactive-exempt'] ) {
				$actorId = $context->getUser()->getActorId();
				$mwCore->setExtraFieldData(
					'inactive_exempt_actor', $actorId, default: 0
				);
			} else {
				$mwCore->setExtraFieldData(
					'inactive_exempt_actor', 0, default: 0
				);
			}
		}
	}
}
