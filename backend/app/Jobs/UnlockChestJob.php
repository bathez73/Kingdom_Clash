<?php

namespace App\Jobs;

use App\Models\Chest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UnlockChestJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Chest $chest) {}

    public function handle(): void
    {
        $this->chest->update(['status' => 'ready']);
    }
}
