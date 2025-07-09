<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Payment\Gateway\Config\Config as MagentoConfig;
use Magento\Sales\Api\Data\OrderInterface;
use TaloPay\Transfer\Api\ConfigInterface;
use TaloPay\Transfer\Model\Config\Source\Environment;

class Config extends MagentoConfig implements ConfigInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
    ) {
        parent::__construct($scopeConfig, ConfigInterface::PAYMENT_CODE);
    }

    /**
     * @inheritDoc
     */
    public function getClientId(?string $environment = null): string
    {
        $environment = $environment ?? $this->getEnvironment();
        return (string)($environment === Environment::ENV_PRODUCTION ?
            $this->getValue(self::XPATH_PRODUCTION_CLIENT_ID) :
            $this->getValue(self::XPATH_SANDBOX_CLIENT_ID));
    }

    /**
     * @inheritDoc
     */
    public function getEnvironment(): string
    {
        return (string)$this->getValue(self::XPATH_ENVIRONMENT);
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(?string $environment = null)
    {
        $environment = $environment ?? $this->getEnvironment();
        return (string)($environment === Environment::ENV_PRODUCTION ?
            $this->getValue(self::XPATH_PRODUCTION_CLIENT_SECRET) :
            $this->getValue(self::XPATH_SANDBOX_CLIENT_SECRET));
    }

    /**
     * @inheritDoc
     */
    public function getInstructions()
    {
        return (string)$this->getValue(self::XPATH_INSTRUCTIONS);
    }

    /**
     * @inheritDoc
     */
    public function getInstructionsGuestTemplateId(): string
    {
        return $this->getValue(self::XPATH_SEND_INSTRUCTIONS_GUEST_TEMPLATE);
    }

    /**
     * @inheritDoc
     */
    public function getInstructionsTemplateId(): string
    {
        return $this->getValue(self::XPATH_SEND_INSTRUCTIONS_TEMPLATE);
    }

    /**
     * @inheritDoc
     */
    public function getOrderKey(OrderInterface $order): string
    {
        return $this->encryptor->hash($order->getProtectCode());
    }

    /**
     * @inheritDoc
     */
    public function getOrderStatus(): string
    {
        return (string)$this->getValue(self::XPATH_ORDER_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function getStatusPay()
    {
        return $this->getValue(self::XPATH_STATUS_PAY);
    }

    /**
     * @inheritDoc
     */
    public function getStatusRejected()
    {
        return $this->getValue(self::XPATH_STATUS_REJECTED);
    }

    /**
     * @inheritDoc
     */
    public function getTaloPayAppId(): string
    {
        return (string)$this->getValue(self::XPATH_TALOPAY_APP_ID);
    }

    /**
     * @inheritDoc
     */
    public function getTaloPayStoreId(): string
    {
        return (string)$this->getValue(self::XPATH_TALOPAY_STORE_ID);
    }

    /**
     * @inheritDoc
     */
    public function getUrl(?string $environment = null): string
    {
        $environment = $environment ?? $this->getEnvironment();
        return $environment === Environment::ENV_PRODUCTION ?
            self::URL_PRODUCTION :
            self::URL_SANDBOX;
    }

    /**
     * @inheritDoc
     */
    public function getUserId(?string $environment = null): string
    {
        $environment = $environment ?? $this->getEnvironment();
        return (string)($environment === Environment::ENV_PRODUCTION ?
            $this->getValue(self::XPATH_PRODUCTION_USER_ID) :
            $this->getValue(self::XPATH_SANDBOX_USER_ID));
    }

    /**
     * @inheritDoc
     */
    public function isDebugMode(): bool
    {
        return !!$this->getValue(self::XPATH_DEBUG);
    }

    /**
     * @inheritDoc
     */
    public function mustRedirect(): bool
    {
        return !!$this->getValue(self::XPATH_REDIRECT);
    }

    /**
     * @inheritDoc
     */
    public function mustSendInstructions(): bool
    {
        return !!$this->getValue(self::XPATH_SEND_INSTRUCTIONS);
    }
}
