<?php
class UltraDev_BRTracker_Model_Notification_Email
{
    protected static $_templateMap = [
        'shipped'          => 'brtracker_shipped',
        'in_transit'       => 'brtracker_in_transit',
        'out_for_delivery' => 'brtracker_out_for_delivery',
        'delivered'        => 'brtracker_delivered',
        'exception'        => 'brtracker_exception',
    ];

    public function send(
        UltraDev_BRTracker_Model_Track $track,
        string $event,
        Mage_Sales_Model_Order $order
    ): bool {
        if ($track->wasEmailSent($event)) {
            return false;
        }

        $templateId = self::$_templateMap[$event] ?? null;
        if (!$templateId) {
            return false;
        }

        $email = $order->getCustomerEmail();
        if (empty($email)) {
            return false;
        }

        $storeId  = (int)$order->getStoreId();
        $identity = Mage::getStoreConfig('brtracker/general/sender_email_identity', $storeId) ?: 'general';

        try {
            $mailer = Mage::getModel('core/email_template');
            $mailer->setDesignConfig(['area' => 'frontend', 'store' => $storeId]);
            $mailer->loadDefault($templateId);
            $mailer->setSenderName(Mage::getStoreConfig("trans_email/ident_{$identity}/name", $storeId));
            $mailer->setSenderEmail(Mage::getStoreConfig("trans_email/ident_{$identity}/email", $storeId));
            $mailer->send($email, $order->getCustomerName(), [
                'order'         => $order,
                'track'         => $track,
                'customer_name' => $order->getCustomerName(),
                'order_number'  => $order->getIncrementId(),
                'carrier_name'  => $track->getCarrierName(),
                'tracking_code' => $track->getTrackingCode(),
                'tracking_url'  => $track->getTrackingUrl(),
                'store'         => $order->getStore(),
            ]);

            $track->markEmailSent($event)->save();
            $this->_log($track, $order, 'email', $event, 'sent', $email);
            return true;
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_log($track, $order, 'email', $event, 'failed', $email, $e->getMessage());
            return false;
        }
    }

    protected function _log(
        UltraDev_BRTracker_Model_Track $track,
        Mage_Sales_Model_Order $order,
        string $channel,
        string $event,
        string $status,
        string $recipient,
        string $message = ''
    ): void {
        $log = Mage::getModel('brtracker/log');
        $log->setData([
            'track_id'  => $track->getId(),
            'order_id'  => $order->getId(),
            'channel'   => $channel,
            'event'     => $event,
            'recipient' => $recipient,
            'status'    => $status,
            'message'   => $message,
        ])->save();
    }
}
