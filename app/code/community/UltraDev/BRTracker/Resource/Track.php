<?php
class UltraDev_BRTracker_Model_Resource_Track extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('brtracker/track', 'id');
    }
}
