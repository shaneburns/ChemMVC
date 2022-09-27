<?php
namespace ChemMVC;

use const PROJECT_NAMESPACE;
use ChemCommon\config;
use ChemCommon\startup;
use ChemCommon\result;
/**
* Chemistry
 */
class ChemMVC
{
    public config $config;
    public startup $chem;
    public routingCatalyst $catalyst;
    private controller $controller;
    private result $result;

    function __construct(config $config, bool $loadOnInit = true){
        \set_error_handler(function($errno, $errstr, $errfile, $errline)
        {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }
            
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        // make some magic with this configuration
        $this->chem = new startup($config);
        
        try{
            
            // Create a routing catalyst
            $this->catalyst = new routingCatalyst();
            if($loadOnInit) {
                $this->instantiateController($loadOnInit);
                
                // Get the result
                if(!empty($this->controller)) $this->result = new result($this->controller->getResult());
            }
        }catch(\Exception $e){
            $this->result = new result($e, 500);
        }
        
        if($loadOnInit) $this->result->display();

        \restore_error_handler();
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
            //die();
            $this->catalyst->setController(ucfirst($this->catalyst->getController()));
            $this->instantiateController(true);
        }
            
    }
}