<?php

namespace App\Http\Controllers;

use App\Models\JobOrder;
use App\Models\RepairQuoteRequest;
use App\Services\JobOrders\JobOrderWorkflow;
use App\Services\TrackingCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerPortalController extends Controller
{
    /**
     * Show the customer portal landing page.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('welcome')->withFragment('track-repair');
    }

    /**
     * Lookup job order by job order number.
     */
    public function lookup(Request $request): RedirectResponse
    {
        $request->validate([
            'job_order_number' => 'required|string',
        ]);

        $input = trim($request->string('job_order_number'));

        // Either identifier works: the short code from the claim stub or the
        // text message, or the full job order number from the paperwork.
        $jobOrder = JobOrder::query()
            ->where('job_order_number', $input)
            ->orWhere('tracking_code', TrackingCode::normalise($input))
            ->first();

        if (! $jobOrder || ! $jobOrder->portal_token) {
            return redirect()->route('customer.portal.index')
                ->withFragment('track-repair')
                ->with('error', 'We could not find that repair. Please check your tracking code or job order number and try again.');
        }

        return redirect()->route('customer.portal.view', ['token' => $jobOrder->portal_token]);
    }

    /**
     * Show job order details via secure token.
     */
    public function view(string $token): View
    {
        $jobOrder = JobOrder::where('portal_token', $token)
            ->with(['receivedBy', 'assignedTo', 'parts', 'services'])
            ->firstOrFail();

        // Each line carries the name and price agreed at intake, so the
        // totals are a sum rather than a rebuild — and a later catalogue
        // change cannot alter what this customer was quoted.
        return view('customer-portal.view', [
            'jobOrder' => $jobOrder,
            'partsTotal' => $jobOrder->partsTotal(),
            'laborTotal' => $jobOrder->laborTotal(),
            'estimatedTotal' => $jobOrder->lineTotal(),
        ]);
    }

    /**
     * Approve quote via customer portal.
     */
    public function approve(string $token): RedirectResponse
    {
        $jobOrder = JobOrder::where('portal_token', $token)->firstOrFail();

        // Only allow approval if status is awaiting_approval
        if ($jobOrder->status->value !== 'awaiting_approval') {
            return redirect()->route('customer.portal.view', ['token' => $token])
                ->with('error', 'This quote cannot be approved at this time.');
        }

        app(JobOrderWorkflow::class)->approveByCustomer($jobOrder);

        return redirect()->route('customer.portal.view', ['token' => $token])
            ->with('success', 'Thank you! Your repair quote has been approved. We will begin work shortly.');
    }

    /**
     * Show repair quote request details via secure token.
     */
    public function quoteView(string $token): View
    {
        $quoteRequest = RepairQuoteRequest::where('portal_token', $token)->firstOrFail();

        return view('customer-portal.quote-view', [
            'quoteRequest' => $quoteRequest,
        ]);
    }

    /**
     * Accept a repair quote via customer portal.
     */
    public function quoteAccept(string $token): RedirectResponse
    {
        $quoteRequest = RepairQuoteRequest::where('portal_token', $token)->firstOrFail();

        if ($quoteRequest->status !== 'quoted') {
            return redirect()->route('customer.portal.quote', ['token' => $token])
                ->with('error', 'This quote cannot be accepted at this time.');
        }

        $quoteRequest->update(['status' => 'approved']);

        return redirect()->route('customer.portal.quote', ['token' => $token])
            ->with('success', 'Thank you! Your repair quote has been accepted. We will contact you to arrange the repair.');
    }

    /**
     * Decline a repair quote via customer portal.
     */
    public function quoteDecline(string $token): RedirectResponse
    {
        $quoteRequest = RepairQuoteRequest::where('portal_token', $token)->firstOrFail();

        if ($quoteRequest->status !== 'quoted') {
            return redirect()->route('customer.portal.quote', ['token' => $token])
                ->with('error', 'This quote cannot be declined at this time.');
        }

        $quoteRequest->update(['status' => 'declined']);

        return redirect()->route('customer.portal.quote', ['token' => $token])
            ->with('info', 'The quote has been declined. If you change your mind, feel free to submit a new request.');
    }
}
