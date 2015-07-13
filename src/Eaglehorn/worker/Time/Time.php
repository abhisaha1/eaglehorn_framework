<?php
namespace Eaglehorn\worker;

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
 * @desc           Responsible for handling x time ago and time difference
 */
class Time
{


    /**
     * Returns time difference in past tense.
     * Usage:
     * echo $this->time->timeAgo('2013-01-15 06:36:42');
     *
     * @param string | date $date
     * @return string
     */
    function timeAgo($date)
    {

        if (empty($date)) {

            return "No date provided";
        }

        $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");

        $lengths = array("60", "60", "24", "7", "4.35", "12", "10");

        $now = time();

        $unix_date = strtotime($date);

        // check validity of date

        if (empty($unix_date)) {

            return "Bad date";
        }

        // is it future date or past date

        if ($now > $unix_date) {

            $difference = $now - $unix_date;

            $tense = "ago";
        } else {

            $difference = $unix_date - $now;
            $tense = "from now";
        }

        for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {

            $difference /= $lengths[$j];
        }

        $difference = round($difference);

        if ($difference != 1) {

            $periods[$j] .= "s";
        }

        return "$difference $periods[$j] {$tense}";
    }

    /**
     * Time Difference
     * Usage: $this->time->dateDiff('2012-01-19 08:30:41','2012-01-19 06:36:42')
     *
     * @param type $d1
     * @param type $d2
     * @return string
     */
    function dateDiff($d1, $d2)
    {
        $date1 = strtotime($d1);
        $date2 = strtotime($d2);
        $seconds = $date1 - $date2;
        $weeks = floor($seconds / 604800);
        $seconds -= $weeks * 604800;
        $days = floor($seconds / 86400);
        $seconds -= $days * 86400;
        $hours = floor($seconds / 3600);
        $seconds -= $hours * 3600;
        $minutes = floor($seconds / 60);
        $seconds -= $minutes * 60;
        $months = round(($date1 - $date2) / 60 / 60 / 24 / 30);
        $years = round(($date1 - $date2) / (60 * 60 * 24 * 365));

        $secs_unit = (abs($seconds) == 1) ? ' Second ' : ' Seconds ';
        $mins_unit = (abs($minutes) == 1) ? ' Minute ' : ' Minutes ';
        $hours_unit = (abs($hours) == 1) ? ' Hour ' : ' Hours ';
        $days_unit = (abs($days) == 1) ? ' Day ' : ' Days ';
        $weeks_unit = (abs($weeks) == 1) ? ' Week ' : ' Weeks ';
        $months_unit = (abs($months) == 1) ? ' Month ' : ' Months ';
        $years_unit = (abs($years) == 1) ? ' Year ' : ' Years ';

        $difference = "";

        if (!empty($years))
            $difference .= $years . $years_unit;
        if (!empty($months))
            $difference .= $months . $months_unit;
        if (!empty($weeks))
            $difference .= $weeks . $weeks_unit;
        if (!empty($days))
            $difference .= $days . $days_unit;
        if (!empty($hours))
            $difference .= $hours . $hours_unit;
        if (!empty($minutes))
            $difference .= $minutes . $mins_unit;
        if (!empty($seconds))
            $difference .= $seconds . $secs_unit;

        return $difference;
    }


}