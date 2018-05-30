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
 * Class CappasityManagerSync
 */
class CappasityManagerSync extends CappasityManagerAbstractManager
{
    /**
     * @var string
     */
    const SETTINGS_SUBMIT_KEY = 'submitCappasityAccountSync';

    /**
     * @var CappasityClient
     */
    protected $client;

    /**
     * @var CappasityManagerDatabase
     */
    protected $db;

    /**
     * @var Cappasity3d
     */
    protected $module;

    /**
     * CappasityManagerSync constructor.
     * @param CappasityClient $client
     * @param CappasityManagerDatabase $db
     * @param Cappasity3d $module
     */
    public function __construct(CappasityClient $client, CappasityManagerDatabase $db, Cappasity3d $module = null)
    {
        $this->client = $client;
        $this->db = $db;
        $this->module = $module;
    }

    /**
     * @param  string  $sku
     * @return boolean
     */
    protected function isValidSKU(string $sku)
    {
        return preg_match('/^[0-9A-Za-z_\\-.]{1,50}$/', $sku);
    }

    /**
     * @param array $products
     * @return array
     */
    protected function makeChunk(array $products)
    {
        $chunk = array();
        $refs = array('reference', 'upc', 'ean13');

        foreach ($products as $product) {
            $params = array('id' => $product['id'], 'aliases' => array());

            // match all possible SKUs
            foreach ($refs as $alias) {
                $sku = $product[$alias];
                if (empty($sku) === false && $this->isValidSKU($sku) === true) {
                    $params['aliases'][] = $sku;
                }
            }

            // in case we have no available SKUs -> skip
            if (count($params['aliases']) === 0) {
                continue;
            }

            // if we already have an assigned cappasity id -> verify it
            if ($product['cappasity_id'] !== null) {
                $params['capp'] = $product['cappasity_id'];
            }

            $chunk[] = $params;
        }

        return $chunk;
    }

    /**
     * @param $token
     * @param $products
     * @param $callback
     * @param $verifyToken
     */
    protected function exchange($token, $products, $callback, $verifyToken)
    {
        $data = array(
            'data' => array(
                'attributes' => array(
                    'products' => $products,
                    'callback' => $callback,
                    'verifyToken' => $verifyToken,
                ),
            ),
        );

        $options = array(
            'headers' => array(
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
                'Content-Encoding' => 'gzip',
            ),
            'body' => gzencode(Tools::jsonEncode($data)),
        );

        $this->client->request('POST', 'files/exchange', $options);
    }

    /**
     * @param $token
     * @param $callback
     * @param int $chunksSize
     * @throws Exception
     */
    public function run($token, $callback, $chunksSize = 2000)
    {
        $i = 0;
        $this->db->removeSyncTasks();

        do {
            $products = $this->db->getProductsList($chunksSize, $chunksSize * $i);

            if (count($products) === 0) {
                break;
            }

            $verification = uniqid();
            $this->db->createSyncTask($verification);
            $chunk = $this->makeChunk($products);

            try {
                $this->exchange($token, $chunk, $callback, $verification);
            } catch (Exception $exception) {
                $this->db->removeSyncTask($verification);

                throw $exception;
            }

            $i += 1;
        } while (count($products) > 0);
    }

    /**
     * @param HelperForm $helper
     * @return string
     */
    public function renderSettingsForm(HelperForm $helper, $checkSyncLink)
    {
        $output = '';
        $form = array();

        $form['legend'] = array(
            'title' => $this->module->l('Synchronize'),
            'icon' => 'icon-cogs',
        );

        $form['description'] = $this->module->l(
            'Synchronize your product catalogue with your Cappasity account.'
            . ' We synchronize data by comparing your catalogue\'s Reference codes with SKU numbers'
            . ' on the Cappasity platform.'
        );

        $form['submit'] = array(
            'title' => $this->module->l('Synchronize'),
        );

        if ($this->hasTasks()) {
            $output .= "<div id='sync-wrapper' data-url='{$checkSyncLink}'>"
                . $this->module->displayWarning('Synchronization in progress')
                . '</div>';
        }

        $output .= $helper->generateForm(array(array('form' => $form)));

        return $output;
    }

    /**
     * @return int
     */
    public function hasTasks()
    {
        return $this->db->getSyncTasksCount() > 0;
    }

    /**
     * @param $verification
     * @return bool
     */
    public function hasTask($verification)
    {
        return (bool)$this->db->hasSyncTask($verification);
    }

    /**
     * @param $verification
     * @return bool
     */
    public function removeTask($verification)
    {
        return $this->db->removeSyncTask($verification);
    }
}
