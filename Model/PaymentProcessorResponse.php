<?php
/**
 * Talopay_Transfer
 *
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Model;

use Magento\Framework\Phrase;
use Magento\Sales\Model\Order\Invoice;
use TaloPay\Transfer\Api\Data\PaymentProcessorResponseInterface;

class PaymentProcessorResponse implements PaymentProcessorResponseInterface
{
    /**
     * @var Invoice[]
     */
    private $invoices = [];
    /**
     * @var Phrase[]|string[]
     */
    private $comments = [];
    /**
     * @var string|null
     */
    private $status = null;
    /**
     * @var string|null
     */
    private $state = null;
    /**
     * @var bool
     */
    private $mustSave = false;

    /**
     * @inheritDoc
     */
    public function addComments(array $comments): PaymentProcessorResponseInterface
    {
        foreach ($comments as $comment) {
            $this->addComment($comment);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addComment(Phrase|string $comment): PaymentProcessorResponseInterface
    {
        $this->comments[] = $comment;
        $this->mustSave = true;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getComments(): array
    {
        return $this->comments;
    }

    /**
     * @inheritDoc
     */
    public function getInvoicesToNotify(): array
    {
        return $this->invoices;
    }

    /**
     * @inheritDoc
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @inheritDoc
     */
    public function setState(?string $state): PaymentProcessorResponseInterface
    {
        if ($this->state !== $state) {
            $this->mustSave = true;
        }
        $this->state = $state;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @inheritDoc
     */
    public function setStatus(?string $status): PaymentProcessorResponseInterface
    {
        if ($this->status !== $status) {
            $this->mustSave = true;
        }
        $this->status = $status;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function mustSave(): bool
    {
        return $this->mustSave;
    }

    /**
     * @inheritDoc
     */
    public function setInvoicesToNotify(array $invoices): PaymentProcessorResponseInterface
    {
        $this->invoices = $invoices;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setMustSave(bool $mustSave): PaymentProcessorResponseInterface
    {
        $this->mustSave = $mustSave;
        return $this;
    }
}
