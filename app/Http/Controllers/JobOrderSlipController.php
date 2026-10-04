<?php

namespace App\Http\Controllers;

use App\Models\JobOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The transaction slip the customer takes home at intake.
 *
 * A browser page sized for an 80mm thermal printer, not a PDF: the counter
 * prints it straight from the tab it opens in, without a download and a
 * second application in between.
 */
class JobOrderSlipController extends Controller
{
    public function __invoke(Request $request, JobOrder $jobOrder): View
    {
        // Route middleware already limits this to administrators and counter
        // staff; the scope keeps the rule in one place if that ever widens.
        abort_unless(
            JobOrder::query()->visibleTo($request->user())->whereKey($jobOrder->id)->exists(),
            403,
        );

        // The debug toolbar injects itself into every HTML page when debug
        // is on, and would come out of the thermal printer with the slip.
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        $jobOrder->load(['receivedBy', 'parts', 'services']);

        return view('job-orders.slip', [
            'jobOrder' => $jobOrder,
            'total' => $jobOrder->amountDue() > 0 ? $jobOrder->amountDue() : $jobOrder->lineTotal(),
            'paid' => $jobOrder->amountPaid(),
            'autoPrint' => ! $request->boolean('preview'),
        ]);
    }
}
