<?php
class UltraDev_BRTracker_Model_Adminhtml_System_Config_Source_WhatsappProvider
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'evolution', 'label' => 'Evolution API (self-hosted, gratuito)'],
            ['value' => 'zapi',      'label' => 'Z-API (zapi.io)'],
            ['value' => '1msg',      'label' => '1msg.io'],
        ];
    }
}
