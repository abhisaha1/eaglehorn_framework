<?php
namespace eaglehorn\core\controller;

/**
 * EagleHorn
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package        EagleHorn
 * @author        Abhishek Saha <abhisheksaha11 AT gmail DOT com>
 * @license        Available under MIT licence
 * @link        http://eaglehorn.org
 * @since        Version 1.0
 * @filesource
 *
 *
 * @desc  Responsible for rendering and parsing Templates
 *
 */

class Template
{

    private $template_markup;
    private $injections = array();
    private $base;

    function __construct(Base $base)
    {
        $this->base = $base;
        foreach ($base->attr as $key => $value) {
            $this->$key = $value;
        }

    }

    /**
     * Store the CSS Link
     * @param $file
     */
    public function injectCSS($file)
    {
        $this->injections['css'][] = "<link href='$file' rel = 'stylesheet'>";
    }

    /**
     * Store the JS File
     * @param $file
     */
    public function injectJS($file)
    {
        $this->injections['js'][] = $file;
    }

    /**
     * Store meta info
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

            $this->template_markup = $this->base->getFileOutput($template_file);

            //apply injections
            $this->_applyInjections();

            //apply template data
            $this->_applyTemplateData();

            //display
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

        foreach ($this->injections as $head) {

            $tags = $this->_getInjectionString($head);

        }

        $this->template_markup = str_replace('</head>', $tags, $this->template_markup);

    }

    /**
     * Combine all CSS, JS, META lines in one string
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

    /**
     * Apply template data
     */
    private function _applyTemplateData()
    {
        $template_data = array();

        if (isset($this->base->load->template[1]) && is_array($this->base->load->template[1])) {
            $template_data = $this->base->load->template[1];
        }

        //get all variables from template
        preg_match_all('/{(.*?)}/', $this->template_markup, $matches);

        //replace vars with values and apply functions
        $this->_replaceVars($matches[1], $template_data);


    }

    /**
     * Replace {VARIABLES} with values and apply filter functions
     * @param $vars
     * @param $values
     */
    private function _replaceVars($vars, $values)
    {
        foreach ($vars as $key => $templateVars) {

            /* check if we need to apply functions to this variable */
            if (strpos($templateVars, '|') !== false) {

                $filters = explode('|', $templateVars);

                $templateVar = $filters[0];

                /* The first element in the array is not a function. So remove it. */
                unset($filters[0]);

                if (is_array($filters)) {

                    $filteredValue = $values[$templateVar];

                    foreach ($filters as $filter) {

                        if (function_exists($filter) && is_callable($filter)) {
                            $filteredValue = $filter($filteredValue);
                        }

                    }

                    $this->template_markup = str_replace('{' . $templateVars . '}', $filteredValue, $this->template_markup);
                }

            } else {
                //check if this value is a valid view file
                $file = configItem('site')['viewdir'] . $values[$templateVars];

                if (file_exists($file) && is_file($file)) {

                    $values[$templateVars] = $this->base->getFileOutput($file);
                    $this->base->logger->info("View parsed - $file");

                }
                $this->template_markup = str_replace('{' . $templateVars . '}', $values[$templateVars], $this->template_markup);
            }
        }

    }


}