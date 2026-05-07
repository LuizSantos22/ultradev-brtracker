<?php
class UltraDev_BRTracker_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ENABLED          = 'brtracker/general/enabled';
    const XML_PATH_WA_ENABLED       = 'brtracker/whatsapp/enabled';
    const XML_PATH_WA_PROVIDER      = 'brtracker/whatsapp/provider';
    const XML_PATH_WA_API_URL       = 'brtracker/whatsapp/api_url';
    const XML_PATH_WA_API_KEY       = 'brtracker/whatsapp/api_key';
    const XML_PATH_WA_INSTANCE      = 'brtracker/whatsapp/instance';
    const XML_PATH_WA_PHONE_FIELD   = 'brtracker/whatsapp/phone_field';

    public function isEnabled($storeId = null): bool
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ENABLED, $storeId);
    }

    public function isWhatsappEnabled($storeId = null): bool
    {
        return $this->isEnabled($storeId)
            && Mage::getStoreConfigFlag(self::XML_PATH_WA_ENABLED, $storeId);
    }

    public function getWaConfig($storeId = null): array
    {
        return [
            'provider' => Mage::getStoreConfig(self::XML_PATH_WA_PROVIDER, $storeId),
            'api_url'  => rtrim((string)Mage::getStoreConfig(self::XML_PATH_WA_API_URL, $storeId), '/'),
            'api_key'  => Mage::getStoreConfig(self::XML_PATH_WA_API_KEY, $storeId),
            'instance' => Mage::getStoreConfig(self::XML_PATH_WA_INSTANCE, $storeId),
        ];
    }

    /**
     * Monta a URL de rastreio para um carrier_code.
     * Hierarquia: Frenet-pattern > Melhor Envio-pattern > mapa por código > fallback Correios
     */
    public function buildTrackingUrl(string $carrierCode, string $trackingCode, $storeId = null): string
    {
        $map = $this->_getCarrierUrlMap($storeId);

        // Normaliza código para lookup
        $key = strtolower($carrierCode);

        // Prefixos conhecidos vindos de módulos de frete
        $prefixMap = [
            'frenet'     => 'frenet_url',
            'melhorenvio'=> 'melhorenvio_url',
            'correios'   => 'correios_url',
            'jadlog'     => 'jadlog_url',
            'loggi'      => 'loggi_url',
            'totalexpress'=> 'totalexpress_url',
        ];

        $urlTemplate = null;
        foreach ($prefixMap as $prefix => $cfgKey) {
            if (strpos($key, $prefix) !== false) {
                $urlTemplate = $map[$cfgKey] ?? null;
                break;
            }
        }

        // Tenta custom carriers (JSON)
        if (!$urlTemplate) {
            $custom = $this->_getCustomCarriers($storeId);
            foreach ($custom as $name => $tpl) {
                if (strpos($key, strtolower($name)) !== false) {
                    $urlTemplate = $tpl;
                    break;
                }
            }
        }

        // Fallback: Correios (cobre a maioria dos casos no BR)
        if (!$urlTemplate) {
            $urlTemplate = $map['correios_url'] ?? 'https://www.linkcorreios.com.br/?id={{code}}';
        }

        return str_replace('{{code}}', urlencode($trackingCode), $urlTemplate);
    }

    /**
     * Normaliza telefone brasileiro para formato E.164 sem o +
     * Ex: (11) 98403-9303 → 5511984039303
     * Celulares BR: DDD (2 dígitos) + 9 + 8 dígitos = 11 dígitos locais
     */
    public function normalizeBrazilianPhone(string $phone): ?string
    {
        // Remove tudo que não é dígito
        $digits = preg_replace('/\D/', '', $phone);

        // Já tem DDI 55
        if (strlen($digits) === 13 && substr($digits, 0, 2) === '55') {
            return $digits;
        }

        // 11 dígitos locais: DDD + 9 + 8 dígitos (celular)
        if (strlen($digits) === 11) {
            $ddd   = substr($digits, 0, 2);
            $local = substr($digits, 2);
            // garante que o 9º dígito existe (celular)
            if (substr($local, 0, 1) === '9') {
                return '55' . $digits;
            }
        }

        // 10 dígitos: DDD + fixo (não é celular — tenta mesmo assim)
        if (strlen($digits) === 10) {
            return '55' . $digits;
        }

        // 9 dígitos sem DDD: não há DDD para inferir, retorna null
        if (strlen($digits) === 9) {
            return null;
        }

        // Comprimento inesperado
        return strlen($digits) >= 10 ? '55' . ltrim($digits, '0') : null;
    }

    /**
     * Interpola variáveis em uma mensagem WhatsApp
     */
    public function interpolateMessage(string $template, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $template = str_replace('{{' . $k . '}}', $v, $template);
        }
        return $template;
    }

    // ── privados ────────────────────────────────────────────────────

    protected function _getCarrierUrlMap($storeId = null): array
    {
        return [
            'correios_url'     => Mage::getStoreConfig('brtracker/carriers/correios_url', $storeId),
            'frenet_url'       => Mage::getStoreConfig('brtracker/carriers/frenet_url', $storeId),
            'melhorenvio_url'  => Mage::getStoreConfig('brtracker/carriers/melhorenvio_url', $storeId),
            'jadlog_url'       => Mage::getStoreConfig('brtracker/carriers/jadlog_url', $storeId),
            'loggi_url'        => Mage::getStoreConfig('brtracker/carriers/loggi_url', $storeId),
            'totalexpress_url' => Mage::getStoreConfig('brtracker/carriers/totalexpress_url', $storeId),
        ];
    }

    protected function _getCustomCarriers($storeId = null): array
    {
        $raw = (string)Mage::getStoreConfig('brtracker/carriers/custom_carriers', $storeId);
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
