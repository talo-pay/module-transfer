<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Controller\Adminhtml\Transfer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\StripTags;
use TaloPay\Transfer\Api\ApiClientInterface;
use TaloPay\Transfer\Api\ConfigInterface;

class TestCredentials extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'TaloPay_Transfer::config_talopay_transfer';

    public function __construct(
        Context $context,
        private readonly ApiClientInterface $taloApi,
        private readonly JsonFactory $resultJsonFactory,
        private readonly StripTags $tagFilter,
    ) {
        parent::__construct($context);
    }

    /**
     * Check for connection to server
     *
     * @return Json
     */
    public function execute()
    {
        $result = [
            'success' => false,
            'errorMessage' => '',
        ];
        $options = $this->getRequest()->getParams();

        try {
            $environment = $options['environment'] ?? '';
            $userId = $options[$environment . '_user_id'] ?? '';
            $clientId = $options[$environment . '_client_id'] ?? '';
            $clientSecret = $options[$environment . '_client_secret'] ?? '';
            // If the secret is "saved" then we need to recover the value
            if ($clientSecret === ConfigInterface::SAFE_PLACEHOLDER) {
                $clientSecret = null;
            }

            $response = $this->taloApi->testCredentials(
                $environment,
                $userId,
                $clientId,
                $clientSecret,
            );
            if ($response) {
                $result['success'] = true;
            }
        } catch (LocalizedException $e) {
            $result['errorMessage'] = $e->getMessage();
        } catch (\Exception $e) {
            $message = __($e->getMessage());
            $result['errorMessage'] = $this->tagFilter->filter($message);
        }

        /** @var Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }
}
