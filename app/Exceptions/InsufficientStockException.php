<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public ?string $productName;
    public int $requestedQty;
    public int $availableQty;
    public int $shortageQty;

    public function __construct(
        string $message,
        ?string $productName = null,
        int $requestedQty = 0,
        int $availableQty = 0,
        int $shortageQty = 0,
        int $code = 422,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->productName = $productName;
        $this->requestedQty = $requestedQty;
        $this->availableQty = $availableQty;
        $this->shortageQty = $shortageQty;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function getRequestedQty(): int
    {
        return $this->requestedQty;
    }

    public function getAvailableQty(): int
    {
        return $this->availableQty;
    }

    public function getShortageQty(): int
    {
        return $this->shortageQty;
    }
}
