<?php
/**
 * ResultSet for Models built around CouchDb.
 *
 * @package    PhouchDb\Mvc
 * @subpackage Model
 * @author     Mike Holloway <me@mikeholloway.co.uk>
 */

namespace PhouchDb\Mvc\Model;

class ResultSet extends \Phalcon\Mvc\Model\Resultset
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
        return $this;
    }

    /**
     * Return the collection as an array.
     *
     * @see https://github.com/phalcon/cphalcon/issues/552#issuecomment-16791464
     *
     * @return array
     */
    public function toArray()
    {
        $items = array();
        $this->rewind();

        foreach ($this->valid() as $item) {
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Retrieve a value from the stack with the provided offset.
     *
     * @param mixed $offset Key to seek in the stack.
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->_rows[$offset];
    }

    /**
     * Retrieve the first value on the stack.
     *
     * @return mixed
     */
    public function getFirst()
    {
        return reset($this->_rows);
    }

    /**
     * Retrieve the last value on the stack.
     *
     * @return mixed
     */
    public function getLast()
    {
        return end($this->_rows);
    }

    /**
     * Generator function to loop through items in set.
     *
     * Not entirely sure what this *should* be doing, but it's part of the
     * interface and the docs are useless.
     *
     * "Check whether the internal resource has rows to fetch"
     *
     * @todo Fix this :p
     *
     * @return void
     */
    public function valid()
    {
        foreach ($this->_rows as $item) {
            yield $item->toArray();
        }
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
