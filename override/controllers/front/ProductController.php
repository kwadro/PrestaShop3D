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

        $fileId = false;
        $cacheKey = Cappasity3d::CACHE_KEY . $productId;

        if (Cache::isStored($cacheKey)) {
            $fileId = Cache::retrieve($cacheKey);
        } else {
            $client = new CappasityClient('1.4.11');
            $dbManager = new CappasityManagerDatabase(Db::getInstance(), _DB_PREFIX_, _MYSQL_ENGINE_);
            $fileManager = new CappasityManagerFile($client, $dbManager);
            $file = $fileManager->getCurrent($productId, array());

            if ($file !== null) {
                $fileId = $file->getId();
                Cache::store($cacheKey, $fileId);
            }
        }

        if (!$fileId) {
            return;
        }

        if (is_object($this->context->smarty->getTemplateVars('product'))) {
            $this->init16();
        } else {
            $this->init17($fileId);
        }
    }

    /**
     *
     */
    public function init17($fileId)
    {
        $product = $this->context->smarty->getTemplateVars('product');
        $categoryImages = $this->context->smarty->getTemplateVars('categoryImages');
        // TODO: make sure we use <module>::SETTING_ALIAS from module const
        $alias = Configuration::get('cappasityAccountAlias');
        $images = $product['images'];

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
                ImageType::getFormattedName('small') => $this->getImage($fileId, 90, 90, $alias),
                ImageType::getFormattedName('cart') => $this->getImage($fileId, 125, 125, $alias),
                ImageType::getFormattedName('home') => $this->getImageStub(),
                ImageType::getFormattedName('medium') => $this->getImage($fileId, 452, 452, $alias),
                ImageType::getFormattedName('large') => $this->getImage($fileId, 800, 800, $alias),
            ),
            'small' => $this->getImageStub(),
            'medium' => $this->getImage($fileId, 452, 452, $alias),
            'large' => $this->getImage($fileId, 800, 800, $alias),
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
        return array(
          'url' => '/modules/cappasity3d/views/img/logo-3d.jpg',
          'width' => 98,
          'height' => 98,
        );
    }

    /**
     *
     */
    public function getImage($fileId, $width, $height, $alias)
    {
        return array(
          'url' => "https://api.cappasity.com/api/files/preview/{$alias}/w{$width}-h{$height}-cpad/{$fileId}",
          'width' => $width,
          'height' => $height,
        );
    }
}
