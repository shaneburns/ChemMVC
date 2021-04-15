<?php
namespace ChemMVC;
/**
 * Bundle class
    * Implement bundleFu
 */
class bundleConfig
{
    private $settings;
    private $filters;
    private $factory;
    public $bundles;

    function __construct(string $docRoot = null, string $cssCache = null, string $jsCache = null)
    {
        // Init Setup
        $this->settings = array(
            'doc_root' => ($docRoot ?? $_SERVER['DOCUMENT_ROOT']. DIRECTORY_SEPARATOR),
            'css_cache_path' => ($cssCache ?? 'css/cache'),
            'js_cache_path' => ($jsCache ?? 'scripts/cache')
        );

        $this->filters = array(
            'js_closure_compiler' => new \DotsUnited\BundleFu\Filter\ClosureCompilerServiceFilter()
        );
        
        $this->factory = new \DotsUnited\BundleFu\Factory($this->settings, $this->filters);

        $this->bundles = array();

    }

    public function createBundle(string $var = '')
    {
        if($var == '') return;
        $this->bundles["$var"] = $this->factory->createBundle(array('js_filter' => 'js_closure_compiler'));
    }
}