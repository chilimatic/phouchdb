<?php
/**
 * High level abstraction for CouchDb database.
 *
 * @package    PhouchDb
 * @subpackage Mvc
 * @author     Mike Holloway <me@mikeholloway.co.uk>
 */

namespace PhouchDb\Mvc;

class Collection extends \Phalcon\Mvc\Collection
{
    /**
     * Stores the current instance of the resource.
     *
     * @var self
     */
    protected static $instance;


    /**
     * Singleton pattern for accessing the instance of the resource.
     *
     * @return self
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * Find a document by its ID.
     *
     * @param string $id ID to search for.
     *
     * @return self
     */
    public static function findById($id)
    {
        return self::findFirst(array('view' => '/' . $id));
    }

    /**
     * Find the first record that match criteria.
     *
     * @param array $parameters Criteria to match.
     *
     * @return array
     */
    public static function findFirst($parameters = null)
    {
        return self::find($parameters)->getFirst();
    }

    /**
     * Find all records that match criteria.
     *
     * @param array $parameters Criteria to match.
     *
     * @throws OutOfBoundsException If couchdb reports an error.
     *
     * @return Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function find($parameters = null)
    {
        $self = self::getInstance();
        $config = $self->getDI()->get('config');
        $client = $self->getDI()->get('couchdb');
        $resources = array();

        // couchdb reserved keys
        $filterKeys = [
            'startkey' => 1,
            'endkey' => 1,
            'skip' => 1,
            'limit' => 1,
            'descending' => 1,
        ];

        $uri = '/' . $self->getSource();

        if (null === $parameters) {
            $parameters = array();
        }

        if (! isset($parameters['view'])) {
            $parameters['view'] = $config->couchdb->defaultView;
        }

        $uri .= $parameters['view'];

        // remove the view param
        unset($parameters['view']);

        // json encode any non-filter params
        foreach ($parameters as $key => &$param) {
            if (! isset($filterKeys[$key])) {
                $param = json_encode($param);
            }
        }

        // call the couchdb instance
        $response = $client
            ->get($uri, $parameters)
            ->body;

        $parsedResponse = (array) json_decode($response);

        if (isset($parsedResponse['error'])) {
            throw new \OutOfBoundsException($parsedResponse['reason']);
        }

        if (isset($parsedResponse['rows'])
            && count($parsedResponse['rows']) > 0
        ) {
            // multiple resources
            foreach ($parsedResponse['rows'] as $raw) {
                $item = (array) $raw->value;
                $resources[] = static::factory($item['type'], $item);
            }

        } else {
            // single resource
            $resources = array(static::factory($parsedResponse['type'], $parsedResponse));
        }

        return new \PhouchDb\Mvc\Model\ResultSet($resources);
    }


    /**
     * Set the id of the document.
     *
     * @param string $id Document ID.
     *
     * @return self
     */
    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    /**
     * Creates/Updates a collection based on the values in the attributes.
     *
     * @return boolean
     */
    public function save()
    {

    }

    /**
     * Create a new resource
     *
     * @param array $data      Data to store.
     * @param array $whitelist Allowed keys to update.
     *
     * @throws Exception If there is a problem creating in storage
     *
     * @return boolean
     */
    public function create($data, $whitelist)
    {
        $client = $this->getDI()->get('couchdb');

        $uri = '/' . $this->getSource();

        if (! isset($data['creationDate'])
            || empty($data['creationDate'])
        ) {
            // ensure a date exists
            $date = new \DateTime;
            $data['creationDate'] = $date->format(\DateTime::ISO8601);
            unset($date);
        }

        $response = $client
            ->post($uri, json_encode($data), false)
            ->body;

        $parsedResponse = (array) json_decode($response);

        if (isset($parsedResponse['ok']) && $parsedResponse['ok'] === true) {
            $this->setId($parsedResponse['id']);
            return true;
        }

        return false;
    }

    /**
     * Patch (update) an existing resource.
     *
     * @param array $data      Data to store.
     * @param array $whitelist Allowed keys to update.
     *
     * @return boolean
     */
    public function update($data, $whitelist)
    {
        $client = $this->getDI()->get('couchdb');

        $uri = '/' . $this->getSource() . '/' . $this->getId();

        $data = array_merge($this->toArray(), $data);

        $response = $client
            ->put($uri, json_encode($data), false)
            ->body;

        $parsedResponse = (array) json_decode($response);

        if (isset($parsedResponse['ok']) && $parsedResponse['ok'] === true) {
            $this->setRevision($parsedResponse['rev']);
            return true;
        }

        return false;
    }
}
