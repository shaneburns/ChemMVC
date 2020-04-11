<?php
namespace ChemMVC;
/**
 * Bundle class
    * Implement bundleFu
 */
class bundleConfig
{
    private $settings;
    private $factory;
    public $bundles;

    function __construct(string $docRoot = null, string $cssCache = null, string $jsCache = null)
    {
        // Init Setup
        $this->settings = array(
            'doc_root' => ($docRoot ?? $_SERVER['DOCUMENT_ROOT']. DIRECTORY_SEPARATOR),
            'css_cache_path' => ($cssCache ?? 'css/cache'),
            'js_cache_path' => ($jsCache ?? 'scripts/cache'),
        );

        $this->factory = new \DotsUnited\BundleFu\Factory($this->settings);

        // $bundle1 and $bundle2 use the same doc_root, css_cache_path and js_cache_path options
        $this->bundles = array();

    }

    public function createBundle(string $var = '')
    {
        if($var == '') return;
        $this->bundles["$var"] = $this->factory->createBundle();
    }
}
