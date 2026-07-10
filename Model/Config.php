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
    public const XML_PATH_EMAIL_LABEL_UPLOADED        = 'kkkonrad_rma/email/label_uploaded_template';
    public const XML_PATH_EMAIL_SENDER                = 'kkkonrad_rma/email/sender';
    public const XML_PATH_EMAIL_ADMIN_NOTIFICATION    = 'kkkonrad_rma/email/admin_notification_email';
    public const XML_PATH_EMAIL_ADMIN_TEMPLATE        = 'kkkonrad_rma/email/admin_notification_template';
    public const XML_PATH_EMAIL_MESSAGE_ADDED_TEMPLATE = 'kkkonrad_rma/email/message_added_template';
    public const XML_PATH_ALLOWED_ORDER_STATUSES      = 'kkkonrad_rma/general/allowed_order_statuses';
    public const XML_PATH_CUSTOMERS_CAN_CANCEL_RMA    = 'kkkonrad_rma/general/customers_can_cancel_rma';
    public const XML_PATH_EXCLUDED_SKUS               = 'kkkonrad_rma/general/excluded_skus';
    public const XML_PATH_CUSTOM_CSS                  = 'kkkonrad_rma/general/custom_css';
    public const XML_PATH_CUSTOM_JS                   = 'kkkonrad_rma/general/custom_js';
    public const XML_PATH_ALLOW_GUEST_RMA            = 'kkkonrad_rma/general/allow_guest_rma';



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
     * Fix 8: Admin notification email address (empty = disabled).
     */
    public function getAdminNotificationEmail(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_ADMIN_NOTIFICATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Fix 8: Admin notification email template identifier.
     */
    public function getAdminNotificationEmailTemplate(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_ADMIN_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Fix 9: Customer notification template when admin sends a message.
     */
    public function getMessageAddedEmailTemplate(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_MESSAGE_ADDED_TEMPLATE,
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
        if ($value === null) {
            return ['complete'];
        }
        return array_filter(array_map('trim', explode(',', (string) $value)));
    }

    /**
     * Whether customers are allowed to cancel their own RMA requests.
     */
    public function canCustomerCancelRma(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CUSTOMERS_CAN_CANCEL_RMA,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Returns the list of product SKUs excluded from RMA (uppercase, trimmed).
     *
     * @return string[]
     */
    public function getExcludedSkus(?int $storeId = null): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_EXCLUDED_SKUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if (!$value) {
            return [];
        }
        return array_filter(array_map(
            static fn(string $sku) => strtoupper(trim($sku)),
            explode(',', (string) $value)
        ));
    }

    /**
     * Get custom CSS.
     */
    public function getCustomCss(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CUSTOM_CSS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get custom JS.
     */
    public function getCustomJs(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CUSTOM_JS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Whether guest RMA is allowed.
     */
    public function allowGuestRma(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ALLOW_GUEST_RMA,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}


