<?php
class UltraDev_BRTracker_Model_Adminhtml_System_Config_Source_PhoneField
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'telephone', 'label' => 'Telefone (telephone)'],
            ['value' => 'fax',       'label' => 'Celular / Fax (fax)'],
        ];
    }
}
