<?php

$wgCreateWikiGlobalWiki = 'wikidb';
$wgCreateWikiDatabase = 'wikidb';
$wgCreateWikiCacheDirectory = "$IP/cache";
$wgContinuousIntegrationInstance = true;
$wgWikimediaJenkinsCI = true;

$wgHooks['MediaWikiServices'][] = 'wfOnMediaWikiServices';

function wfOnMediaWikiServices() {
	$dbw = wfGetDB( DB_PRIMARY );
	$dbw->insert(
		'cw_wikis',
		[
			'wiki_dbname' => 'wikidb',
			'wiki_dbcluster' => 'c1',
			'wiki_sitename' => 'TestWiki',
			'wiki_language' => 'en',
			'wiki_private' => (int)0,
			'wiki_creation' => 1632266296,
			'wiki_category' => 'uncategorised'
		]
	);
}
