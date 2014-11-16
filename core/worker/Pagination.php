<?php
namespace ajaxtown\eaglehorn_framework\core\worker;;
/**
 * EagleHorn
 *
 * An open source application development framework for PHP 5.3 or newer
 *
 * @package        EagleHorn
 * @author        Abhishek Saha <abhisheksaha11 AT gmail DOT com>
 * @license        Available under MIT licence
 * @link        http://eaglehorn.org
 * @since        Version 1.0
 * @filesource
 *
 *
 * @desc  Responsible for handling paginations
 *
 */
class Pagination
{

    var $page;
    var $adjacents;
    var $next;
    var $prev;
    var $lastpage;
    var $lpm1;
    var $totalcount;
    var $limit;
    var $targetpage;

    var $parameter;

    var $pagination = "";

    var $size = "-sm"; //-lg

    //text
    var $first_text = "<<";
    var $last_text = ">>";
    var $previous_text = "<";
    var $next_text = ">";


    function prepare($targetpage, $totalcount, $limit = 5, $para = '', $page = '')
    {

        $this->totalcount = $totalcount;
        $this->limit = $limit;
        $this->targetpage = $targetpage;


        $this->page = ((int)$para <= 0) ? 1 : $para;
        if ($page != '') $this->page = $page;
        $this->adjacents = 2;
        $this->prev = $this->page - 1;
        $this->next = $this->page + 1;

        $this->lastpage = ceil($this->totalcount / $this->limit);
        $this->lpm1 = $this->lastpage - 1;

        return $this->init($para);
    }

    function init($para)
    {


        if ($this->lastpage > 1) {
            $this->pagination .= "<ul class=\"pagination pagination$this->size\">";

            //first page
            if ($this->page == 1)
                $this->pagination .= "<li class=\"disabled\"><a href=\"#\">$this->first_text</a></li>";
            else
                $this->pagination .= "<li><a href=\"$this->targetpage/1/$para\">$this->first_text</a></li>";


            //previous button
            if ($this->page > 1)
                $this->pagination .= "<li><a href=\"$this->targetpage/$this->prev/$para\">$this->previous_text</a></li>";
            else
                $this->pagination .= "<li class=\"disabled\"><a href=\"#\">$this->previous_text</a></li>";
            //pages
            if ($this->lastpage < 7 + ($this->adjacents * 2)) { //not enough pages to bother breaking it up
                for ($counter = 1; $counter <= $this->lastpage; $counter++) {
                    if ($counter == $this->page)
                        $this->pagination .= "<li class=\"active\"><a href=\"#\">$counter</a></li>";
                    else
                        $this->pagination .= "<li><a href=\"$this->targetpage/$counter/$para\">$counter</a></li>";
                }
            } elseif ($this->lastpage > 5 + ($this->adjacents * 2)) { //enough pages to hide some
                //close to beginning; only hide later pages
                if ($this->page < 1 + ($this->adjacents * 2)) {
                    for ($counter = 1; $counter < 4 + ($this->adjacents * 2); $counter++) {
                        if ($counter == $this->page)
                            $this->pagination .= "<li class=\"active\"><a href=\"#\">$counter</a></li>";
                        else
                            $this->pagination .= "<li><a href=\"$this->targetpage/$counter/$para\">$counter</a></li>";
                    }
                    $this->pagination .= "<li><a href=\"#\">...</a></li>";
                    $this->pagination .= "<li><a href=\"$this->targetpage/$this->lpm1/$para\">$this->lpm1</a></li>";
                    $this->pagination .= "<li><a href=\"$this->targetpage/$this->lastpage/$para\">$this->lastpage</a></li>";
                } //in middle; hide some front and some back
                elseif ($this->lastpage - ($this->adjacents * 2) > $this->page && $this->page > ($this->adjacents * 2)) {
                    $this->pagination .= "<li><a href=\"$this->targetpage/1/$para\">1</a>";
                    $this->pagination .= "<li><a href=\"$this->targetpage/2/$para\">2</a>";
                    $this->pagination .= "<a href=\"#\">...</a>";
                    for ($counter = $this->page - $this->adjacents; $counter <= $this->page + $this->adjacents; $counter++) {
                        if ($counter == $this->page)
                            $this->pagination .= "<li class=\"active\"><a href=\"#\">$counter</a></li>";
                        else
                            $this->pagination .= "<li><a href=\"$this->targetpage/$counter/$para\">$counter</a></li>";
                    }
                    $this->pagination .= "<li><a href=\"#\">...</a></li>";
                    $this->pagination .= "<li><a href=\"$this->targetpage/$this->lpm1/$para\">$this->lpm1</a></li>";
                    $this->pagination .= "<li><a href=\"$this->targetpage/$this->lastpage/$para\">$this->lastpage</a></li>";
                } //close to end; only hide early pages
                else {
                    $this->pagination .= "<li><a href=\"$this->targetpage/1/$para\">1</a></li>";
                    $this->pagination .= "<li><a href=\"$this->targetpage/2/$para\">2</a></li>";
                    $this->pagination .= "<li><a href=\"#\">...</a></li>";
                    $lp = $this->lastpage - (2 + ($this->adjacents * 2));
                    for ($counter = $lp; $counter <= $this->lastpage; $counter++) {
                        if ($counter == $this->page)
                            $this->pagination .= "<li class=\"active\"><a href=\"#\">$counter</a></li>";
                        else
                            $this->pagination .= "<li><a href=\"$this->targetpage/$counter/$para\">$counter</a></li>";
                    }
                }
            }
            //next button
            if ($this->page < $counter - 1)
                $this->pagination .= "<li><a href=\"$this->targetpage/$this->next/$para\">$this->next_text</a></li>";
            else
                $this->pagination .= "<li class=\"disabled\"><a href=\"#\">$this->next_text</a></li>";

            if ($this->page == $this->lastpage)
                $this->pagination .= "<li class=\"disabled\"><a href=\"#\">$this->last_text</a></li>";
            else
                $this->pagination .= "<li><a href=\"$this->targetpage/$this->lastpage/$para\">$this->last_text</a></li>";
            $this->pagination .= "</ul>\n";
        }

        return $this->pagination;
    }

}