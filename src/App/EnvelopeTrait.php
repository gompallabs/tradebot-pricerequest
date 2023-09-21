<?php

namespace App\App;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

trait EnvelopeTrait
{
    public function handle(Envelope $envelope): array
    {
        $handledStamp = $envelope->last(HandledStamp::class);

        return $handledStamp->getResult();
    }
}
