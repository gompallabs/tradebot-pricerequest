<?php

namespace App\Domain\DataProcessor\TickDataTransformers;

interface TransformerInterface
{
    public function transform(\ArrayIterator $tickData): \ArrayIterator;

    public function setNext(TransformerInterface $transformer): TransformerInterface;
}
