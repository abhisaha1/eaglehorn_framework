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
 * @desc           Responsible for rendering views
 */
class View
{
    var $base;

    /**
     * Holds the data which gets passed to the view
     *
     * @var array
     */
    private $_view_data = array();

    function __construct(Base $base)
    {
        $this->base = $base;
        foreach ($this->base->attr as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * While _loading views, every view and its data gets stored in viewset.
     * This method loops through the viewset and checks if the view exist. If so, then
     * include the view and convert the data(array),if exists, into independent variables.
     * As views are different from templates, special variables wrapped with {} cannot be used.
     * Instead, the data(array) which is passed to the view can be echoed directly using
     * the key.
     * For eg,
     * $data = array('title','My first page');
     * $this->load->view('myview.php',$data);
     * $this->render();
     * In myview.php, you can directly echo $title as it automatically gets converted into a variable.
     */

    public function render()
    {
        foreach ($this->base->load->viewset as $key => $view) {

            $viewdir = configItem('site')['viewdir'];
            if (file_exists($viewdir . $view[0])) {

                if (isset($view[1]) && is_array($view[1])) {
                    $this->_view_data = $view[1];
                    extract($view[1]);
                }

                include_once $viewdir . $view[0];
                $this->base->logger->info("View loaded - $view[0]", $this->_view_data);
            } else {
                $this->base->logger->error("404 View - $view[0]");
            }
        }
    }

    static function show($view,$parameters = array())
    {
        extract($parameters);
        $viewdir = configItem('site')['viewdir'];
        include_once $viewdir . $view;

    }

}