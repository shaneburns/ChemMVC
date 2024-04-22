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
    private $makeup;
    private $view;
    private $logic;
    public $title;
    public $description;
    public $displayed = false;
    public $styles = array();
    public $scripts = array();

    public function __construct($controller = '', $action = ''){
        if($controller !== '' && $action !== '') {
            $this->controller = $controller;
            $this->action = $action;

            $this->makeup = $this->getFileContents();
            if($this->makeup == null) return new result(null, 404);

            $this->uncouple();
        }else{
            $error = 'Error: a sequence makeup was not set upon construct';
            throw new Exception($error);
        }
    }

    function getPath(){
        return ROOT.ds."views".ds.$this->controller.ds.$this->action.".php";
    }

    function getFileContents($recursion = false){
        $fileContent = null;
        try{
            $fileContent = html_entity_decode(file_get_contents($this->getPath()));
        }catch(\Exception $e){
            if($recursion) return null;
            $caseType = preg_match('~^\p{Lu}~u', $this->controller) ? 'upper' : 'lower';
            if($caseType == 'upper') $this->controller = \lcfirst($this->controller);
            else $fileContent = $this->controller = \ucfirst($this->controller);
            $fileContent = $this->getFileContents(true);
        }
        return $fileContent;
    }

    public function uncouple(){
        // Sequence Breakdown
        if($this->makeup != null && strpos($this->makeup, '<?php') === 0){
            $sb = explode('?>', $this->makeup);
        }
        if(isset($sb) && is_array($sb)){// We got stuff to parse out
            if(count($sb) == 2){// Almost guarenteed php code block && html view
                $this->view = $sb[1];
                if(strpos($sb[0], '<?php') !== false){
                    $this->logic = explode('<?php', $sb[0])[1];
                }
                return;
            }else{
                $this->view = $this->makeup;
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
    public function hasLogicalView(){
        return !empty($this->view) && strpos($this->view, '<?php') !== false;
    }
    public function evalLogic(){// Evaluate php logic if not null
        if($this->logic !== null) eval($this->logic);
        else throw new Exception("No logic to evaluate.");

    }
    public function displayView(){// Echo view if not null
        if($this->view !== null) echo $this->view;
        else throw new Exception("No view to display.");
    }
    public function renderStyleTags(){
        foreach ($this->styles as $url) echo "\r\n".'<link rel="stylesheet" href="'.$url.'">'."\r\n";
    }
    public function renderScriptTags(){
        foreach ($this->scripts as $url) echo "\r\n".'<script src="'.$url.'"></script>'."\r\n";
    }
    public function execute(){
        if($this->hasLogic() && !$this->hasLogicalView()){
            $this->evalLogic();
            if($this->hasView() && !$this->displayed) $this->displayView();
        }
        else if($this->hasLogicalView()) include($this->getPath());
        else if($this->hasView() && !$this->displayed) $this->displayView();
    }
}
