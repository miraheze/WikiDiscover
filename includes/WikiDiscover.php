<?php

use MediaWiki\MediaWikiServices;

class WikiDiscover {
	/** @var Config */
	private $config;

	/** @var array */
	private $closed = [];

	/** @var array */
	private $inactive = [];

	/** @var array */
	private $private = [];

	/** @var array */
	private $deleted = [];

	/** @var array */
	private $locked = [];

	/** @var array */
	private $langCodes = [];

	public function __construct() {
		$this->config = MediaWikiServices::getInstance()->getMainConfig();
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

		$dbr = $lbFactory->getMainLB( $this->config->get( 'CreateWikiDatabase' ) )
			->getMaintenanceConnectionRef( DB_REPLICA, [], $this->config->get( 'CreateWikiDatabase' ) );

		$res = $dbr->select(
			'cw_wikis', [
				'wiki_dbname',
				'wiki_language',
				'wiki_private',
				'wiki_closed',
				'wiki_inactive',
				'wiki_locked',
				'wiki_deleted',
			],
		);

		if ( $res ) {
			foreach ( $res as $row ) {
				$this->langCodes[$row->wiki_dbname] = $row->wiki_language;

				if ( $row->wiki_private ) {
					$this->private[] = $row->wiki_dbname;
				}

				if ( $row->wiki_closed ) {
					$this->closed[] = $row->wiki_dbname;
				}

				if ( $row->wiki_inactive ) {
					$this->inactive[] = $row->wiki_dbname;
				}

				if ( $row->wiki_deleted ) {
					$this->deleted[] = $row->wiki_dbname;
				}

				if ( $row->wiki_locked ) {
					$this->locked[] = $row->wiki_dbname;
				}
			}
		}
	}

	/**
	 * @return int
	 */
	public function getCount() {
		return count( $this->config->get( 'LocalDatabases' ) );
	}

	/**
	 * @param string $dbname
	 * @return array
	 */
	public function getWikis( $dbname ) {
		$wikis = [];

		$wikiList = $dbname ? explode( ',', $dbname ) : $this->config->get( 'LocalDatabases' );

		foreach ( $wikiList as $db ) {
			if ( preg_match( "/(.*)wiki\$/", $db, $a ) ) {
				$wiki = $a[1];
				$wikis[] = [ $wiki, 'wiki' ];
			}
		}

		return $wikis;
	}

	/**
	 * @param string $dbname
	 * @return string[]
	 */
	public function getWikiPrefixes( $dbname ) {
		$wikiprefixes = [];

		$wikiList = $dbname ? explode( ',', $dbname ) : $this->config->get( 'LocalDatabases' );

		foreach ( $wikiList as $db ) {
			if ( preg_match( "/(.*)wiki\$/", $db, $a ) ) {
				$wikiprefixes[] = $a[1];
			}
		}

		return $wikiprefixes;
	}

	/**
	 * @param string $database
	 * @return string
	 */
	public function getUrl( $database ) {
		return $this->config->get( 'Conf' )->get( 'wgServer', $database );
	}

	/**
	 * @param string $database
	 * @return string
	 */
	public function getSitename( $database ) {
		return $this->config->get( 'Conf' )->get( 'wgSitename', $database );
	}

	/**
	 * @param string $database
	 * @return string
	 */
	public function getLanguageCode( $database ) {
		return $this->langCodes[$database];
	}

	/**
	 * @param string $database
	 * @return string Language name or empty
	 */
	public function getLanguage( $database ) {
		$languagecode = $this->getLanguageCode( $database );

		return MediaWikiServices::getInstance()->getLanguageNameUtils()->getLanguageName( $languagecode );
	}

	/**
	 * @param string $database
	 * @return bool
	 */
	public function isClosed( $database ) {
		return in_array( $database, $this->closed );
	}

	/**
	 * @param string $database
	 * @return bool
	 */
	public function isInactive( $database ) {
		return in_array( $database, $this->inactive );
	}

	/**
	 * @param string $database
	 * @return bool
	 */
	public function isPrivate( $database ) {
		return in_array( $database, $this->private );
	}

	/**
	 * @param string $database
	 * @return bool
	 */
	public function isDeleted( $database ) {
		return in_array( $database, $this->deleted );
	}

	/**
	 * @param string $database
	 * @return bool
	 */
	public function isLocked( $database ) {
		return in_array( $database, $this->locked );
	}

	/**
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'numberofwikisincategory', [ __CLASS__, 'numberOfWikisInCategory' ], Parser::SFH_NO_HASH );
		$parser->setFunctionHook( 'numberofwikisinlanguage', [ __CLASS__, 'numberOfWikisInLanguage' ], Parser::SFH_NO_HASH );
		$parser->setFunctionHook( 'numberofwikisbysetting', [ __CLASS__, 'numberOfWikisBySetting' ], Parser::SFH_NO_HASH );
		$parser->setFunctionHook( 'wikicreationdate', [ __CLASS__, 'wikiCreationDate' ], Parser::SFH_NO_HASH );
	}

	/**
	 * @param Parser $parser
	 * @param string $category Default to 'uncategorised'
	 * @return int
	 */
	public static function numberOfWikisInCategory( Parser $parser, string $category = 'uncategorised' ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

		$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
			->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

		return $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_category' => strtolower( $category ) ] );
	}

	/**
	 * @param Parser $parser
	 * @param string $language Default to 'en'
	 * @return int
	 */
	public static function numberOfWikisInLanguage( Parser $parser, string $language = 'en' ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

		$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
			->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

		return $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_language' => strtolower( $language ) ] );
	}

	/**
	 * @param Parser $parser
	 * @param mixed $setting Default to null
	 * @param mixed $value Default to null
	 * @return string|int
	 */
	public static function numberOfWikisBySetting( Parser $parser, $setting = null, $value = null ) {
		if ( !$setting && !$value ) {
			return 'Error: no input specified.';
		}

		$config = MediaWikiServices::getInstance()->getMainConfig();
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

		$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
			->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

		$extList = array_keys( $config->get( 'ManageWikiExtensions' ) );

		if ( !$value && !in_array( $setting, $extList ) ) {
			return 0;
		}

		if ( in_array( $setting, $extList ) ) {
			$selectExtensions = implode( ',', $dbr->selectFieldValues( 'mw_settings', 's_extensions' ) );

			return substr_count( $selectExtensions, '"' . $setting . '"' );
		}

		$selectSettings = $dbr->selectFieldValues( 'mw_settings', 's_settings' );
		$settingUsageCount = 0;

		foreach ( $selectSettings as $key ) {
			if ( !is_bool( array_search( $value, (array)( json_decode( $key, true )[$setting] ?? [] ) ) ) ) {
				$settingUsageCount++;
			}
		}

		return $settingUsageCount;
	}

	/**
	 * @param Parser $parser
	 * @param ?string $database
	 * @return string
	 */
	public static function wikiCreationDate( Parser $parser, ?string $database = null ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$lang = RequestContext::getMain()->getLanguage();

		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

		$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
			->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

		$wikiDatabase = $database ?? $config->get( 'DBname' );

		$creationDate = $dbr->selectField( 'cw_wikis', 'wiki_creation', [ 'wiki_dbname' => $wikiDatabase ] );

		return $lang->date( wfTimestamp( TS_MW, strtotime( $creationDate ) ) );
	}

	/**
	 * @param Parser $parser
	 * @param array &$cache
	 * @param string $magicWordId
	 * @param string &$ret
	 */
	public static function onParserGetVariableValueSwitch(
		Parser $parser,
		&$cache,
		$magicWordId,
		&$ret ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		switch ( $magicWordId ) {
			case 'numberofwikis':
				$ret = $cache[$magicWordId] = count( $config->get( 'LocalDatabases' ) );
				break;
			case 'numberofprivatewikis':
				$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

				$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
					->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_private' => 1 ] );
				break;
			case 'numberofpublicwikis':
				$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

				$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
					->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_private' => 0 ] );
				break;
			case 'numberofactivewikis':
				$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

				$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
					->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_closed' => 0, 'wiki_deleted' => 0, 'wiki_inactive' => 0 ] );
				break;
			case 'numberofinactivewikis':
				$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

				$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
					->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_inactive' => 1 ] );
				break;
			case 'numberofclosedwikis':
				$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

				$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
					->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_closed' => 1 ] );
				break;
			case 'numberoflockedwikis':
				$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

				$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
					->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_locked' => 1 ] );
				break;
			case 'numberofdeletedwikis':
				$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

				$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
					->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 1 ] );
				break;
			case 'numberofinactivityexemptwikis':
				$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

				$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
					->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', '*', [ 'wiki_deleted' => 0, 'wiki_inactive_exempt' => 1 ] );
				break;
			case 'numberofcustomdomains':
				$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

				$dbr = $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
					->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );

				$ret = $cache[$magicWordId] = $dbr->selectRowCount( 'cw_wikis', 'wiki_url', [ 'wiki_deleted' => 0 ] );
				break;
			case 'wikicreationdate':
				$ret = $cache[$magicWordId] = self::wikiCreationDate( $parser );
				break;
		}
	}

	/**
	 * @param array &$variableIDs
	 */
	public static function onGetMagicVariableIDs( &$variableIDs ) {
		$variableIDs[] = 'numberofwikis';
		$variableIDs[] = 'numberofprivatewikis';
		$variableIDs[] = 'numberofpublicwikis';
		$variableIDs[] = 'numberofactivewikis';
		$variableIDs[] = 'numberofinactivewikis';
		$variableIDs[] = 'numberofclosedwikis';
		$variableIDs[] = 'numberoflockedwikis';
		$variableIDs[] = 'numberofdeletedwikis';
		$variableIDs[] = 'numberofinactivityexemptwikis';
		$variableIDs[] = 'numberofcustomdomains';
		$variableIDs[] = 'wikicreationdate';
	}
}
