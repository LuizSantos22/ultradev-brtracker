<?php
/**
 * UltraDev_BRTracker
 * Install script — OpenMage 20.x / PHP 8.2+
 * Substitui o padrão legado mysql4-install-*.php
 *
 * @var Mage_Core_Model_Resource_Setup $installer
 */
$installer = $this;
$installer->startSetup();

$conn = $installer->getConnection();

// ── ultradev_brt_tracks ──────────────────────────────────────────────────────
$tableTracks = $installer->getTable('brtracker/track');

if ($conn->isTableExists($tableTracks)) {
    $conn->dropTable($tableTracks);
}

$table = $conn->newTable($tableTracks)
    ->addColumn(
        'id',
        Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
        'Primary Key'
    )
    ->addColumn(
        'order_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        ['unsigned' => true, 'nullable' => false],
        'Sales Order ID'
    )
    ->addColumn(
        'shipment_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        ['unsigned' => true, 'nullable' => true],
        'Sales Shipment ID'
    )
    ->addColumn(
        'tracking_code',
        Varien_Db_Ddl_Table::TYPE_VARCHAR, 100,
        ['nullable' => false],
        'Tracking Code'
    )
    ->addColumn(
        'carrier_code',
        Varien_Db_Ddl_Table::TYPE_VARCHAR, 100,
        ['nullable' => true],
        'Carrier Code (e.g. correios, frenet)'
    )
    ->addColumn(
        'carrier_name',
        Varien_Db_Ddl_Table::TYPE_VARCHAR, 255,
        ['nullable' => true],
        'Carrier Display Name'
    )
    ->addColumn(
        'tracking_url',
        Varien_Db_Ddl_Table::TYPE_TEXT, null,
        ['nullable' => true],
        'Full Tracking URL'
    )
    ->addColumn(
        'customer_email',
        Varien_Db_Ddl_Table::TYPE_VARCHAR, 255,
        ['nullable' => true],
        'Customer Email'
    )
    ->addColumn(
        'customer_phone',
        Varien_Db_Ddl_Table::TYPE_VARCHAR, 30,
        ['nullable' => true],
        'Customer Phone E.164 (e.g. 5511984039303)'
    )
    ->addColumn(
        'status',
        Varien_Db_Ddl_Table::TYPE_VARCHAR, 50,
        ['nullable' => false, 'default' => 'pending'],
        'Current Tracking Status: pending|shipped|in_transit|out_for_delivery|delivered|exception'
    )
    ->addColumn(
        'tracking_history',
        Varien_Db_Ddl_Table::TYPE_TEXT, null,
        ['nullable' => true],
        'JSON: array of {date, status, description, location} from carrier API'
    )
    ->addColumn(
        'email_sent',
        Varien_Db_Ddl_Table::TYPE_TEXT, null,
        ['nullable' => true],
        'JSON: list of events already notified by email'
    )
    ->addColumn(
        'wa_sent',
        Varien_Db_Ddl_Table::TYPE_TEXT, null,
        ['nullable' => true],
        'JSON: list of events already notified by WhatsApp'
    )
    ->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null,
        ['nullable' => false, 'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT],
        'Created At'
    )
    ->addColumn(
        'updated_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null,
        ['nullable' => false, 'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT_UPDATE],
        'Updated At'
    )
    ->addIndex(
        $installer->getIdxName($tableTracks, ['order_id']),
        ['order_id']
    )
    ->addIndex(
        $installer->getIdxName($tableTracks, ['tracking_code']),
        ['tracking_code']
    )
    ->addIndex(
        $installer->getIdxName($tableTracks, ['status']),
        ['status']
    )
    ->addIndex(
        $installer->getIdxName($tableTracks, ['updated_at']),
        ['updated_at']
    )
    ->setComment('UltraDev BRTracker — Tracking Records');

$conn->createTable($table);

// ── ultradev_brt_notification_log ────────────────────────────────────────────
$tableLog = $installer->getTable('brtracker/log');

if ($conn->isTableExists($tableLog)) {
    $conn->dropTable($tableLog);
}

$log = $conn->newTable($tableLog)
    ->addColumn(
        'id',
        Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
        'Primary Key'
    )
    ->addColumn(
        'track_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        ['unsigned' => true, 'nullable' => false],
        'FK to ultradev_brt_tracks.id'
    )
    ->addColumn(
        'order_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        ['unsigned' => true, 'nullable' => false],
        'Sales Order ID'
    )
    ->addColumn(
        'channel',
        Varien_Db_Ddl_Table::TYPE_VARCHAR, 20,
        ['nullable' => false],
        'Notification channel: email | whatsapp'
    )
    ->addColumn(
        'event',
        Varien_Db_Ddl_Table::TYPE_VARCHAR, 50,
        ['nullable' => false],
        'Event: shipped|in_transit|out_for_delivery|delivered|exception'
    )
    ->addColumn(
        'recipient',
        Varien_Db_Ddl_Table::TYPE_VARCHAR, 255,
        ['nullable' => true],
        'Email address or phone number'
    )
    ->addColumn(
        'status',
        Varien_Db_Ddl_Table::TYPE_VARCHAR, 20,
        ['nullable' => false, 'default' => 'sent'],
        'Delivery status: sent | failed'
    )
    ->addColumn(
        'message',
        Varien_Db_Ddl_Table::TYPE_TEXT, null,
        ['nullable' => true],
        'Error message or delivery info'
    )
    ->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null,
        ['nullable' => false, 'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT],
        'Created At'
    )
    ->addIndex(
        $installer->getIdxName($tableLog, ['track_id']),
        ['track_id']
    )
    ->addIndex(
        $installer->getIdxName($tableLog, ['order_id']),
        ['order_id']
    )
    ->addIndex(
        $installer->getIdxName($tableLog, ['channel']),
        ['channel']
    )
    ->addIndex(
        $installer->getIdxName($tableLog, ['created_at']),
        ['created_at']
    )
    ->setComment('UltraDev BRTracker — Notification Log');

$conn->createTable($log);

$installer->endSetup();
