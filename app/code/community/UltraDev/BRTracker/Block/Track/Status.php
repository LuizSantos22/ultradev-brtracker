<?php
class UltraDev_BRTracker_Block_Track_Status extends Mage_Core_Block_Template
{
    protected ?UltraDev_BRTracker_Model_Track $_track = null;
    protected ?Mage_Sales_Model_Order $_order = null;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ultradev/brtracker/track/status.phtml');
    }

    public function getTrack(): ?UltraDev_BRTracker_Model_Track
    {
        if ($this->_track === null) {
            $this->_track = Mage::registry('brtracker_current_track');
        }
        return $this->_track;
    }

    public function getOrder(): ?Mage_Sales_Model_Order
    {
        if ($this->_order === null) {
            $this->_order = Mage::registry('brtracker_current_order');
        }
        return $this->_order;
    }

    public function getTrackingHistory(): array
    {
        $track = $this->getTrack();
        if (!$track) {
            return [];
        }
        $raw = $track->getData('tracking_history');
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? array_reverse($decoded) : [];
    }

    public function getCurrentStatus(): string
    {
        return $this->getTrack()?->getStatus() ?? 'pending';
    }

    public function getCarrierName(): string
    {
        return (string)($this->getTrack()?->getCarrierName() ?? '');
    }

    public function getTrackingCode(): string
    {
        return (string)($this->getTrack()?->getTrackingCode() ?? '');
    }

    public function getTrackingUrl(): string
    {
        return (string)($this->getTrack()?->getTrackingUrl() ?? '');
    }

    public function getShippingAddress(): string
    {
        $address = $this->getOrder()?->getShippingAddress();
        if (!$address) {
            return '';
        }
        return implode(', ', array_filter([
            $address->getStreetFull(),
            $address->getCity(),
            $address->getRegion(),
            $address->getPostcode(),
        ]));
    }

    public function getShippedOn(): string
    {
        $order = $this->getOrder();
        if (!$order) {
            return '';
        }
        $collection = $order->getShipmentsCollection()->setPageSize(1);
        $shipment   = $collection->getFirstItem();
        if ($shipment && $shipment->getId()) {
            return Mage::helper('core')->formatDate(
                $shipment->getCreatedAt(), Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM
            );
        }
        return '';
    }

    /**
     * Mapa de status => label traduzível
     */
    public function getStatusLabel(string $status): string
    {
        $map = [
            'pending'          => $this->__('Aguardando'),
            'shipped'          => $this->__('Despachado'),
            'in_transit'       => $this->__('Em Trânsito'),
            'out_for_delivery' => $this->__('Saiu p/ Entrega'),
            'delivered'        => $this->__('Entregue'),
            'exception'        => $this->__('Ocorrência'),
        ];
        return $map[$status] ?? ucfirst($status);
    }

    /**
     * Ordem dos steps da timeline
     */
    public function getTimelineSteps(): array
    {
        return [
            'shipped'          => $this->__('Despachado'),
            'in_transit'       => $this->__('Em Trânsito'),
            'out_for_delivery' => $this->__('Saiu p/ Entrega'),
            'delivered'        => $this->__('Entregue'),
        ];
    }

    /**
     * Retorna: 'done' | 'active' | 'pending'
     */
    public function getStepState(string $step): string
    {
        $order = array_keys($this->getTimelineSteps());
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
