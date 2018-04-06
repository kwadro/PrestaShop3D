<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author    Cappasity Inc <info@cappasity.com>
 * @copyright 2014-2018 Cappasity Inc.
 * @license   http://cappasity.us/eula_modules/  Cappasity EULA for Modules
 */

/**
 *
 */
class CappasityClient
{
    /**
     *
     */
    const METHOD_POST = 'POST';

    /**
     *
     */
    const METHOD_GET = 'GET';

    /**
     * @var
     */
    protected $client;

    /**
     *
     */
    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client(array('base_url' => 'https://api.cappasity.com/api/'));
    }

    /**
     * @param $method
     * @param $uri
     * @param $options
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function request($method, $uri, array $options = array())
    {
        return $this->client->send(
            $this->client->createRequest($method, $uri, $options)
        );
    }

    /**
     * @param string $uri
     * @param array $query
     * @param string $token
     * @return array
     */
    public function get($uri, array $query = array(), $token = null)
    {
        $options = $this->populateAuth($token);
        $options['query'] = $query;

        $response = $this->request(self::METHOD_GET, $uri, $options);

        return $this->decodeResponse($response->getBody());
    }

    /**
     * @param string $uri
     * @param array $data
     * @param string $token
     * @return array
     */
    public function post($uri, $data = array(), $token = null)
    {
        $options = $this->populateAuth($token);
        $options['json'] = $data;

        $response = $this->request(self::METHOD_POST, $uri, $options);

        return $this->decodeResponse($response->getBody());
    }

    /**
     * @param string $token
     * @param array $options
     * @return array
     */
    protected function populateAuth($token, array $options = array())
    {
        if ($token !== null) {
            $options['headers']['Authorization'] = "Bearer {$token}";
        }

        return $options;
    }

    /**
     * @param $response
     * @return array
     */
    protected function decodeResponse($response)
    {
        return Tools::jsonDecode($response, true);
    }
}
