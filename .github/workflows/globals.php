<?php

$wgCreateWikiGlobalWiki = 'wikidb';
$wgCreateWikiDatabase = 'wikidb';
$wgCreateWikiCacheDirectory = "$IP/cache";
$wgContinuousIntegrationInstance = true;
$wgWikimediaJenkinsCI = true;

require_once( "$IP/extensions/CreateWiki/includes/WikiManager.php" );

$factory = new ConfigFactory();
$factory->register( 'createwiki', 'GlobalVarConfig::newInstance' );

$wm = new WikiManager( 'wikidb' );
$wm->create( 'TestWiki', 'en', false, 'uncategorised', 'WikiAdmin', 'WikiAdmin', 'TestWiki' );
