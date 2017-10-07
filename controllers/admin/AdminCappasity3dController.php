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
 * @copyright 2014-2017 Cappasity Inc.
 * @license   http://cappasity.us/eula_modules/  Cappasity EULA for Modules
 */

require dirname(__FILE__) . '/../../vendor/autoload.php';

/**
 * Class AdminCappasity3dController
 */
class AdminCappasity3dController extends ModuleAdminController
{
    /**
     *
     */
    public function __construct()
    {
        error_reporting(0);
        ignore_user_abort(true);
        set_time_limit(0);

        parent::__construct();

        $client = new CappasityClient();
        $dbManager = new CappasityManagerDatabase(Db::getInstance(), _DB_PREFIX_, _MYSQL_ENGINE_);

        $this->fileManager = new CappasityManagerFile($client, $dbManager);
        $this->syncManager = new CappasityManagerSync($client, $dbManager, null);

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                echo Tools::safeOutput($this->processChallenge());
                break;
            case 'POST':
                echo Tools::safeOutput($this->processProducts());
                break;
        }

        die();
    }

    /**
     * @return string
     */
    public function processChallenge()
    {
        $verifyToken =  Tools::getValue('verifyToken', null);
        $challenge = Tools::getValue('challenge', null);

        if ($verifyToken === null || $challenge === null) {
            return '';
        }

        if ($this->syncManager->hasTask($verifyToken)) {
            return $challenge;
        }

        return '';
    }

    /**
     * @return string
     */
    public function processProducts()
    {
        $input = Tools::file_get_contents('php://input');
        $verifyToken = Tools::getValue('verifyToken', null);

        if ($verifyToken === null) {
            return '';
        }

        if ($this->syncManager->hasTask($verifyToken) === false) {
            return '';
        }

        if (array_key_exists('HTTP_CONTENT_ENCODING', $_SERVER) === true
            && $_SERVER['HTTP_CONTENT_ENCODING'] === 'gzip'
        ) {
            $input = gzdecode($input);
        }

        if ($input === false) {
            return '';
        }

        $products = Tools::jsonDecode($input, true);

        if ($products === null) {
            return '';
        }

        foreach ($products as $product) {
            $this->fileManager->update($product['id'], $product['uploadId']);
            usleep(500);
        }

        $this->syncManager->removeTask($verifyToken);

        return count($products);
    }
}
