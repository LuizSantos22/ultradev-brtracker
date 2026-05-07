<?php
class UltraDev_BRTracker_Block_Adminhtml_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('brtrackerLogGrid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('brtracker/log')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id',        ['header' => 'ID',          'index' => 'id',        'width' => '60px']);
        $this->addColumn('order_id',  ['header' => 'Pedido ID',   'index' => 'order_id',  'width' => '80px']);
        $this->addColumn('channel',   ['header' => 'Canal',       'index' => 'channel',   'width' => '80px']);
        $this->addColumn('event',     ['header' => 'Evento',      'index' => 'event',     'width' => '120px']);
        $this->addColumn('recipient', ['header' => 'Destinatário','index' => 'recipient']);
        $this->addColumn('status',    ['header' => 'Status',      'index' => 'status',    'width' => '80px']);
        $this->addColumn('message',   ['header' => 'Mensagem/Erro','index' => 'message']);
        $this->addColumn('created_at',['header' => 'Data',        'index' => 'created_at','type' => 'datetime','width' => '150px']);
        return parent::_prepareColumns();
    }
}
