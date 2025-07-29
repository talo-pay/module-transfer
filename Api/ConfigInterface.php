<?php
/**
 * Talopay_Transfer
 *
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Api;

use Magento\Sales\Api\Data\OrderInterface;

interface ConfigInterface
{
    public const APP_ID = 'magento_talopay_transfer';
    public const KEY_TALOPAY_TRANSFER_ID = 'transfer_id';
    public const KEY_TALOPAY_TRANSFER_URL = 'transfer_url';
    public const ORDER_ADDITIONAL_KEY = 'talopay_transfer';
    public const PAYMENT_CODE = 'talopay_transfer';
    public const SAFE_PLACEHOLDER = '******';
    public const URL_PRODUCTION = 'https://api.talo.com.ar';
    public const URL_SANDBOX = 'https://sandbox-api.talo.com.ar';
    public const XPATH_DEBUG = 'debug';
    public const XPATH_SEND_INSTRUCTIONS = 'send_instructions';
    public const XPATH_SEND_INSTRUCTIONS_TEMPLATE = 'send_instructions_template';
    public const XPATH_SEND_INSTRUCTIONS_GUEST_TEMPLATE = 'send_instructions_guest_template';
    public const XPATH_ENVIRONMENT = 'environment';
    public const XPATH_INSTRUCTIONS = 'instructions';
    public const XPATH_ORDER_STATUS = 'order_status';
    public const XPATH_PRODUCTION_CLIENT_ID = 'production_credentials/client_id';
    public const XPATH_PRODUCTION_CLIENT_SECRET = 'production_credentials/client_secret';
    public const XPATH_PRODUCTION_USER_ID = 'production_credentials/user_id';
    public const XPATH_REDIRECT = 'redirect';
    public const XPATH_SANDBOX_CLIENT_ID = 'sandbox_credentials/client_id';
    public const XPATH_SANDBOX_CLIENT_SECRET = 'sandbox_credentials/client_secret';
    public const XPATH_SANDBOX_USER_ID = 'sandbox_credentials/user_id';
    public const XPATH_NOTIFICATION_EMAIL_STATUS = 'notifications/email_enabled';
    public const XPATH_NOTIFICATION_EMAIL_TEMPLATE = 'notifications/email_notification_template';
    public const XPATH_STATUS_PAY = 'status_pay';
    public const XPATH_STATUS_REJECTED = 'status_rejected';
    public const XPATH_TALOPAY_APP_ID = 'app_id';
    public const XPATH_TALOPAY_STORE_ID = 'store_id';

    /**
     * @param string|null $environment
     * @return string
     */
    public function getClientId(?string $environment = null): string;

    /**
     * @param string|null $environment
     * @return string
     */
    public function getClientSecret(?string $environment = null);

    /**
     * @return string
     */
    public function getEnvironment(): string;

    /**
     * @return string
     */
    public function getInstructions();

    /**
     * @return string
     */
    public function getInstructionsGuestTemplateId(): string;

    /**
     * @return string
     */
    public function getInstructionsTemplateId(): string;

    /**
     * Return the current notification email status
     *
     * @return string
     */
    public function getNotificationEmailStatus(): string;

    /**
     * @return string
     */
    public function getNotificationEmailTemplate(): string;

    /**
     * @param OrderInterface $order
     * @return string
     */
    public function getOrderKey(OrderInterface $order): string;

    /**
     * @return string
     */
    public function getOrderStatus(): string;

    /**
     * @return string
     */
    public function getStatusPay();

    /**
     * @return string
     */
    public function getStatusRejected();

    /**
     * @return string
     */
    public function getTaloPayAppId(): string;

    /**
     * @return string
     */
    public function getTaloPayStoreId(): string;

    /**
     * @param string|null $environment
     * @return string
     */
    public function getUrl(?string $environment = null): string;

    /**
     * @param string|null $environment
     * @return string
     */
    public function getUserId(?string $environment = null): string;

    /**
     * @return bool
     */
    public function isDebugMode(): bool;

    /**
     * @return bool
     */
    public function mustRedirect(): bool;

    /**
     * @return bool
     */
    public function mustSendInstructions(): bool;
}
