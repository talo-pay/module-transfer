<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Controller\Adminhtml\Transfer;

use Exception;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Sales\Model\OrderRepository;
use TaloPay\Transfer\Model\Order\Email\Sender\InstructionSender;

class SendInstructions implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'TaloPay_Transfer::instruction_email';

    /**
     * @param OrderRepository $orderRepository
     * @param RequestInterface $request
     * @param InstructionSender $instructionSender
     * @param RedirectFactory $redirectFactory
     * @param RedirectInterface $redirect
     */
    public function __construct(
        readonly private OrderRepository $orderRepository,
        readonly private RequestInterface $request,
        readonly private InstructionSender $instructionSender,
        readonly private RedirectFactory $redirectFactory,
        readonly private RedirectInterface $redirect,
        readonly private MessageManagerInterface $messageManager,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $orderId = (int)$this->request->getParam('id');

        try {
            $order = $this->orderRepository->get($orderId);
            if ($this->instructionSender->send($order, true)) {
                $this->messageManager->addSuccessMessage(__('Your Instructions has been sent.'));
            } else {
                $this->messageManager->addErrorMessage(__('There was an error sending your Instructions.'));
            }
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        $resultRedirect = $this->redirectFactory->create();
        $resultRedirect->setUrl($this->redirect->getRefererUrl());
        return $resultRedirect;
    }
}
