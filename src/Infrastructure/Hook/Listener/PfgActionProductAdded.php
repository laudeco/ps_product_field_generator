<?php

namespace PsProductFieldGenerator\Infrastructure\Hook\Listener;

use CombinationCore;
use ProductCore;

final class PfgActionProductAdded
{

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var bool
     */
    private $force = false;

    public static function create(): self
    {
        return new self();
    }

    public function setPrefix(string $prefix = ''): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function force(): self
    {
        $this->force = true;
        return $this;
    }

    public function execute(int $productId = null, ProductCore $product = null)
    {
        if (null === $productId || null === $product) {
            return;
        }

        $updated = false;

        if ($this->force || null === $product->ean13 || empty($product->ean13)) {
            $product->ean13 = $this->generateEan13($productId);
            $updated = true;
        }

        if ($this->force || null === $product->reference || empty($product->reference)) {
            $product->reference = $this->generateReference($productId);
            $updated = true;
        }

        if (!$updated) {
            return;
        }

        $product->update();
    }

    public function executeForCombination(CombinationCore $combination = null)
    {
        if (null === $combination) {
            return;
        }

        $updated = false;

        if ($this->force || null === $combination->ean13 || empty($combination->ean13)) {
            $combination->ean13 = $this->generateEan13((int)($combination->id_product.''.$combination->id));
            $updated = true;
        }

        if ($this->force || null === $combination->reference || empty($combination->reference)) {
            $combination->reference = $this->generateReference((int)($combination->id_product.''.$combination->id));
            $updated = true;
        }

        if (!$updated) {
            return;
        }

        $combination->update();
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

        return $this->prefix . str_pad($productId, 12, 0);
    }
}