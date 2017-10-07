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
 * Class CappasityManagerPlayer
 */
class CappasityManagerPlayer extends CappasityManagerAbstractManager
{
    /**
     *
     */
    const SETTINGS_SUBMIT_KEY = 'submitCappasityPlayerSettings';

    /**
     * @var array
     */
    protected $settings = array(
        'autorun' => array(
            'type' => 'boolean',
            'default' => 1,
            'paid' => false,
            'description' => 'Auto-start player',
            'name' => 'cappasityPlayerAutorun',
            'enabled' => true,
        ),
        'closebutton' => array(
            'type' => 'boolean',
            'default' => 0,
            'paid' => true,
            'description' => 'Show close button',
            'name' => 'cappasityPlayerClosebutton',
            'enabled' => true,
        ),
        'hidecontrols' => array(
            'type' => 'boolean',
            'default' => 0,
            'paid' => true,
            'description' => 'Hide player controls',
            'name' => 'cappasityPlayerHideControls',
            'enabled' => true,
        ),
        'logo' => array(
            'type' => 'boolean',
            'default' => 1,
            'paid' => true,
            'description' => 'Show logo',
            'name' => 'cappasityPlayerLogo',
            'enabled' => true,
        ),
        'hidefullscreen' => array(
            'type' => 'boolean',
            'default' => 1,
            'paid' => true,
            'description' => 'Hide fullscreen button',
            'name' => 'cappasityPlayerHideFullScreen',
            'enabled' => false,
        ),
        'width' => array(
            'type' => 'string',
            'default' => '100%',
            'paid' => false,
            'description' => 'Width of embedded window (px or %)',
            'name' => 'cappasityPlayerWidth',
            'enabled' => true,
            'validation' => array(
                'method' => 'isSize',
                'error' => 'Width must be a number of pixels or persents',
            ),
        ),
        'height' => array(
            'type' => 'string',
            'default' => '600px',
            'paid' => false,
            'description' => 'Height of embedded window (px or %)',
            'name' => 'cappasityPlayerHeight',
            'enabled' => true,
            'validation' => array(
                'method' => 'isSize',
                'error' => 'Height must be a number of pixels or persents',
            ),
        ),
    );

    /**
     * @var \Cappasity3d
     */
    protected $module;

    /**
     * Player constructor.
     * @param Cappasity3d $module
     */
    public function __construct(Cappasity3d $module)
    {
        $this->module = $module;
    }

    /**
     *
     */
    public function removeSettings()
    {
        foreach ($this->settings as $setting) {
            $this->deleteSetting($setting['name']);
        }
    }

    /**
     *
     */
    public function setDefaultSettings()
    {
        foreach ($this->settings as $setting) {
            $this->setSetting($setting['name'], $setting['default']);
        }
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        $settings = array();

        foreach ($this->settings as $key => $setting) {
            $settings[$key] = $this->getSetting($setting['name'], $setting['default']);
        }

        return $settings;
    }

    /**
     * @param array $settings
     */
    public function updateSettings(array $settings = array())
    {
        foreach ($this->getEnabledSettings() as $setting) {
            $name = $setting['name'];

            if (array_key_exists($name, $settings) === false) {
                continue;
            }

            $value = $settings[$name];

            if (array_key_exists('validation', $setting) === true) {
                $validationOptions = $setting['validation'];

                if ($this->{$validationOptions['method']}($value) === false) {
                    throw new CappasityManagerPlayerExceptionsValidation(
                        $validationOptions['error']
                    );
                }
            }

            $this->setSetting($name, $value);
        }
    }

    /**
     * @param HelperForm $helper
     * @param boolean $isAccountPaid
     * @return string
     */
    public function renderSettingsForm(HelperForm $helper, $isAccountPaid)
    {
        $settings = $this->getEnabledSettings();
        $params = $isAccountPaid
            ? $settings
            : array_filter($settings, function ($value) {
                return $value['paid'] !== true;
            });
        $values = array();
        $input = array();

        foreach ($params as $param) {
            $name = $param['name'];
            $values[$name] = $this->getSetting($name, $param['default']);

            switch ($param['type']) {
                case 'boolean':
                    $input[] = array(
                        'type' => 'select',
                        'label' => $this->module->l($param['description']),
                        'name' => $name,
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_option' => 0,
                                    'name' => 'no',
                                ),
                                array(
                                    'id_option' => 1,
                                    'name' => 'yes',
                                )
                            ),
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                    );
                    break;
                case 'string':
                    $input[] = array(
                        'type' => 'text',
                        'label' => $this->module->l($param['description']),
                        'name' => $name,
                        'required' => true,
                    );
                    break;
            }
        }

        $helper->fields_value = $values;

        return $helper->generateForm(
            array(
                array(
                    'form' => array(
                        'legend' => array(
                            'title' => $this->module->l('Player settings'),
                            'icon' => 'icon-cogs',
                        ),
                        'description' => $this->module->l('3D player settings'),
                        'input' => $input,
                        'submit' => array(
                            'title' => $this->module->l('Save'),
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * @return array
     */
    protected function getEnabledSettings()
    {
        return array_filter($this->settings, function ($value) {
            return $value['enabled'] === true;
        });
    }

    /**
     * @return boolean
     */
    protected function isSize($value)
    {
        return preg_match('/^\d+(px|%)$/m', $value) === 1;
    }
}
