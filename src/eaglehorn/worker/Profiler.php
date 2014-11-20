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
 * @desc           Responsible for handling paginations
 *                 Example usage:
 *                 $p = new Profile();
 *                 $time = $p->profile("classname", "methodname", array(arg1, arg2, ...));
 *                 $p->printDetails();
 *                 You can also provide an optional number to profile, which will result
 *                 in the method getting called that many times. Details are then recoded
 *                 about the total execution time, average time, and worst single time.
 */
class Profiler
{
    /**
     * Stores details about the last profiled method
     */
    private $details;

    //var $time = microtime(true);

    public function __construct()
    {

    }

    /**
     * Runs a method with the provided arguments, and
     * returns details about how long it took. Works
     * with instance methods and static methods.
     *
     * @param string $classname
     * @param string $methodname
     * @param array  $methodargs
     * @param int    $invocations
     * @throws Exception
     * @internal param string $classname
     * @internal param string $methodname
     * @internal param array $methodargs
     * @internal param int $invocations The number of times to call the method
     * @return float average invocation duration in seconds
     */
    public function profile($classname, $methodname, $methodargs, $invocations = 1)
    {
        if (class_exists($classname) != TRUE) {
            throw new Exception("{$classname} doesn't exist");
        }

        $method = new ReflectionMethod($classname, $methodname);

        $instance = NULL;
        if (!$method->isStatic()) {
            $class = new ReflectionClass($classname);
            $instance = $class->newInstance();
        }

        $durations = array();
        for ($i = 0; $i < $invocations; $i++) {
            $start = microtime(true);
            $method->invokeArgs($instance, $methodargs);
            $durations[] = microtime(true) - $start;
        }
        $durations['worst'] = round(max($durations), 5);
        $durations['total'] = round(array_sum($durations), 5);
        $durations['average'] = round($durations['total'] / count($durations), 5);


        $this->details = array('class' => $classname,
            'method' => $methodname,
            'arguments' => $methodargs,
            'duration' => $durations,
            'invocations' => $invocations);

        return $durations['average'];
    }

    /**
     * Returns a string representing the last invoked
     * method, including any arguments
     *
     * @return string
     */
    private function invokedMethod()
    {
        return "{$this->details['class']}::{$this->details['method']}(" .
        join(", ", $this->details['arguments']) . ")";
    }

    /**
     * Prints out details about the last profiled method
     */
    public function printDetails()
    {
        $methodString = $this->invokedMethod();
        $numInvoked = $this->details['invocations'];

        if ($numInvoked == 1) {
            echo "{$methodString} took {$this->details['duration']['average']}s\n";
        } else {
            echo "{$methodString} was invoked {$numInvoked} times<br/>";
            echo "Total duration:   {$this->details['duration']['total']}s<br/>";
            echo "Average duration: {$this->details['duration']['average']}s<br/>";
            echo "Worst duration:   {$this->details['duration']['worst']}s<br/>";
        }
    }
}