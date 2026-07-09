<?php
declare(strict_types=1);

namespace Kkkonrad\Rma\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_ENABLED             = 'kkkonrad_rma/general/enabled';
    public const XML_PATH_RETURN_WINDOW_DAYS  = 'kkkonrad_rma/general/return_window_days';
    public const XML_PATH_MAX_FILE_SIZE_MB    = 'kkkonrad_rma/general/max_file_size_mb';
    public const XML_PATH_ALLOWED_EXTENSIONS  = 'kkkonrad_rma/general/allowed_file_extensions';
    public const XML_PATH_AUTO_CANCEL_DAYS    = 'kkkonrad_rma/automation/auto_cancel_days';
    public const XML_PATH_EMAIL_CREATED       = 'kkkonrad_rma/email/created_template';
    public const XML_PATH_EMAIL_STATUS_CHANGED = 'kkkonrad_rma/email/status_changed_template';
    public const XML_PATH_EMAIL_LABEL_UPLOADED = 'kkkonrad_rma/email/label_uploaded_template';
    public const XML_PATH_EMAIL_SENDER        = 'kkkonrad_rma/email/sender';
    public const XML_PATH_ALLOWED_ORDER_STATUSES = 'kkkonrad_rma/general/allowed_order_statuses';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getReturnWindowDays(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_RETURN_WINDOW_DAYS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMaxFileSizeMb(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_MAX_FILE_SIZE_MB,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return string[]
     */
    public function getAllowedExtensions(?int $storeId = null): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_EXTENSIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return array_map('trim', explode(',', (string) $value));
    }

    public function getAutoCancelDays(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_AUTO_CANCEL_DAYS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getEmailSender(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_SENDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getCreatedEmailTemplate(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_CREATED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getStatusChangedEmailTemplate(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_STATUS_CHANGED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getLabelUploadedEmailTemplate(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_LABEL_UPLOADED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return string[]
     */
    public function getAllowedOrderStatuses(?int $storeId = null): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_ORDER_STATUSES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if (!$value) {
            return ['complete'];
        }
        return array_filter(array_map('trim', explode(',', (string) $value)));
    }
}
