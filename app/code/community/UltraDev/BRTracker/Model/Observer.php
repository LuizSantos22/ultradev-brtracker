<?php
class UltraDev_BRTracker_Model_Observer
{
    /**
     * Disparado após salvar um tracking em um shipment (admin)
     */
    public function afterShipmentTrackSaved(Varien_Event_Observer $observer): void
    {
        /** @var UltraDev_BRTracker_Helper_Data $helper */
        $helper = Mage::helper('brtracker');
        if (!$helper->isEnabled()) {
            return;
        }

        /** @var Mage_Sales_Model_Order_Shipment_Track $track */
        $track    = $observer->getEvent()->getTrack();
        $shipment = $track->getShipment();
        $order    = $shipment->getOrder();
        $address  = $order->getShippingAddress();

        $trackingCode = trim($track->getTrackNumber() ?: $track->getNumber());
        if (empty($trackingCode)) {
            return;
        }

        // Determina telefone para WA
        $phoneField   = Mage::getStoreConfig('brtracker/whatsapp/phone_field') ?: 'telephone';
        $rawPhone     = $address ? (string)$address->getData($phoneField) : '';
        $phone        = $rawPhone ? $helper->normalizeBrazilianPhone($rawPhone) : null;

        $trackingUrl  = $helper->buildTrackingUrl($track->getCarrierCode(), $trackingCode);

        // Cria ou atualiza registro
        /** @var UltraDev_BRTracker_Model_Track $model */
        $model = Mage::getModel('brtracker/track');
        $model->loadByOrderAndCode((int)$order->getId(), $trackingCode);

        $model->setOrderId((int)$order->getId())
              ->setShipmentId((int)$track->getParentId())
              ->setTrackingCode($trackingCode)
              ->setCarrierCode($track->getCarrierCode())
              ->setCarrierName($track->getTitle())
              ->setTrackingUrl($trackingUrl)
              ->setCustomerEmail($order->getCustomerEmail())
              ->setCustomerPhone($phone);

        if (!$model->getId()) {
            $model->setStatus('shipped');
        }

        $model->save();

        // Notifica se configurado
        if (Mage::getStoreConfigFlag('brtracker/general/notify_shipped')) {
            Mage::getModel('brtracker/notification_email')->send($model, 'shipped', $order);
            if ($helper->isWhatsappEnabled()) {
                Mage::getModel('brtracker/notification_whatsapp')->send($model, 'shipped', $order);
            }
        }
    }

    /**
     * Disparado após remover um tracking de um shipment
     */
    public function afterShipmentTrackDeleted(Varien_Event_Observer $observer): void
    {
        $track        = $observer->getEvent()->getTrack();
        $order        = $track->getShipment()->getOrder();
        $trackingCode = trim($track->getTrackNumber() ?: $track->getNumber());

        /** @var UltraDev_BRTracker_Model_Track $model */
        $model = Mage::getModel('brtracker/track');
        $model->loadByOrderAndCode((int)$order->getId(), $trackingCode);
        if ($model->getId()) {
            $model->delete();
        }
    }

    /**
     * Cron: consulta status nas APIs de rastreio e notifica mudanças
     */
    public function cronPollCarriers(): void
    {
        if (!Mage::helper('brtracker')->isEnabled()) {
            return;
        }

        /** @var UltraDev_BRTracker_Model_Resource_Track_Collection $collection */
        $collection = Mage::getModel('brtracker/track')->getCollection()
            ->addFieldToFilter('status', ['nin' => ['delivered', 'exception_final']]);

        foreach ($collection as $track) {
            try {
                $this->_pollAndNotify($track);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    protected function _pollAndNotify(UltraDev_BRTracker_Model_Track $track): void
    {
        $helper   = Mage::helper('brtracker');
        $newStatus = $this->_fetchStatus($track);

        if (!$newStatus || $newStatus === $track->getStatus()) {
            return;
        }

        $oldStatus = $track->getStatus();
        $track->setStatus($newStatus)->save();

        $order = Mage::getModel('sales/order')->load($track->getOrderId());

        $notifyMap = [
            'in_transit'       => 'notify_in_transit',
            'out_for_delivery' => 'notify_out_for_delivery',
            'delivered'        => 'notify_delivered',
            'exception'        => 'notify_exception',
        ];

        if (isset($notifyMap[$newStatus])
            && Mage::getStoreConfigFlag('brtracker/general/' . $notifyMap[$newStatus])
        ) {
            Mage::getModel('brtracker/notification_email')->send($track, $newStatus, $order);
            if ($helper->isWhatsappEnabled()) {
                Mage::getModel('brtracker/notification_whatsapp')->send($track, $newStatus, $order);
            }
        }
    }

    /**
     * Stub: implementar integração real com API de rastreio (Frenet, ME, etc.)
     * Retorna string de status ou null se não houver mudança
     */
    protected function _fetchStatus(UltraDev_BRTracker_Model_Track $track): ?string
    {
        // Ponto de extensão: integre aqui Frenet Track API ou Melhor Envio Tracking
        return null;
    }
}
