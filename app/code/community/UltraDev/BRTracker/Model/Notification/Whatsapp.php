<?php
class UltraDev_BRTracker_Model_Notification_Whatsapp
{
    protected static $_msgConfigMap = [
        'shipped'          => 'brtracker/whatsapp/msg_shipped',
        'in_transit'       => 'brtracker/whatsapp/msg_in_transit',
        'out_for_delivery' => 'brtracker/whatsapp/msg_out_for_delivery',
        'delivered'        => 'brtracker/whatsapp/msg_delivered',
        'exception'        => 'brtracker/whatsapp/msg_exception',
    ];

    public function send(
        UltraDev_BRTracker_Model_Track $track,
        string $event,
        Mage_Sales_Model_Order $order
    ): bool {
        if ($track->wasWaSent($event)) {
            return false;
        }

        $phone = $track->getCustomerPhone();
        if (empty($phone)) {
            return false;
        }

        $storeId  = (int)$order->getStoreId();
        $helper   = Mage::helper('brtracker');
        $cfg      = $helper->getWaConfig($storeId);
        $tpl      = Mage::getStoreConfig(self::$_msgConfigMap[$event] ?? '', $storeId);

        if (empty($tpl)) {
            return false;
        }

        $message = $helper->interpolateMessage($tpl, [
            'customer_name' => $order->getCustomerName(),
            'order_number'  => $order->getIncrementId(),
            'carrier_name'  => $track->getCarrierName(),
            'tracking_code' => $track->getTrackingCode(),
            'tracking_url'  => $track->getTrackingUrl(),
        ]);

        try {
            $result = $this->_dispatch($cfg, $phone, $message);
            $track->markWaSent($event)->save();
            $this->_log($track, $order, $event, 'sent', $phone);
            return $result;
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_log($track, $order, $event, 'failed', $phone, $e->getMessage());
            return false;
        }
    }

    /**
     * Despacha mensagem de acordo com o provider configurado
     */
    protected function _dispatch(array $cfg, string $phone, string $message): bool
    {
        switch ($cfg['provider']) {
            case 'evolution':
                return $this->_sendEvolution($cfg, $phone, $message);
            case 'zapi':
                return $this->_sendZapi($cfg, $phone, $message);
            case '1msg':
                return $this->_send1msg($cfg, $phone, $message);
            default:
                throw new Exception("BRTracker: provider '{$cfg['provider']}' não suportado.");
        }
    }

    // ── Evolution API ────────────────────────────────────────────────
    protected function _sendEvolution(array $cfg, string $phone, string $message): bool
    {
        $url  = "{$cfg['api_url']}/message/sendText/{$cfg['instance']}";
        $body = json_encode(['number' => $phone, 'text' => $message]);
        return $this->_httpPost($url, $body, ['apikey: ' . $cfg['api_key']]);
    }

    // ── Z-API ────────────────────────────────────────────────────────
    protected function _sendZapi(array $cfg, string $phone, string $message): bool
    {
        $url  = "{$cfg['api_url']}/instances/{$cfg['instance']}/token/{$cfg['api_key']}/send-text";
        $body = json_encode(['phone' => $phone, 'message' => $message]);
        return $this->_httpPost($url, $body, ['Content-Type: application/json']);
    }

    // ── 1msg.io ──────────────────────────────────────────────────────
    protected function _send1msg(array $cfg, string $phone, string $message): bool
    {
        $url  = "{$cfg['api_url']}/send";
        $body = json_encode(['phone' => $phone, 'body' => $message]);
        return $this->_httpPost($url, $body, [
            'Content-Type: application/json',
            'x-maytapi-key: ' . $cfg['api_key'],
        ]);
    }

    protected function _httpPost(string $url, string $body, array $headers): bool
    {
        $client = new Varien_Http_Client($url);
        $client->setMethod(Zend_Http_Client::POST);
        $client->setHeaders(array_merge(['Content-Type: application/json'], $headers));
        $client->setRawData($body, 'application/json');
        $response = $client->request();

        if (!$response->isSuccessful()) {
            throw new Exception("BRTracker WA HTTP error {$response->getStatus()}: {$response->getBody()}");
        }
        return true;
    }

    protected function _log(
        UltraDev_BRTracker_Model_Track $track,
        Mage_Sales_Model_Order $order,
        string $event,
        string $status,
        string $recipient,
        string $message = ''
    ): void {
        Mage::getModel('brtracker/log')->setData([
            'track_id'  => $track->getId(),
            'order_id'  => $order->getId(),
            'channel'   => 'whatsapp',
            'event'     => $event,
            'recipient' => $recipient,
            'status'    => $status,
            'message'   => $message,
        ])->save();
    }
}
