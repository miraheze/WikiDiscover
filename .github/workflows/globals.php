<?php

$wgCreateWikiGlobalWiki = 'wikidb';
$wgCreateWikiDatabase = 'wikidb';
$wgCreateWikiCacheDirectory = "$IP/cache";
$wgContinuousIntegrationInstance = true;
$wgWikimediaJenkinsCI = true;

$wgHooks['MediaWikiServices'][] = 'wfOnMediaWikiServices';

function wfOnMediaWikiServices() {
	$wm = new WikiManager( 'wikidb' );
	$wm->create( 'TestWiki', 'en', false, 'uncategorised', 'WikiAdmin', 'WikiAdmin', 'TestWiki' );
}
