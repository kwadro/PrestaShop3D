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

/**
 * Class CappasityManagerAbstractManager
 */
class CappasityManagerAbstractManager
{
    /**
     * @var array
     */
    protected $settings = array();

    /**
     *
     */
    public function removeSettings()
    {
        if (count($this->settings) === 0) {
            return;
        }

        foreach ($this->settings as $setting) {
            Configuration::deleteByName($setting);
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    protected function setSetting($key, $value)
    {
        Configuration::updateValue($key, $value);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getSetting($key, $default = null)
    {
        if (Configuration::hasKey($key) === true) {
            return Configuration::get($key);
        }

        return $default;
    }

    /**
     * @param string $key
     */
    protected function deleteSetting($key)
    {
        Configuration::deleteByName($key);
    }
}
