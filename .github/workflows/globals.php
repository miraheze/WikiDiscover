<?php

define( 'MW_DB', 'wikidb' );

require_once "$IP/extensions/CreateWiki/includes/WikiInitialise.php";
$wi = new WikiInitialise();

$wi->setVariables(
	"$IP/cache",
	[
		''
	],
	[
		'127.0.0.1' => ''
	]
);

$wi->config->settings += [
	'cwClosed' => [
		'default' => false,
	],
	'cwInactive' => [
		'default' => false,
	],
	'cwPrivate' => [
		'default' => false,
	],
];

$wgWikimediaJenkinsCI = true;
$wgCommandLineMode = true;

$wgCreateWikiGlobalWiki = 'wikidb';
$wgCreateWikiDatabase = 'wikidb';
$wgCreateWikiCacheDirectory = "$IP/cache";

$wgHooks['MediaWikiServices'][] = 'wfOnMediaWikiServices';

function wfOnMediaWikiServices() {
	MediaWiki\MediaWikiServices::getInstance()
		->getDBLoadBalancerFactory()
		->disableChronologyProtection();

	$dbr = wfGetDB( DB_REPLICA );

	MediaWiki\MediaWikiServices::getInstance()
		->getDBLoadBalancerFactory()
		->disableChronologyProtection();

	$check = $dbr->selectRow(
		'cw_wikis',
		'*',
		[
			'wiki_dbname' => 'wikidb'
		]
	);

	MediaWiki\MediaWikiServices::getInstance()
		->getDBLoadBalancerFactory()
		->disableChronologyProtection();

	if ( !$check ) {
		$dbw = wfGetDB( DB_PRIMARY );

		MediaWiki\MediaWikiServices::getInstance()
			->getDBLoadBalancerFactory()
			->disableChronologyProtection();

		$dbw->insert(
			'cw_wikis',
			[
				'wiki_dbname' => 'wikidb',
				'wiki_dbcluster' => 'c1',
				'wiki_sitename' => 'TestWiki',
				'wiki_language' => 'en',
				'wiki_private' => (int)0,
				'wiki_creation' => 1632266296,
				'wiki_category' => 'uncategorised',
				'wiki_closed' => (int)0,
				'wiki_deleted' => (int)0,
				'wiki_locked' => (int)0,
				'wiki_inactive' => (int)0,
				'wiki_inactive_exempt' => (int)0
			]
		);

		MediaWiki\MediaWikiServices::getInstance()
			->getDBLoadBalancerFactory()
			->disableChronologyProtection();
	}
}

$wi->readCache();
$wi->config->extractAllGlobals( $wi->dbname );
$wgConf = $wi->config;
