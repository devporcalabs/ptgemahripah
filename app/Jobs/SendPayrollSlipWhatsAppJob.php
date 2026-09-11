<?php

namespace App\Jobs;

use App\Services\PayrollSlipWhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPayrollSlipWhatsAppJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly int $payrollPeriodId,
        public readonly string $queueMarker
    ) {
        $this->onQueue('notifications');
    }

    public function handle(PayrollSlipWhatsAppService $service): void
    {
        $service->sendForFinalizedPeriod($this->payrollPeriodId, $this->queueMarker);
    }
}
