<?php
class UltraDev_BRTracker_Adminhtml_BrtrackerLogController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction(): void
    {
        $this->loadLayout();
        $this->_title($this->__('BRTracker'))->_title($this->__('Log de Notificações'));
        $this->_setActiveMenu('ultradev/brtracker/log');
        $this->getLayout()->getBlock('content')
            ->append($this->getLayout()->createBlock('brtracker_adminhtml/log_grid'));
        $this->renderLayout();
    }

    protected function _isAllowed(): bool
    {
        return Mage::getSingleton('admin/session')->isAllowed('ultradev/brtracker/log');
    }
}
