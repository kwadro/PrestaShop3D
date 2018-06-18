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

require dirname(__FILE__) . '/vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @method registerHook($hook)
 * @method displayWarning($text)
 * @method displayConfirmation($text)
 * @method displayError($text)
 * @method l($text)
 * @method adminDisplayInformation($text)
 *
 * @property array $_errors
 */
class Cappasity3d extends Module
{
    /**
     * Form fields
     */
    const FORM_FIELD_CAPPASITY_ACTION = 'cappasityAction';
    const FORM_FIELD_CAPPASITY_ID = 'cappasityId';
    const FORM_FIELD_REFERENCE = 'reference';
    const FORM_FIELD_EAN13 = 'ean13';
    const FORM_FIELD_UPC = 'upc';

    /**
     * Request params
     */
    const REQUEST_PARAM_PRODUCT_ID = 'id_product';

    /**
     *
     */
    const CACHE_KEY = 'cappasity::';

    /**
     * @var CappasityClient
     */
    protected $client;

    /**
     * Cappasity3d constructor.
     */
    public function __construct()
    {
        $this->name = 'cappasity3d';
        $this->tab = 'others';
        $this->version = '1.4.11';
        $this->author = 'Cappasity Inc';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = 'c0e2e1cb0722701f2fbe5ad322c89654';
        $this->author_address = '0x00F704196923d7739D922c83b48ca21916d024c5';

        parent::__construct();

        $this->displayName = $this->l('Cappasity 3D and 360 Product Viewer');
        $this->description = $this->l('Showcase your product in 3D with the most powerful 3D platform on the market. Create your own 3D Views with the free Cappasity solution using just a digital camera and embed into your online store.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->client = new CappasityClient($this->version);
        $dbManager = new CappasityManagerDatabase(Db::getInstance(), _DB_PREFIX_, _MYSQL_ENGINE_);

        $this->dbManager = $dbManager;
        $this->accountManager = new CappasityManagerAccount($this->client, $this);
        $this->fileManager = new CappasityManagerFile($this->client, $dbManager);
        $this->playerManager = new CappasityManagerPlayer($this);
        $this->syncManager = new CappasityManagerSync($this->client, $dbManager, $this);
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (extension_loaded('curl') === false) {
            $this->_errors[] = $this->l('\'cURL\' extension required');

            return false;
        }

        $result = $this->dbManager->setUp()
            && parent::install()
            && $this->installTab()
            && $this->registerHook('header')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('displayAdminProductsExtra');

        if ($result === true && $this->isGTE171() === true) {
            $result = $this->registerHook('displayAfterProductThumbs');
        }

        if ($result === true) {
            $this->playerManager->setDefaultSettings();
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function uninstall()
    {
        $this->accountManager->removeSettings();
        $this->playerManager->removeSettings();

        return $this->dbManager->cleanUp()
            && $this->uninstallTab()
            && parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';

        $this->adminDisplayInformation(
            $this->context->smarty->fetch($this->local_path . 'views/templates/admin/description.tpl')
        );

        if (Tools::isSubmit(CappasityManagerAccount::SETTINGS_SUBMIT_KEY) === true) {
            $output .= $this->processAccountSettings();
        }

        if (Tools::isSubmit(CappasityManagerPlayer::SETTINGS_SUBMIT_KEY) === true) {
            $output .= $this->processPlayerSettings();
        }

        if (Tools::isSubmit(CappasityManagerSync::SETTINGS_SUBMIT_KEY) === true) {
            $output .= $this->processSync();
        }

        $output .= $this->accountManager->renderSettingsForm(
            $this->getFormHelper(CappasityManagerAccount::SETTINGS_SUBMIT_KEY)
        );

        if ($this->accountManager->getToken() !== null) {
            $output .= $this->playerManager->renderSettingsForm(
                $this->getFormHelper(CappasityManagerPlayer::SETTINGS_SUBMIT_KEY),
                $this->accountManager->isAccountPaid()
            );

            $output .= $this->syncManager->renderSettingsForm(
                $this->getFormHelper(CappasityManagerSync::SETTINGS_SUBMIT_KEY),
                $this->context->link->getAdminLink('AdminCappasity3d', true)
                    . '&action=status'
            );

            if ($this->syncManager->hasTasks() === true) {
                $this->context->controller->addJS(
                    $this->local_path . 'views/js/check-sync.js'
                );
            }
        }

        return $output;
    }

    /**
     * @return string
     */
    protected function processAccountSettings()
    {
        $token = Tools::getValue(CappasityManagerAccount::SETTING_TOKEN);

        try {
            $account = $this->accountManager->info($token);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->client->sentry->captureException($e, array(
                'level' => 'debug',
                'extra' => array(
                    'code' => 'E_INVALID_TOKEN'
                )
            ));
            return $this->displayError($this->l('Invalid token'));
        } catch (Exception $e) {
            $event_id = $this->client->sentry->captureException($e, array(
                'level' => 'fatal',
                'extra' => array(
                    'code' => 'E_TOKEN_SYNC'
                )
            ));
            return $this->displayError(
                '[Error ' . $event_id . '] ' .
                $this->l('Something went wrong') .
                ': ' . $e->getMessage()
            );
        }

        $this->accountManager->updateSettings($token, $account);

        return $this->displayConfirmation($this->l('Account settings were saved'));
    }

    /**
     * @return string
     */
    protected function processPlayerSettings()
    {
        try {
            // use $_POST for v1.6.0.14
            $this->playerManager->updateSettings($_POST);
        } catch (CappasityManagerPlayerExceptionsValidation $e) {
            $this->client->sentry->captureException($e, array(
                'level' => 'debug',
                'extra' => array(
                    'code' => 'E_PLAYER_SETTINGS'
                )
            ));
            return $this->displayError($e->getMessage());
        }

        return $this->displayConfirmation($this->l('Module settings were saved'));
    }

    /**
     *
     */
    protected function processSync()
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $callback = $this->is17()
          ? $this->context->link->getAdminLink('AdminCappasity3d', false)
          : Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . basename(_PS_ADMIN_DIR_) . '/'
            . $this->context->link->getAdminLink('AdminCappasity3d', false);

        try {
            $token = $this->accountManager->getToken();
            if ($token !== null) {
                // verify token still active
                $this->accountManager->info($token);
                // start sync process
                $this->syncManager->run($token, $callback, 2000);
            } else {
                throw new Exception('Attempted to sync without security token present');
            }
        } catch (Exception $e) {
            $event_id = $this->client->sentry->captureException($e, array(
                'level' => 'error',
                'extra' => array(
                    'code' => 'E_SYNC',
                    'callback' => $callback
                )
            ));

            return $this->displayError(
                '[Error ' . $event_id . '] ' .
                $this->l('Something went wrong, please try again later or contact customer care: support@cappasity.com')
            );
        }

        return $this->displayConfirmation($this->l('Sync started'));
    }

    /**
     *
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $productId = array_key_exists(self::REQUEST_PARAM_PRODUCT_ID, $params)
            ? (int)$params[self::REQUEST_PARAM_PRODUCT_ID]
            : (int)Tools::getValue(self::REQUEST_PARAM_PRODUCT_ID, 0);
        $token = $this->accountManager->getToken();

        if ($productId === 0) {
            return $this->displayWarning('You must save this product before adding 3D.');
        }

        if ($token === null) {
            return $this->displayError($this->l('Set up your account in settings of the module'));
        }

        $currentFile = $this->fileManager->getCurrent($productId, $this->playerManager->getSettings());

        $this->context->smarty->assign(
            array(
                'currentFile' => $currentFile,
                'action' => $this->context->link->getAdminLink('AdminCappasity3d', true),
                'productId' => $productId,
                'is17' => $this->is17(),
            )
        );

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/extra.tpl');
    }

    /**
     *
     */
    public function hookActionProductUpdate()
    {
        $productId = (int)Tools::getValue(self::REQUEST_PARAM_PRODUCT_ID);
        $cappasityId = Tools::getValue(self::FORM_FIELD_CAPPASITY_ID, null);
        $cappasityAction = Tools::getValue(self::FORM_FIELD_CAPPASITY_ACTION, null);

        // action is called not from admin office
        if ($cappasityId === null) {
            return true;
        }

        $cacheKey = self::CACHE_KEY . $productId;

        if ($cappasityAction === 'remove') {
            $this->fileManager->remove($productId);
            Cache::clean($cacheKey);

            return;
        }

        // if id exists update table
        if ($cappasityId !== '') {
            $result = $this->fileManager->update($productId, $cappasityId);
            Cache::store($cacheKey, true);

            return $result;
        }

        $currentModel = $this->fileManager->getCurrent($productId, $this->playerManager->getSettings());

        if ($currentModel !== null) {
            return true;
        }

        return $this->hookActionProductAdd(array('id_product' => $productId));
    }

    /**
     *
     */
    public function hookActionProductAdd($params)
    {
        $alias = $this->accountManager->getAlias();
        $productId = $params['id_product'];
        $reference = Tools::getValue(self::FORM_FIELD_REFERENCE);
        $ean13 = Tools::getValue(self::FORM_FIELD_EAN13);
        $upc = Tools::getValue(self::FORM_FIELD_UPC);
        $cacheKey = self::CACHE_KEY . $productId;

        try {
            $model = $this->fileManager->search(array($reference, $ean13, $upc), $alias);
        } catch (Exception $e) {
            $this->client->sentry->captureException($e, array(
                'level' => 'error',
                'extra' => array(
                    'code' => 'E_HOOK_PRODUCT_ADD'
                )
            ));
            return false;
        }

        if ($model === null) {
            return true;
        }

        $result = $this->fileManager->update($productId, $model['id']);
        Cache::store($cacheKey, true);

        return $result;
    }

    /**
     * @return string
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->local_path . 'views/css/cappasity.css');

        if ($this->is17() === true) {
            $this->context->controller->addJS($this->local_path . 'views/js/cappasity17.js');
        } else {
            $this->context->controller->addJS($this->local_path . 'views/js/cappasity16.js');
        }

        return $this->getEmbedCode();
    }

    /**
     * @return string
     */
    public function hookDisplayAfterProductThumbs()
    {
        // handle quickview only
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return '';
        }

        return $this->getEmbedCode()
            . '<script>'
            . Tools::file_get_contents($this->local_path . 'views/js/cappasity17.js')
            . '</script>'
            . '<style>'
            . Tools::file_get_contents($this->local_path . 'views/css/cappasity.css')
            . '</style>';
    }

    /**
     * @return string
     */
    public function getEmbedCode()
    {
        $productId = Tools::getValue(self::REQUEST_PARAM_PRODUCT_ID, null);

        if ($productId === null) {
            return '';
        }

        $currentModel = $this->fileManager->getCurrent($productId, $this->playerManager->getSettings());

        if ($currentModel === null) {
            return '';
        }

        $this->context->smarty->assign(array('model' => $currentModel));

        return $this->context->smarty->fetch($this->local_path . 'views/templates/front/embed.tpl');
    }

    /**
     * @return mixed
     */
    public function installTab()
    {
        $tab = new Tab();
        $tab->active = 0;
        $tab->name = array();
        $tab->class_name = 'AdminCappasity3d';
        $tab->id_parent = -1;
        $tab->module = $this->name;

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Cappasity';
        }

        return $tab->add();
    }

    /**
     * @return bool
     */
    public function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminCappasity3d');

        if ($id_tab) {
            $tab = new Tab($id_tab);

            return $tab->delete();
        }

        return false;
    }

    /**
     * @param string $action
     * @return HelperForm
     */
    protected function getFormHelper($action)
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = $action;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper;
    }

    /**
     * @return bool
     */
    public function is17()
    {
        return strpos(_PS_VERSION_, '1.7') !== false;
    }

    /**
     * @return string
     */
    public function isGTE171()
    {
        return $this->is17() && strpos(_PS_VERSION_, '1.7.0') === false;
    }
}
