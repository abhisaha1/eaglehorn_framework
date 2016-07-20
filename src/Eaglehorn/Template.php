<?php
namespace Eaglehorn;

/**
 * EagleHorn
 * An open source application development framework for PHP 5.4 or newer
 *
 * @package        EagleHorn
 * @author         Abhishek Saha <abhisheksaha11 AT gmail DOT com>
 * @license        Available under MIT licence
 * @link           http://Eaglehorn.org
 * @since          Version 1.0
 * @filesource
 * @desc           Responsible for rendering and parsing Templates
 */

class Template
{
    private $template_markup;
    private $injections = array();
    private $base;
    public $twig;

    function __construct(Base $base)
    {
        $this->base = $base;
        foreach ($base->attr as $key => $value) {
            $this->$key = $value;
        }
        $templates = configItem('site')['appdir'].'templates';
        $loader = new \Twig_Loader_Filesystem($templates);
        $this->twig = new \Twig_Environment($loader,array(
            //'cache' => configItem('cache')['dir'],
        ));
        $this->twigFunctions();
    }

    function twigFunctions()
    {
        $function = new \Twig_SimpleFunction('configItem', 'configItem');
        $this->twig->addFunction($function);

        /**
         * The below code adds a way to include files inside template.
         * Yes, this is awesome :)
         * Usage inside template:
         *
         * {{inc("header.php")}}
         */
        $function = new \Twig_SimpleFunction('inc', function($file){
            $file = configItem("site")["viewdir"] . $file;
            echo call_user_func_array(array($this->base,"getFileOutput"),array($file));
        });
        $this->twig->addFunction($function);
    }

    /**
     * Store the CSS Link
     *
     * @param $file
     */
    public function injectCSS($file)
    {
        $file = configItem('site')['url'].$file;
        $this->injections['css'][] = "<link href='$file' rel = 'stylesheet'>";
    }

    /**
     * Store the JS File
     *
     * @param $file
     */
    public function injectJS($file)
    {
        $file = configItem('site')['url'].$file;
        $this->injections['js'][] = "<script src='$file' type = 'text/javascript'></script>";
    }

    /**
     * Store meta info
     *
     * @param $name
     * @param $content
     */
    public function meta($name, $content)
    {
        $this->injections['meta'][] = array($name, $content);
    }

    /**
     * Render the template
     */
    public function render()
    {
        $template_name = $this->base->load->template[0];

        $template_file = configItem('site')['templatedir'] . $template_name . '.tpl';

        if (file_exists($template_file)) {

            $template = $this->twig->loadTemplate($template_name . '.tpl');

            $template_data = array();

            if (isset($this->base->load->template[1]) && is_array($this->base->load->template[1]))
            {
                $template_data = $this->base->load->template[1];

                foreach($template_data as $key => $value)
                {
                    $file = configItem('site')['viewdir'] . $value;

                    if (file_exists($file) && is_file($file))
                    {
                        $template_data[$key] = $this->base->getFileOutput($file);
                        $this->base->logger->info("View parsed - $file");

                    }
                }
            }
            $this->template_markup = $template->render($template_data);

            if(sizeof($this->injections) > 0)
            {
                $this->_applyInjections();
            }
            echo $this->template_markup;

        } else {
            //log error
        }
    }

    /**
     * Insert the stored CSS links, JS, files and Meta into the HEAD
     */
    private function _applyInjections()
    {
        foreach ($this->injections as $head)
        {
            $tags = $this->_getInjectionString($head);

        }

        $this->template_markup = str_replace('</head>', $tags, $this->template_markup);
    }

    /**
     * Combine all CSS, JS, META lines in one string
     *
     * @param $content
     * @return string
     */
    private function _getInjectionString($content)
    {
        $tags = '';
        foreach ($content as $value) {

            $tags .= $value;
        }
        return $tags;
    }
}
