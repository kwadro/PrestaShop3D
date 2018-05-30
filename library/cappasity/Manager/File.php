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

        $result = $this->db->getCappasityExtra($productId);

        if ($result !== null) {
            return new CappasityModelFile($result['cappasity_id'], '', '', $params);
        }

        return null;
    }

    /**
     * @param $productId
     * @return mixed
     */
    public function remove($productId)
    {
        $productId = (int)$productId;

        return $this->db->removeCappasityExtra($productId);
    }

    /**
     * @param integer $productId
     * @param string $cappasityId
     * @return mixed
     */
    public function update($productId, $cappasityId)
    {
        $productId = (int)$productId;

        return $this->db->upsertCappasityExtra($productId, $cappasityId);
    }
}
