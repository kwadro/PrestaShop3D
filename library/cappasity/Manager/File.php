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
 * Class CappasityManagerFile
 */
class CappasityManagerFile
{
    /**
     * @var CappasityClient
     */
    protected $client;

    /**
     * @var CappasityManagerDatabase
     */
    protected $db;

    /**
     * File constructor.
     * @param CappasityClient $client
     * @param CappasityManagerDatabase $db
     */
    public function __construct(CappasityClient $client, CappasityManagerDatabase $db)
    {
        $this->client = $client;
        $this->db = $db;
    }

    /**
     * @param string $token
     * @param string $query
     * @param int $page
     * @param int $onPage
     * @return array
     */
    public function files($token, $query = '', $page = 1, $onPage = 12)
    {
        $data = array(
            'limit' => $onPage,
            'offset' => ($page - 1) * $onPage,
            'order' => 'DESC',
            'sortBy' => 'uploadedAt',
        );

        if ($query) {
            $data['filter'] = Tools::jsonEncode(array(
                '#multi' => array(
                    'fields' => array('name', 'alias'),
                    'match' => $query,
                ),
            ));
        }

        return $this->client->get('files', $data, $token);
    }

    /**
     * @param array $queries
     * @param $owner
     * @return null
     * @throws Exception
     */
    public function search(array $queries, $owner)
    {
        foreach ($queries as $query) {
            // verify to ensure that $query
            if ($this->client->isValidSKU($query) !== true) {
                continue;
            }

            try {
                $response = $this->client->get("files/info/{$owner}/{$query}");
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $code = $e->getCode();
                if ($code === 404 || $code === 400) {
                    continue;
                }

                throw $e;
            }

            return $response['data'];
        }

        return null;
    }

    /**
     * @param integer $productId
     * @param array $params
     * @return CappasityModelFile
     */
    public function getCurrent($productId, $params)
    {
        $productId = (int)$productId;

        $result = $this->db->getCappasity(array('productId' => $productId, 'variantId' => null));

        if (count($result) !== 0) {
            return new CappasityModelFile($result[0]['cappasity_id'], '', '', $params);
        }

        return null;
    }
}
