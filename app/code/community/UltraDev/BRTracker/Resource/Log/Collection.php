<?php
class UltraDev_BRTracker_Model_Resource_Log_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('brtracker/log');
    }
}
