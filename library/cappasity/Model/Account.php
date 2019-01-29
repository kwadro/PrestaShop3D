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
 * Class CappasityModelAccount
 */
class CappasityModelAccount
{
    /**
     * @var string
     */
    protected $plan;

    /**
     * @var string
     */
    protected $alias;

    /**
     * Account constructor.
     * @param $plan
     * @param $alias
     */
    public function __construct($plan, $alias)
    {
        $this->alias = $alias;
        $this->plan = $plan;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return bool
     */
    public function isFree()
    {
        return $this->plan === 'free';
    }
}
