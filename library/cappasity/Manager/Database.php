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
 * Class CappasityManagerDatabase
 */
class CappasityManagerDatabase
{
    /**
     *
     */
    const TABLE_CAPPASITY = 'cappasity3d';

    /**
     *
     */
    const TABLE_SYNC_TASKS = 'cappasity3d_sync';

    /**
     * @var Db
     */
    protected $db;

    /**
     * @var string
     */
    protected $engine;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * Database constructor.
     * @param Db $db
     * @param string $prefix
     * @param string $engine
     */
    public function __construct(Db $db, $prefix, $engine)
    {
        $this->db = $db;
        $this->engine = pSQL($engine);
        $this->prefix = pSQL($prefix);
    }

    /**
     * @return string
     */
    protected function getCappasityTableName()
    {
        return $this->prefix . self::TABLE_CAPPASITY;
    }

    /**
     * @return string
     */
    protected function getSyncTasksTableName()
    {
        return $this->prefix . self::TABLE_SYNC_TASKS;
    }

    /**
     * @return bool
     */
    public function setUp()
    {
        $sql = array();

        $sql[] = "CREATE TABLE IF NOT EXISTS `{$this->getCappasityTableName()}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `product_id` INT UNSIGNED NOT NULL,
                `cappasity_id` VARCHAR(1024) NOT NULL,
                PRIMARY KEY  (`id`)
            ) ENGINE={$this->engine} DEFAULT CHARSET=utf8;";

        $sql[] = "CREATE TABLE IF NOT EXISTS `{$this->getSyncTasksTableName()}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `verification` VARCHAR(1024) NOT NULL,
                `created` DATETIME NOT NULL,
                PRIMARY KEY  (`id`)
            ) ENGINE={$this->engine} DEFAULT CHARSET=utf8;";

        foreach ($sql as $query) {
            if ($this->db->execute($query) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function cleanUp()
    {
        $sql = array();

        $sql[] = "DROP TABLE IF EXISTS `{$this->getCappasityTableName()}`;";
        $sql[] = "DROP TABLE IF EXISTS `{$this->getSyncTasksTableName()}`;";

        foreach ($sql as $query) {
            if ($this->db->execute($query) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws \Exception
     */
    public function getProductsList($limit = 50, $offset = 0)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $sql = "SELECT
                  `p`.`id_product` AS `id`,
                  `p`.`reference`,
                  `p`.`upc`,
                  `p`.`ean13`,
                  `ps`.`cappasity_id`
                FROM `{$this->prefix}product` AS `p`
                LEFT JOIN `{$this->getCappasityTableName()}` AS `ps` ON `p`.`id_product` = `ps`.`product_id`
                WHERE
                  `p`.`upc` > ''
                  OR `p`.`ean13` > 0
                  OR `p`.`reference` > ''
                LIMIT {$limit}
                OFFSET {$offset}";

        $result = $this->db->ExecuteS($sql);

        if (is_array($result) === false) {
            throw new \Exception('Can not get products from database');
        }

        return $result;
    }

    /**
     * @param $verification
     * @return mixed
     */
    public function createSyncTask($verification)
    {
        return $this->db->insert(self::TABLE_SYNC_TASKS, array(
            'verification' => pSQL($verification),
            'created' => date('c'),
        ));
    }

    /**
     * @return mixed
     */
    public function removeSyncTasks()
    {
        return $this->db->delete(self::TABLE_SYNC_TASKS);
    }

    /**
     * @return int
     */
    public function getSyncTasksCount()
    {
        $count = $this->db->getValue(
            "SELECT count(`id`) FROM `{$this->getSyncTasksTableName()}`"
        );

        return (int)$count;
    }

    /**
     * @param $verification
     * @return bool
     */
    public function hasSyncTask($verification)
    {
        $verification = pSQL($verification);

        return $this->db->getRow(
            "SELECT `id` FROM `{$this->getSyncTasksTableName()}` WHERE `verification` = '{$verification}'"
        );
    }

    /**
     * @param $verification
     * @return mixed
     */
    public function removeSyncTask($verification)
    {
        $verification = pSQL($verification);

        return $this->db->delete(self::TABLE_SYNC_TASKS, "verification = '{$verification}'");
    }

    /**
     * @param $productId
     * @return array
     */
    public function getCappasityExtra($productId)
    {
        $productId = (int)$productId;

        $data = $this->db->ExecuteS(
            "SELECT * FROM `{$this->getCappasityTableName()}` WHERE `product_id` = {$productId}"
        );

        if (count($data) === 0 || $data === false) {
            return null;
        }

        return $data[0];
    }

    /**
     * @param $productId
     * @return mixed
     */
    public function removeCappasityExtra($productId)
    {
        $productId = (int)$productId;

        return $this->db->delete(self::TABLE_CAPPASITY, "product_id = {$productId}");
    }

    /**
     * @param integer $productId
     * @param string $cappasityId
     * @return mixed
     */
    public function upsertCappasityExtra($productId, $cappasityId)
    {
        $productId = (int)$productId;

        $exist = $this->db->getRow(
            "SELECT `id` FROM `{$this->getCappasityTableName()}` WHERE `product_id` = {$productId}"
        );

        if ($exist === false) {
            return $this->db->insert(self::TABLE_CAPPASITY, array(
                'product_id' => $productId,
                'cappasity_id' => pSQL($cappasityId),
            ));
        }

        return $this->db->update(
            self::TABLE_CAPPASITY,
            array(
                'cappasity_id' => pSQL($cappasityId),
            ),
            "product_id={$productId}"
        );
    }
}
