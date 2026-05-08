<?php
class UltraDev_BRTracker_Adminhtml_BrtrackerTrackController
    extends Mage_Adminhtml_Controller_Action
{
    /**
     * Abre o popup de rastreio — chamado via AJAX no admin do shipment
     * URL: /admin/brtracker_track/popup?track_id=X
     */
    public function popupAction(): void
    {
        $trackId = (int)$this->getRequest()->getParam('track_id');

        /** @var UltraDev_BRTracker_Model_Track $track */
        $track = Mage::getModel('brtracker/track')->load($trackId);

        if (!$track->getId()) {
            $this->getResponse()->setBody(
                '<p style="color:red;padding:20px">' .
                Mage::helper('brtracker')->__('Rastreio não encontrado.') .
                '</p>'
            );
            return;
        }

        $order = Mage::getModel('sales/order')->load($track->getOrderId());

        Mage::register('brtracker_current_track', $track);
        Mage::register('brtracker_current_order', $order);

        $this->loadLayout('popup');
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('brtracker_adminhtml/sales_order_shipment_tracking_popup')
                ->toHtml()
        );
    }

    /**
     * Força re-consulta do status na transportadora para um track
     */
    public function refreshAction(): void
    {
        $trackId = (int)$this->getRequest()->getParam('track_id');
        $track   = Mage::getModel('brtracker/track')->load($trackId);

        $result = ['success' => false, 'message' => ''];

        if (!$track->getId()) {
            $result['message'] = 'Track not found.';
        } else {
            try {
                Mage::getModel('brtracker/observer')->forcePollTrack($track);
                $result['success'] = true;
                $result['status']  = $track->getStatus();
                $result['message'] = 'Status atualizado com sucesso.';
            } catch (Exception $e) {
                $result['message'] = $e->getMessage();
            }
        }

        $this->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($result));
    }

    protected function _isAllowed(): bool
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('ultradev/brtracker');
    }
}
