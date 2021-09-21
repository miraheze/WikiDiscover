<?php

$wgCreateWikiGlobalWiki = 'wikidb';
$wgCreateWikiDatabase = 'wikidb';
$wgCreateWikiCacheDirectory = "$IP/cache";
$wgContinuousIntegrationInstance = true;
$wgWikimediaJenkinsCI = true;

$wgHooks['LoadExtensionSchemaUpdates'][] = 'wfOnLoadExtensionSchemaUpdates';

function wfOnLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
	$wm = new WikiManager( 'wikidb' );
	$wm->create( 'TestWiki', 'en', false, 'uncategorised', 'WikiAdmin', 'WikiAdmin', 'TestWiki' );
}
