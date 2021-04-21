<?php
namespace ChemMVC;

use const PROJECT_NAMESPACE;
use Doctrine\DBAL;
use Doctrine\Common;
use Monolog\Logger;
use TheCodingMachine\TDBM;
/**
* Chemistry
 */
class chemistry
{
    public startup $config;
    public routingCatalyst $catalyst;
    public TDBM\TDBMService $tdbmService;
    private controller $controller;
    private result $result;

    function __construct(startup $config, bool $loadOnInit = true){
        \set_error_handler('exceptionErrorHandler');

        // Store config locally
        $this->config = $config;
        
        try{
            $this->DefineEnvConstants($config->settings);

            if(is_null(PROJECT_NAMESPACE) || is_null(ENV_DETAILS_PATH)){
                $this->result = new result("FATAL CHEMISTRY APPLICATION ERROR :: - \nThe expected PROJECT_NAMESPACE or ENV_DETAILS_PATH variables were not located in the defined constants scope.");
                $this->result->display();
                die();
            }
            // Parse .env file for
            $this->putEnvVars(parse_ini_file(ENV_DETAILS_PATH));

            // Require SSL if denoted
            if(getenv('requireSSL') && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off")) $this->sslRedirect();

            // Start TDBM services
            if(getenv('myDB') != null) $this->startTDBMService();

            // Create a routing catalyst
            $this->catalyst = new routingCatalyst();
            if($loadOnInit) {
                $this->instantiateController($loadOnInit);
                
                // Get the result
                if($this->controller != null) $this->result = new result($this->controller->getResult());
            }
        }catch(\Exception $e){
            $this->result = new result($e->message, 500);
        }
        
        if($loadOnInit) $this->result->display();

        \restore_error_handler();
    }

    public function putEnvVars(array $vars) : boid
    {
        foreach($vars as $key => $val) putenv($key."=".$val);
    }
    
    public function DefineEnvConstants(array $constants) : void
    {
        foreach($constants as $var => $val ){
            if(!defined($var)){
                define($var, $val);
            }else{
                $this->result = new result(new \Exception("Chemistry Error: Constant '$var' is already defined and cannot be set again."), 500);
                $this->result->display();
            }
        }
    }

    private function sslRedirect() : void
    {
        // Permanently redirect please
        $this->result = new result(null, 301, ['Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']]);
        $this->result->display();
        exit;
    }
    
    private function startTDBMService() : void
    {
        $DBALconfig = new DBAL\Configuration();

        $connectionParams = array(
            'user' => getenv('username'),
            'password' => getenv('password'),
            'host' => getenv('servername'),
            'driver' => getenv('driver'),
            'dbname' => getenv('myDB')
        );

        $dbConnection = DBAL\DriverManager::getConnection($connectionParams, $DBALconfig);

        // The bean and DAO namespace that will be used to generate the beans and DAOs. These namespaces must be autoloadable from Composer.
        $baseSpace = (CORE_NAMESPACE != null) ? CORE_NAMESPACE : PROJECT_NAMESPACE;
        $beanNamespace = $baseSpace .'\\Beans';
        $daoNamespace = $baseSpace .'\\Daos';

        $cache = new Common\Cache\ArrayCache();

        $logger = new Logger('cantina-app'); // $logger must be a PSR-3 compliant logger (optional).

        // Let's build the configuration object
        $configuration = new TDBM\Configuration(
            $beanNamespace,
            $daoNamespace,
            $dbConnection,
            null,    // An optional "naming strategy" if you want to change the way beans/DAOs are named
            $cache,
            null,    // An optional SchemaAnalyzer instance
            $logger, // An optional logger
            []       // A list of generator listeners to hook into code generation
        );

        // The TDBMService is created using the configuration object.
        $this->tdbmService = new TDBM\TDBMService($configuration);
    }

    public function instantiateController(bool $invokeAction = false){
        // form the class path
        $controllerName = $this->catalyst->getController();
        $path = $this->catalyst->getControllerPath();
        if (strpos($path, 'favicon') !== false || is_null($path)) return false;
        if(class_exists($path)){
            try{// pull the trigger
                // Instantiate the class and 
                $this->controller = new $path($this, $invokeAction);
            }catch(\Exception $e){// that's a dud
                // log path and error and everything
                // 404 response
                $this->result = new result($e, 404);
            }
        }else if(!preg_match("/^[A-Z]/", $this->catalyst->getController())){
            die();
            $this->catalyst->setController(ucfirst($this->catalyst->getController()));
            $this->instantiateController(true);
        }
            
    }

    function exceptionErrorHandler($errno, $errstr, $errfile, $errline) : void
    {
        // error was suppressed with the @-operator
        if (0 === error_reporting()) {
            return false;
        }
        
        $this->result = new result(new \ErrorException($errstr, 0, $errno, $errfile, $errline));
        $this->result->display();
    }
}