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
  * Class ProductController
  */
class ProductController extends ProductControllerCore
{
    /**
     *
     */
    const IMAGE_ID = 1000000000;

    /**
     *
     */
    const IMAGE_LEGEND = 'cappasity-preview';

    /**
     *
     */
    public function initContent()
    {
        parent::initContent();

        $productId = (int)Tools::getValue('id_product', null);

        if ($productId === null) {
            return;
        }

        $hasModel = false;
        $cacheKey = Cappasity3d::CACHE_KEY . $productId;

        if (Cache::isStored($cacheKey)) {
            $hasModel = Cache::retrieve($cacheKey);
        } else {
            $client = new CappasityClient();
            $dbManager = new CappasityManagerDatabase(Db::getInstance(), _DB_PREFIX_, _MYSQL_ENGINE_);
            $fileManager = new CappasityManagerFile($client, $dbManager);
            $hasModel = $fileManager->getCurrent($productId, array()) !== null;

            Cache::store($cacheKey, $hasModel);
        }

        if (!$hasModel) {
            return;
        }

        if (is_object($this->context->smarty->getTemplateVars('product'))) {
            $this->init16();
        } else {
            $this->init17();
        }
    }

    /**
     *
     */
    public function init17()
    {
        $product = $this->context->smarty->getTemplateVars('product');
        $categoryImages = $this->context->smarty->getTemplateVars('categoryImages');
        $images = $product['images'];
        $imageStub = $this->getImageStub();

        foreach ($categoryImages as $category => $pictures) {
            if (count($pictures) > 0) {
                $sampleImage = reset($pictures);
                $sampleImage['id_image'] = self::IMAGE_ID;
                $sampleImage['legend'] = self::IMAGE_LEGEND;
                array_unshift($categoryImages[$category], $sampleImage);
            }
        }

        array_unshift($images, array(
            'bySize' => array(
                ImageType::getFormattedName('small') => $imageStub,
                ImageType::getFormattedName('cart') => $imageStub,
                ImageType::getFormattedName('home') => $imageStub,
                ImageType::getFormattedName('medium') => $imageStub,
                ImageType::getFormattedName('large') => $imageStub,
            ),
            'small' => $imageStub,
            'medium' => $imageStub,
            'large' => $imageStub,
            'legend' => self::IMAGE_ID,
            'cover' => '0',
            'id_image' => self::IMAGE_LEGEND,
            'position' => self::IMAGE_ID,
            'associatedVariants' => array_keys($categoryImages),
        ));
        $product['images'] = $images;

        $this->context->smarty->assign(array(
            'combinationImages' => $categoryImages,
            'product' => $product,
        ));
    }

    /**
     *
     */
    public function init16()
    {
        $images = $this->context->smarty->getTemplateVars('images');
        $combinationImages = $this->context->smarty->getTemplateVars('combinationImages');

        foreach ($combinationImages as $productId => $pictures) {
            if (count($pictures) > 0) {
                $sampleImage = reset($pictures);
                $sampleImage['id_image'] = self::IMAGE_ID;
                $sampleImage['legend'] = self::IMAGE_LEGEND;
                array_unshift($combinationImages[$productId], $sampleImage);
            }
        }

        array_unshift($images, array(
            'legend' => self::IMAGE_LEGEND,
            'cover' => '0',
            'id_image' => self::IMAGE_ID,
            'position' => self::IMAGE_ID,
        ));

        $this->context->smarty->assign(array(
            'combinationImages' => $combinationImages,
            'images' => $images,
        ));
    }

    /**
     *
     */
    public function getImageStub()
    {
        return array('url' => '/modules/cappasity3d/logo.png', 'width' => 57, 'height' => 57);
    }
}
