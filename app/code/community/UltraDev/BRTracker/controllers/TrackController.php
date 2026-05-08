<?php
class UltraDev_BRTracker_TrackController extends Mage_Core_Controller_Front_Action
{
    /**
     * /brtracker/track/index?order_id=XXX&tracking_code=YYY
     * URL amigável configurada no config.xml: /rastreio/:order_id/:tracking_code
     */
    public function indexAction(): void
    {
        $orderId      = (int)$this->getRequest()->getParam('order_id');
        $trackingCode = trim((string)$this->getRequest()->getParam('tracking_code'));

        if (!$orderId || !$trackingCode) {
            $this->_redirect('/');
            return;
        }

        /** @var UltraDev_BRTracker_Model_Track $track */
        $track = Mage::getModel('brtracker/track')
            ->loadByOrderAndCode($orderId, $trackingCode);

        if (!$track->getId()) {
            $this->_forward('noRoute');
            return;
        }

        // Carrega pedido para validação mínima de segurança
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$order->getId()) {
            $this->_forward('noRoute');
            return;
        }

        Mage::register('brtracker_current_track', $track);
        Mage::register('brtracker_current_order', $order);

        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');
        $this->getLayout()->getBlock('head')
            ?->setTitle(Mage::helper('brtracker')->__('Rastreio do Pedido #%s', $order->getIncrementId()));
        $this->renderLayout();
    }
}
