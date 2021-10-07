<?php

namespace PsProductFieldGenerator\Infrastructure\Hook\Listener;

use PrestaShop\PrestaShop\Core\Domain\Product\Command\UpdateProductDetailsCommand;
use PrestaShop\PrestaShop\Core\Domain\Product\CommandHandler\UpdateProductDetailsHandlerInterface;
use ProductCore;

final class PfgActionProductAdded
{

    /**
     * @var string
     */
    private $prefix;

    public static function create(): self
    {
        return new self();
    }

    public function setPrefix(string $prefix = ''): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function execute(int $productId = null, ProductCore $product = null)
    {
        if (null === $productId || null === $product) {
            return;
        }

        $updated = false;

        if (null === $product->ean13 || empty($product->ean13)) {
            $product->ean13 = $this->generateEan13($productId);
            $updated = true;
        }

        if (null === $product->reference || empty($product->reference)) {
            $product->reference = $this->generateReference($productId);
            $updated = true;
        }

        if (!$updated) {
            return;
        }

        $product->update();
    }

    private function generateEan13($number): string
    {
        $code = '200' . str_pad($number, 9, '0');
        $weightflag = true;
        $sum = 0;
        // Weight for a digit in the checksum is 3, 1, 3.. starting from the last digit.
        // loop backwards to make the loop length-agnostic. The same basic functionality
        // will work for codes of different lengths.
        for ($i = strlen($code) - 1; $i >= 0; $i--) {
            $sum += (int)$code[$i] * ($weightflag ? 3 : 1);
            $weightflag = !$weightflag;
        }
        $code .= (10 - ($sum % 10)) % 10;

        return '' . $code;
    }

    private function generateReference(int $productId = null): string
    {
        if (null === $productId) {
            return '';
        }

        $reference = $this->prefix;
        $reference .= str_pad($reference, 12 - strlen('' . $productId), 0);
        $reference .= $productId;

        return $reference;
    }
}