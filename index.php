<?php
	
	defined('APPLICATION_ENV')
    	|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

    // Define path to application directory
	defined('APPLICATION_PATH')
		|| define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

    // Define path to data directory, used with the cache
	defined('DATA_PATH')
		|| define('DATA_PATH', realpath(dirname(__FILE__) . '/../data'));

	set_include_path(implode(PATH_SEPARATOR, array(
		realpath(APPLICATION_PATH . '/../../library/Classes'),
		realpath(APPLICATION_PATH . '/../../library'),
		realpath(APPLICATION_PATH . '/models'),
		realpath(APPLICATION_PATH . '/models/plugins'),
		get_include_path()
	)));

	spl_autoload_register('_loader');

    /*************************   START CACHING   ****************************************/
    
	$debug_header = APPLICATION_ENV == 'production' ? false : true; //whether info is showed in head of document
	//defaults to cache on production, not on dev
	$caching = APPLICATION_ENV == 'production' ? true : false;
	$caching = isset($_REQUEST['cache']) && $_REQUEST['cache'] == 1 ? true : $caching; //force cache on
	$caching = isset($_REQUEST['nocache']) && $_REQUEST['nocache'] == 1 ? false : $caching; //force cache off

	//the cache is based on the URL, if this URL is cached everything else skipped
	include_once 'Zend/Cache.php';
	
	$frontendOptions = array(
	   'lifetime' => 86400,  //seconds of life, null = forever
	   'automatic_serialization' => false,
	   'caching' => $caching, // when set back to true will use the cached version
	   'debug_header' => $debug_header,
	   'automatic_cleaning_factor' => 0,
	   'default_options' => array (
	          'cache_with_get_variables'    => true, //will use the cache only when there is no param variables
	          'cache_with_post_variables' => true,
	          'cache_with_cookie_variables'    => true, //since there was a cookie this was preventing the cache
	          'cache_with_session_vairables' => true,
	          'cache_with_files_variables' => true,
	          'make_id_with_get_variables'    => false,
	          'make_id_with_cookie_variables' => false,
	          'make_id_with_post_variables' => false,
	          'make_id_with_session_variables' => false,
	          'make_id_with_files_variables' => false
	      )
	);

	$backendOptions = array(
	    'cache_dir' => DATA_PATH.'/cache/'
	);

	$cache = Zend_Cache::factory('Page', 'File', $frontendOptions, $backendOptions);

	$cache->start(); 
	
	//code stops here if cache for this URL was found, otherwise run Zend Application

    /*************************  DEFINE CONSTANTS ****************************************/
    
    $basePath = APPLICATION_ENV !== 'production' ? substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], 'public') + 6) : "/";
	$requestPath = APPLICATION_ENV !== 'production' 
		? Uri::getRequestUri(substr($_SERVER['REQUEST_URI'],strpos($_SERVER['REQUEST_URI'], 'public') + 6))
		: Uri::getRequestUri($_SERVER['REQUEST_URI']);

	//pretty version of current request i.e. database/mysql/table-create
	// used with crumb system
	define('REQUEST_PATH',$requestPath);
	
	//path to the config file
    define('CONFIG_PATH',realpath(APPLICATION_PATH . '/../../library/Config'));

    defined('BASE_URL')
			|| define('BASE_URL', 'http://' . $_SERVER['SERVER_NAME'] . $basePath . (substr($basePath, -1) == "/" ? "" : "/"));
	
	/*************************  RUN APP  ****************************************/

	session_start(); 
	
	/** Zend_Application */
	require_once 'Zend/Application.php';

	// Create application, bootstrap, and run
	$application = new Zend_Application(
		APPLICATION_ENV,
		CONFIG_PATH . '/global_zend.ini' 
	);
	$application->bootstrap()
				->run();


	function _loader($class) 
	{
		
		$aIncludePaths = explode(PATH_SEPARATOR,get_include_path());

		foreach($aIncludePaths as $path){
			if(file_exists($path.DIRECTORY_SEPARATOR.$class.'.php')){
				include_once($class.'.php');
				return;
			}
		}
		
		//need to handle errors some how 

		//new FileException('Unable to locate php include: '.$class.'.php');
		//$this->_redirect('error'); // this not working, test more with faulty namespaces, broken when obj instantiated in bootstrap

	}