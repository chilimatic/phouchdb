<?php
/**
 * A test model for interacting with storage.
 *
 * @package    PhouchDb
 * @subpackage Tests
 * @author     Mike Holloway <me@mikeholloway.co.uk>
 */

namespace PhouchDb\Tests;

class Robot extends \PhouchDb\Mvc\Collection
{
    /**
     * Retrieve the collection name.
     *
     * @return string
     */
    public function getSource()
    {
        return 'robots';
    }
}
