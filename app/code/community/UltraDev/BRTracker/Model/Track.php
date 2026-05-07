<?php
class UltraDev_BRTracker_Model_Track extends Mage_Core_Model_Abstract
{
    const STATUS_SHIPPED           = 'shipped';
    const STATUS_IN_TRANSIT        = 'in_transit';
    const STATUS_OUT_FOR_DELIVERY  = 'out_for_delivery';
    const STATUS_DELIVERED         = 'delivered';
    const STATUS_EXCEPTION         = 'exception';

    protected function _construct()
    {
        $this->_init('brtracker/track');
    }

    public function loadByOrderAndCode(int $orderId, string $code): self
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('tracking_code', $code)
            ->setPageSize(1);
        $item = $collection->getFirstItem();
        if ($item->getId()) {
            $this->setData($item->getData());
        }
        return $this;
    }

    public function getEmailSentList(): array
    {
        $raw = $this->getData('email_sent');
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }

    public function markEmailSent(string $event): self
    {
        $list   = $this->getEmailSentList();
        $list[] = $event;
        return $this->setData('email_sent', json_encode(array_unique($list)));
    }

    public function wasEmailSent(string $event): bool
    {
        return in_array($event, $this->getEmailSentList(), true);
    }

    public function getWaSentList(): array
    {
        $raw = $this->getData('wa_sent');
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }

    public function markWaSent(string $event): self
    {
        $list   = $this->getWaSentList();
        $list[] = $event;
        return $this->setData('wa_sent', json_encode(array_unique($list)));
    }

    public function wasWaSent(string $event): bool
    {
        return in_array($event, $this->getWaSentList(), true);
    }
}
