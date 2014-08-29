<?php
/**
 * Bootstrap for the test environment.
 *
 * @package    PhouchDb
 * @subpackage Tests
 * @author     Mike Holloway <me@mikeholloway.co.uk>
 */

require_once __DIR__ . '/../vendor/autoload.php';

$envConfig = __DIR__ . '/configs/testing.config.php';
$config = new \Phalcon\Config(include __DIR__ . '/configs/config.php');

// merge local config, if it exists
if (file_exists($envConfig)) {
    $config->merge(new \Phalcon\Config(include $envConfig));
}
unset($envConfig);

$di = new \Phalcon\DI();

// store the config so it doesn't have to be parsed again
$di->set('config', $config);

$di->set('collectionManager', function () {
    return new \Phalcon\Mvc\Collection\Manager();
});

$di->set('couchdb', function () use ($config) {
    $client = \Phalcon\Http\Client\Request::getProvider();
    $client->setBaseUri($config->couchdb->baseUri);
    $client->header->set('Content-Type', 'application/json');

    return $client;
});
