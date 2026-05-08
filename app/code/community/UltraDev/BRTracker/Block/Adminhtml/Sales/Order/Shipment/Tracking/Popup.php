<?php
class UltraDev_BRTracker_Block_Adminhtml_Sales_Order_Shipment_Tracking_Popup
    extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ultradev/brtracker/shipment/tracking_popup.phtml');
    }

    public function getTrack(): ?UltraDev_BRTracker_Model_Track
    {
        return Mage::registry('brtracker_current_track');
    }

    public function getOrder(): ?Mage_Sales_Model_Order
    {
        return Mage::registry('brtracker_current_order');
    }

    public function getTrackingHistory(): array
    {
        $track = $this->getTrack();
        if (!$track) {
            return [];
        }
        $raw     = $track->getData('tracking_history');
        $decoded = $raw ? json_decode($raw, true) : [];
        return is_array($decoded) ? array_reverse($decoded) : [];
    }

    public function getCurrentStatus(): string
    {
        return $this->getTrack()?->getStatus() ?? 'pending';
    }

    public function getTimelineSteps(): array
    {
        return [
            'shipped'          => Mage::helper('brtracker')->__('Dispatched'),
            'in_transit'       => Mage::helper('brtracker')->__('In Transit'),
            'out_for_delivery' => Mage::helper('brtracker')->__('Out for Delivery'),
            'delivered'        => Mage::helper('brtracker')->__('Delivered'),
        ];
    }

    public function getStepState(string $step): string
    {
        $order      = array_keys($this->getTimelineSteps());
        $currentIdx = array_search($this->getCurrentStatus(), $order, true);
        $stepIdx    = array_search($step, $order, true);

        if ($currentIdx === false) {
            return 'pending';
        }
        if ($stepIdx < $currentIdx) {
            return 'done';
        }
        if ($stepIdx === $currentIdx) {
            return 'active';
        }
        return 'pending';
    }
}
