<?php

namespace App\Http\Controllers;

use App\Models\JobOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CustomerPortalController extends Controller
{
    /**
     * Show the customer portal landing page.
     */
    public function index(): View
    {
        return view('customer-portal.index');
    }
    
    /**
     * Lookup job order by job order number.
     */
    public function lookup(Request $request): RedirectResponse
    {
        $request->validate([
            'job_order_number' => 'required|string',
        ]);
        
        $jobOrder = JobOrder::where('job_order_number', $request->job_order_number)
            ->first();
            
        if (!$jobOrder || !$jobOrder->portal_token) {
            return redirect()->route('customer.portal.index')
                ->withFragment('track-repair')
                ->with('error', 'Job order not found. Please check your job order number and try again.');
        }
        
        return redirect()->route('customer.portal.view', ['token' => $jobOrder->portal_token]);
    }
    
    /**
     * Show job order details via secure token.
     */
    public function view(string $token): View
    {
        $jobOrder = JobOrder::where('portal_token', $token)
            ->with(['receivedBy', 'assignedTo'])
            ->firstOrFail();
            
        // Normalize parts data
        $parts = $jobOrder->parts_needed ?? [];
        if (is_array($parts) && count($parts) > 0) {
            foreach ($parts as $idx => $p) {
                $parts[$idx]['quantity'] = isset($p['quantity']) ? (int) $p['quantity'] : 1;
                
                if (empty($p['part_name']) && !empty($p['part_id'])) {
                    $partModel = \App\Models\Part::find($p['part_id']);
                    if ($partModel) {
                        $parts[$idx]['part_name'] = $partModel->name;
                        $parts[$idx]['unit_sale_price'] = $partModel->unit_sale_price;
                    } else {
                        $parts[$idx]['part_name'] = $parts[$idx]['part_name'] ?? 'N/A';
                        $parts[$idx]['unit_sale_price'] = $parts[$idx]['unit_sale_price'] ?? 0;
                    }
                }
            }
            $jobOrder->parts_needed = $parts;
        }
        
        // Calculate totals
        $partsTotal = 0.0;
        foreach($jobOrder->parts_needed ?? [] as $p) {
            $qty = isset($p['quantity']) ? (int)$p['quantity'] : 1;
            $price = isset($p['unit_sale_price']) ? (float)$p['unit_sale_price'] : 0.0;
            $partsTotal += $qty * $price;
        }
        
        $laborTotal = 0.0;
        if(!empty($jobOrder->issues) && is_array($jobOrder->issues)) {
            foreach($jobOrder->issues as $issue) {
                if (!empty($issue['type'])) {
                    $svc = \App\Models\Service::where('name', $issue['type'])->first();
                    if ($svc) $laborTotal += (float)$svc->labor_price;
                }
            }
        }
        
        $estimatedTotal = $partsTotal + $laborTotal;
        
        return view('customer-portal.view', [
            'jobOrder' => $jobOrder,
            'partsTotal' => $partsTotal,
            'laborTotal' => $laborTotal,
            'estimatedTotal' => $estimatedTotal,
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
        
        $jobOrder->approveByCustomer();
        
        return redirect()->route('customer.portal.view', ['token' => $token])
            ->with('success', 'Thank you! Your repair quote has been approved. We will begin work shortly.');
    }
}
