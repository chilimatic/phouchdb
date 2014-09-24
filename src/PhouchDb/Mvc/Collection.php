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
        $client = $self->getConnection();

        $resources = array();
        $method = 'get';
        $viewKeys = null;

        $filterKeys = [
            // couchdb reserved keys
            // http://docs.couchdb.org/en/latest/api/database/bulk-api.html
            'conflicts' => false,
            'descending' => true,
            'endkey' => null,
            'end_key' => null,
            'endkey_docid' => null,
            'end_key_doc_id' => null,
            'include_docs' => true,
            'inclusive_end' => true,
            'key' => null,
            'limit' => null,
            'skip' => null,
            'stale' => null,
            'startkey' => null,
            'start_key' => null,
            'startkey_docid' => null,
            'start_key_doc_id' => null,
            'update_seq' => false,

            // request body keys
            'keys' => null,

            // my keys
            'includeSpecialComponents' => false,
            'view' => null,
        ];

        $uri = '/' . $self->getSource();

        if (null === $parameters) {
            $parameters = array();
        }

        // include the filter keys in our params
        $parameters = array_filter(array_merge($filterKeys, $parameters));

        if (isset($parameters['keys'])) {
            $method = 'post';
            $viewKeys = array_values($parameters['keys']);
            unset($parameters['keys']);
        }

        if (! isset($parameters['view'])) {
            $parameters['view'] = $config->couchdb->defaultView;
        }

        $uri .= $parameters['view'];

        // remove the view param
        unset($parameters['view']);

        // json encode any non-filter params
        foreach ($parameters as $key => &$param) {

            if ('keys' === $key) {
                continue;
            }

            if ('true' === strtolower($param) || 'false' === strtolower($param)) {
                $param = filter_var($param, FILTER_VALIDATE_BOOLEAN);
            }

            if (is_bool($param)) {
                $param = true === $param ? 'true' : 'false';
            } elseif (!is_numeric($param)) {
                $param = json_encode($param);
            }
        }

        if (null !== $viewKeys) {
            /**
             * filter by keys.
             *
             * you'll need to manually sort these results at the time of writing:
             * http://stackoverflow.com/questions/2817703/sorting-couchdb-views-by-value
             */
            $uri .= '?' . http_build_query($parameters);
            $parameters = json_encode(array('keys' => $viewKeys));
            $method = 'post';
        }

        // call the couchdb instance
        $response = $client
            ->{$method}($uri, $parameters)
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
                if (property_exists($raw, 'error')) {
                    throw new \Exception($raw->key . ': ' . $raw->error);
                }

                if (0 === strncmp($raw->id, '_', 1)
                    && (
                        false === isset($parameters['includeSpecialComponents'])
                        || false === $parameters['includeSpecialComponents']
                    )
                ) {
                    // special component
                    continue;
                }

                if (property_exists($raw, 'doc')) {
                    $item = (array) $raw->doc;

                } elseif (property_exists($raw, 'value')) {
                    $item = (array) $raw->value;
                }

                $resources[] = self::cloneResult(new static(), $item);
                unset($item);
            }

        } else {
            // single resource
            $resources = array(self::cloneResult(new static(), (array) $parsedResponse));
        }

        return new \PhouchDb\Mvc\Model\ResultSet($resources);
    }


    /**
     * Use the couchdb service.
     *
     * @return void
     */
    public function initialize()
    {
        $this->setConnectionService('couchdb');
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
