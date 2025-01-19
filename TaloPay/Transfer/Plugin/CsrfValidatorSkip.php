<?php

namespace TaloPay\Transfer\Plugin;

use Magento\Framework\App\ProductMetadata;

class CsrfValidatorSkip
{
    /**
     * @var ProductMetadata
     */
    protected $productMetadata;

    /**
     * CsrfValidatorSkip constructor.
     *
     * @param ProductMetadata $productMetadata
     */
    public function __construct(ProductMetadata $productMetadata)
    {
        $this->productMetadata = $productMetadata;
    }

    /**
     * Plugin to skip CSRF validation for specific actions
     *
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        if ($this->productMetadata->getVersion() < '2.3.0') {
            $proceed($request, $action);
        }
    }
}
