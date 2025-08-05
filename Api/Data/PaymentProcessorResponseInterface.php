<?php
/**
 * Talopay_Transfer
 *
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Api\Data;

interface PaymentProcessorResponseInterface
{
    /**
     * Add a new comment
     *
     * @param string|\Magento\Framework\Phrase $comment
     * @return self
     */
    public function addComment(string|\Magento\Framework\Phrase $comment): PaymentProcessorResponseInterface;

    /**
     * @param string[]|\Magento\Framework\Phrase[] $comments
     * @return self
     */
    public function addComments(array $comments): PaymentProcessorResponseInterface;

    /**
     * Return a list of comments to add
     *
     * @return string[]|\Magento\Framework\Phrase[]
     */
    public function getComments(): array;

    /**
     * @return \Magento\Sales\Model\Order\Invoice[]
     */
    public function getInvoicesToNotify(): array;

    /**
     * Return a new state to change to
     *
     * @return string|null
     */
    public function getState(): ?string;

    /**
     * Return a new status to change to
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Return true if anything on the response has changed
     *
     * @return bool
     */
    public function mustSave(): bool;

    /**
     * Set the generated invoices
     *
     * @param \Magento\Sales\Model\Order\Invoice[] $invoices
     * @return PaymentProcessorResponseInterface
     */
    public function setInvoicesToNotify(array $invoices): PaymentProcessorResponseInterface;

    /**
     * Force status to must save
     *
     * @param bool $mustSave
     * @return self
     */
    public function setMustSave(bool $mustSave): PaymentProcessorResponseInterface;

    /**
     * Set the state to be change
     *
     * @param string|null $state
     * @return self
     */
    public function setState(?string $state): PaymentProcessorResponseInterface;

    /**
     * Set the status to be change
     *
     * @param string|null $status
     * @return self
     */
    public function setStatus(?string $status): PaymentProcessorResponseInterface;
}
