<?php
/**
 * ResultSet for Models built around CouchDb.
 *
 * @see http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_Model_Resultset.html
 * @see https://github.com/phalcon/cphalcon/blob/master/ext/mvc/model/resultset.c
 *
 * @package    PhouchDb\Mvc
 * @subpackage Model
 * @author     Mike Holloway <me@mikeholloway.co.uk>
 */

namespace PhouchDb\Mvc\Model;

class Resultset extends \Phalcon\Mvc\Model\Resultset
{
    /**
     * Init result set.
     *
     * @param array $items Data to populate the set with.
     */
    public function __construct(array $items = array())
    {
        if (! empty($items)) {
            $this->fromArray($items);
        }
    }

    /**
     * Populate the resultset from an array of results.
     *
     * @param array $items A collection of results to assign to the set.
     *
     * @return self
     */
    public function fromArray(array $items)
    {
        $this->_rows = $items;
        $this->rewind();
        return $this;
    }

    /**
     * Return the collection as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $items = array();
        $this->rewind();

        while ($this->valid() && $item = $this->current()) {
            $items[] = $item->toArray();
            $this->next();
        }

        return $items;
    }

    /**
     * Retrieve the item at the current pos.
     *
     * @return \Phalcon\Mvc\Collection
     */
    public function current()
    {
        return $this->_rows[$this->key()];
    }

    /**
     * Check if the current index exists.
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->offsetExists($this->key());
    }

    /**
     * Serialize the object.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * Convert a serialized form into a ResultSet.
     *
     * @param string $serialized The serialized form of a ResultSet.
     *
     * @return self
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        return new static($data);
    }
}
