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

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_5_0($module)
{
    $table = _DB_PREFIX_ . CappasityManagerDatabase::TABLE_CAPPASITY;
    $alter = "ALTER TABLE `{$table}` "
      . " ADD `variant_id` INT UNSIGNED NOT NULL DEFAULT 0, "
      . " ADD `from_hook` TINYINT UNSIGNED NOT NULL DEFAULT 0, "
      . " ADD `from_sync` TINYINT UNSIGNED NOT NULL DEFAULT 0, "
      . " ADD `from_pick` TINYINT UNSIGNED NOT NULL DEFAULT 0, "
      . " ADD UNIQUE INDEX(`product_id`, `variant_id`) ";

    $module->uninstallOverrides();
    $module->installOverrides();

    return Db::getInstance()->execute($alter);
}
