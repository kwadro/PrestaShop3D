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
  * Class ProductController
  */
class ProductController extends ProductControllerCore
{
    /**
     * @TODO legacy block, prestashop could not remove those constans on uninstal
     */
    const IMAGE_ID = 1000000000;
    const IMAGE_LEGEND = 'cappasity-preview';

    /**
     *
     */
    public function initContent()
    {
        parent::initContent();

        $productId = Tools::getValue('id_product', null);

        if ($productId === null) {
            return;
        }

        $cappasityImages = $this->getCappasityImages($productId);

        if (count($cappasityImages) === 0) {
            return;
        }

        $product = $this->context->smarty->getTemplateVars('product');

        if (is_array($product) || $product instanceof ArrayAccess) {
            $this->init17($cappasityImages);
        } else {
            $this->init16($cappasityImages);
        }
    }

    /**
     *
     */
    protected function getCappasityImages($productId)
    {
        $cacheKey = Cappasity3d::CACHE_KEY . $productId;
        $dbManager = new CappasityManagerDatabase(Db::getInstance(), _DB_PREFIX_, _MYSQL_ENGINE_);

        // @todo Does a cache work?
        if (Cache::isStored($cacheKey)) {
            $cappasityImages = Cache::retrieve($cacheKey);
        } else {
            $cappasityImages = $dbManager->getCappasity(array('productId' => (int)$productId));

            if (count($cappasityImages) !== 0) {
                Cache::store($cacheKey, $cappasityImages);
            }
        }

        return $cappasityImages;
    }

    /**
     *
     */
    protected function groupByCappasityImage(array $images = array())
    {
        $counter = 100000000;
        $cappasityImages = array();

        foreach ($images as $image) {
            $cappasityId = (string)$image['cappasity_id'];
            $variantId = (string)$image['variant_id'];

            if (array_key_exists($cappasityId, $cappasityImages) === false) {
                $imageId = (string)$counter += 1;
                $cappasityImages[$cappasityId] = array(
                  'cappasityId' => $cappasityId,
                  'imageId' => $imageId,
                  'variants' => array((string)$variantId),
                );
            } else {
                $cappasityImages[$cappasityId]['variants'][] = $variantId;
            }
        }

        return $cappasityImages;
    }

    /**
     *
     */
    protected function groupByVariant(array $images = array())
    {
        $counter = 100000000;
        $cappasityImages = array();

        foreach ($images as $image) {
            $cappasityId = (string)$image['cappasity_id'];
            $variantId = (string)$image['variant_id'];
            $imageId = (string)$counter += 1;

            if (array_key_exists($variantId, $cappasityImages) === false) {
                $cappasityImages[$variantId] = array();
            }

            if (array_key_exists($cappasityId, $cappasityImages[$variantId]) === true) {
                continue;
            }

            $cappasityImages[$variantId][$cappasityId] = array(
              'cappasityId' => $cappasityId,
              'imageId' => $imageId,
              'variantId' => $variantId,
            );
        }

        return $cappasityImages;
    }

    /**
     *
     */
    protected function init17(array $cappasityImages = array())
    {
        $product = $this->context->smarty->getTemplateVars('product');
        $combinationImages = is_array($this->context->smarty->getTemplateVars('combinationImages'))
          ? $this->context->smarty->getTemplateVars('combinationImages')
          : array();
        $productAttributeId = (string)$product['id_product_attribute'];
        $images = $product['images'];
        $productVariants = $product['main_variants'];

        $groupedByCappasityImage = $this->groupByCappasityImage($cappasityImages);
        $groupedByVariant = $this->groupByVariant($cappasityImages);

        foreach ($productVariants as $productVariant) {
            $productVariantId = (string)$productVariant['id_product_attribute'];
            $cappasityVariantImages = false;

            if (array_key_exists($productVariantId, $groupedByVariant) === true) {
                $cappasityVariantImages = $groupedByVariant[$productVariantId];
            } elseif (array_key_exists('0', $groupedByVariant) === true) {
                $cappasityVariantImages = $groupedByVariant['0'];
            }

            if ($cappasityVariantImages === false) {
                continue;
            }

            if (array_key_exists($productVariantId, $combinationImages) === false) {
                $combinationImages[$productVariantId] = array();
            }

            foreach ($cappasityVariantImages as $cappasityVariantImage) {
                $imageId = (string)$cappasityVariantImage['imageId'];
                $cappasityId = (string)$cappasityVariantImage['cappasityId'];
                $legend = "cappasity:{$cappasityId}";

                array_unshift($combinationImages[$productVariantId], array(
                    'id_product_attribute' => $productVariantId,
                    'id_image' => $imageId,
                    'legend' => $legend,
                ));
            }
        }

        // has variants
        if (array_key_exists($productAttributeId, $groupedByVariant) === true) {
            foreach ($groupedByVariant[$productAttributeId] as $variantImage) {
                array_unshift($images, $this->get17Image($variantImage, $groupedByCappasityImage));
            }
        // has no variants, try to add common image
        } elseif (array_key_exists('0', $groupedByVariant) === true) {
            foreach ($groupedByVariant['0'] as $variantImage) {
                array_unshift($images, $this->get17Image($variantImage, $groupedByCappasityImage));
            }
        }

        $product['images'] = $images;

        $this->context->smarty->assign(array(
            'combinationImages' => $combinationImages,
            'product' => $product,
        ));
    }

    /**
     *
     */
    protected function init16(array $cappasityImages = array())
    {
        $groupedByCappasityImage = $this->groupByCappasityImage($cappasityImages);
        // could be null or array
        $templateImages = $this->context->smarty->getTemplateVars('images');
        $images = is_array($templateImages) ? $templateImages : array();
        // could be null or array
        $combinationImages = $this->context->smarty->getTemplateVars('combinationImages');

        foreach ($groupedByCappasityImage as $cappasityImage) {
            $imageId = (string)$cappasityImage['imageId'];
            $variantsIds = $cappasityImage['variants'];
            $cappasityId = (string)$cappasityImage['cappasityId'];
            $legend = "cappasity:{$cappasityId}";
            // add on top of all pictures
            $images = array($imageId => array(
                'legend' => $legend,
                'cover' => '0',
                'id_image' => $imageId,
                'position' => $imageId,
            )) + $images;

            // add variants
            foreach ($variantsIds as $variantId) {
                if ($variantId === '0') {
                    continue;
                }

                if (is_array($combinationImages) === false) {
                    $combinationImages = array();
                }

                if (array_key_exists($variantId, $combinationImages) === false) {
                    $combinationImages[$variantId] = array();
                }

                // add picture for variant
                array_unshift($combinationImages[$variantId], array(
                     'id_product_attribute' => $variantId,
                     'id_image' => $imageId,
                     'legend' => $legend,
                ));
            }
        }

        $this->context->smarty->assign(array(
            'combinationImages' => $combinationImages,
            'images' => $images,
        ));
    }

    /**
     *
     */
    protected function getImageStub()
    {
        return array(
            'url' => '/modules/cappasity3d/views/img/1548780211.logo-3d.jpg',
            'width' => 98,
            'height' => 98,
        );
    }

    /**
     *
     */
    protected function getImage($fileId, $width, $height)
    {
        // TODO: make sure we use <module>::SETTING_ALIAS from module const
        $alias = Configuration::get('cappasityAccountAlias');

        return array(
            'url' => "https://api.cappasity.com/api/files/preview/{$alias}/w{$width}-h{$height}-cpad/{$fileId}.jpeg",
            'width' => $width,
            'height' => $height,
        );
    }

    /**
     *
     */
    protected function get17Image($image, $groupedByCappasityImage)
    {
        $imageId = (string)$image['imageId'];
        $cappasityId = (string)$image['cappasityId'];
        $legend = "cappasity:{$cappasityId}";

        return array(
            'bySize' => array(
                ImageType::getFormattedName('small') => $this->getImage($cappasityId, 90, 90),
                ImageType::getFormattedName('cart') => $this->getImage($cappasityId, 125, 125),
                ImageType::getFormattedName('home') => $this->getImageStub(),
                ImageType::getFormattedName('medium') => $this->getImage($cappasityId, 452, 452),
                ImageType::getFormattedName('large') => $this->getImage($cappasityId, 800, 800),
            ),
            'small' => $this->getImageStub(),
            'medium' => $this->getImage($cappasityId, 452, 452),
            'large' => $this->getImage($cappasityId, 800, 800),
            'legend' => $legend,
            'cover' => '0',
            'id_image' => $imageId,
            'position' => $imageId,
            'associatedVariants' => array_values(array_diff(
                $groupedByCappasityImage[$cappasityId]['variants'],
                array('0')
            )),
        );
    }
}
