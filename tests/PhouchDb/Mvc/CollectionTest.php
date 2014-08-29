<?php
/**
 * Bootstrap for the test environment.
 *
 * @package    PhouchDb\Tests
 * @subpackage Mvc
 * @author     Mike Holloway <me@mikeholloway.co.uk>
 */

namespace PhouchDb\Tests\Mvc;

use PhouchDb\Tests\Robot;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Retrieve an individual robot.
     *
     * @return void
     */
    public function testFindById()
    {
        $robot = Robot::findById('508735d32d42b8c3d15ec4e3');
        $this->assertInstanceOf('PhouchDb\Tests\Robot', $robot);
    }

    /**
     * Find the first document from the collection.
     *
     * @return void
     */
    public function testFindFirst()
    {
        $robot = Robot::findFirst();
        $this->assertInstanceOf('PhouchDb\Tests\Robot', $robot);
    }

    /**
     * Retrieve a collection of robots.
     *
     * @return void
     */
    public function testFindAll()
    {
        $robots = Robot::find();
        $this->assertInstanceOf('PhouchDb\Mvc\Model\Resultset', $robots);
        $this->assertCount(3, $robots);
    }

    /**
     * Find a number of robots from the collection.
     *
     * I'm using a custom view here that emits documents by robot name, and
     * using 'keys' to specify which one's I'm interested in.
     *
     * @return void
     */
    public function testFindSome()
    {
        $robots = Robot::find(array(
            'view' => '/_design/robots/_view/name/',
            'keys' => array('Astro Boy', 'Wall-E'),
        ));
        $this->assertInstanceOf('PhouchDb\Mvc\Model\Resultset', $robots);
        $this->assertCount(2, $robots);
    }
}
