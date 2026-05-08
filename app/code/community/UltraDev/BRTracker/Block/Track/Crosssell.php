<?php
class UltraDev_BRTracker_Block_Track_Crosssell extends Mage_Catalog_Block_Product_List
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ultradev/brtracker/track/crosssell.phtml');
    }

    /**
     * Retorna produtos em destaque (is_saleable + enabled) para cross-sell.
     * Prioriza cross-sells dos itens do pedido; fallback para featured products.
     */
    public function getCrosssellProducts(): Mage_Catalog_Model_Resource_Product_Collection
    {
        $order = Mage::registry('brtracker_current_order');
        $productIds = [];

        if ($order) {
            foreach ($order->getAllVisibleItems() as $item) {
                $productIds[] = (int)$item->getProductId();
            }
        }

        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect(['name', 'price', 'small_image', 'url_key', 'special_price'])
            ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['in' => [
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            ]])
            ->addStoreFilter()
            ->setPageSize(5)
            ->setOrder('updated_at', 'DESC');

        // Exclui produtos já comprados
        if (!empty($productIds)) {
            $collection->addAttributeToFilter('entity_id', ['nin' => $productIds]);
        }

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);

        return $collection;
    }

    public function getAddToCartUrl(Mage_Catalog_Model_Product $product): string
    {
        return $this->getUrl('checkout/cart/add', [
            'product' => $product->getId(),
            '_secure' => $this->_isSecure(),
        ]);
    }

    public function formatPrice(float $price): string
    {
        return Mage::helper('core')->currency($price, true, false);
    }
}
