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
 * @copyright 2014-2019 Cappasity Inc.
 * @license   http://cappasity.com/eula_modules/  Cappasity EULA for Modules
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
     * @todo change domain
     * Cappasity API server
     * @var string
     */
    const API_BASE = 'https://api.cappasity.com/api/';

    /**
     * Reporting URL to debug errors.
     * @var string
     */
    const SENTRY_DSN = 'https://4a87ef881e8140d7927f6a3d05796bf5:669b86c7469b47a69cc97952da2e3a5b@sentry.io/1201230';

    /**
     * @var
     */
    protected $client;

    /**
     * @var
     */
    public $sentry;

    /**
     * Cappasity API Client.
     * @param string $version - module version.
     */
    public function __construct($version)
    {
        $this->client = new \GuzzleHttp\Client(array('base_url' => self::API_BASE));
        $cappasityClient = $this;

        /**
         * https://docs.sentry.io/clients/php/config/
         * @var Raven_Client
         */
        $this->sentry = new Raven_Client(self::SENTRY_DSN, array(
            'tags' => array(
                'php_version' => phpversion(),
            ),
            'release' => $version,
            'transport' => function ($client, $data) use ($cappasityClient) {
                $options = array(
                    'headers' => array(
                        'Content-Encoding' => 'gzip',
                        'Content-Type'     => 'application/octet-stream',
                        'User-Agent'       => $client->getUserAgent(),
                        'X-Sentry-Auth'    => $client->getAuthHeader(),
                    ),
                    'body' => gzencode(Tools::jsonEncode($data)),
                );

                $cappasityClient->request(
                    self::METHOD_POST,
                    $client->getServerEndpoint(),
                    $options
                );
            }
        ));
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
     * @param  string  $sku
     * @return boolean
     */
    public function isValidSKU($sku)
    {
        return empty($sku) === false && preg_match('/^[0-9A-Za-z_\\-.]{1,50}$/', $sku) === 1;
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
