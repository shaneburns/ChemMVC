<?php
namespace ChemMVC;
use const PROJECT_NAMESPACE\config;
class sequence{
    /*
        Desc: Accept a string($chems) to parse for php and view content.  These
            properties can then be evaluated(php code) or echoed(html views) per
            method calls.
        Caveat: This current implementation of the Bond class will only parse
            for one block of php code. Thus a bond can only have two components,
            in this current state.
    */
    public $bundleConfig; // Dependency ref
    private $makeup;
    private $view;
    private $logic;
    public $renderScripts = array();

    public function __construct($makeup = '', bundleConfig $bundle = null){
        if($makeup !== '') {
            $this->makeup = $makeup;
            $this->uncouple();
            if($bundle != null) $this->bundleConfig = $bundle;
            return;
        }else{
            $error = 'Error: a sequence makeup was not set upon construct';
            throw new Exception($error);
        }
    }
    public function uncouple(){
        // Sequence Breakdown
        $sb = explode('?>', $this->makeup);

        if(is_array($sb)){// We got stuff to parse out

            if(count($sb) == 2){// Almost guarenteed php code block && html view
                $this->view = $sb[1];
                if(strpos($sb[0], '<?php') !== false){
                    $this->logic = explode('<?php', $sb[0])[1];
                }
                return;
            }

            if(count($sb) == 1){// Should be html only, but could be php only(check for php first, if php exists run and return - else echo out html)
                if(strpos($sb[0], '<?php') !== false){
                    $this->logic = explode('<?php', $sb[0])[1];
                    return;
                }
                $this->view = $sb[0];
            }
        }else{// just html -> we can eval it just the same as php
            $this->view = $this->makeup;
        }
    }
    public function hasLogic(){
        return !empty($this->logic);
    }
    public function hasView(){
        return !empty($this->view);
    }
    public function evalLogic(){// Evaluate php logic if not null
        if($this->logic !== null) eval($this->logic);
        else throw new Exception("No logic to evaluate.");

    }
    public function displayView(){// Echo view if not null
        if($this->view !== null) echo $this->view;
        else throw new Exception("No view to display.");
    }
}
