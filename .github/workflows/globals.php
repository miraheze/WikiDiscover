<?php

$wgCreateWikiGlobalWiki = 'wikidb';
$wgCreateWikiDatabase = 'wikidb';
$wgCreateWikiCacheDirectory = "$IP/cache";
$wgContinuousIntegrationInstance = true;
$wgWikimediaJenkinsCI = true;

$wgHooks['SetupAfterCache'][] = 'wfOnSetupAfterCache';

function wfOnSetupAfterCache() {
	$wm = new WikiManager( 'wikidb' );
	$wm->create( 'TestWiki', 'en', false, 'uncategorised', 'WikiAdmin', 'WikiAdmin', 'TestWiki' );
}
