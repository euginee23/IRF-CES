<?php

use App\Enums\JobOrderStatus;
use App\Enums\Role;
use App\Mail\JobCompletedMail;
use App\Models\JobOrder;
use App\Models\Part;
use App\Models\Service;
use App\Enums\PaymentMethod;
use App\Exceptions\UnpaidBalance;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PaymentService;
use Illuminate\Validation\Rule;
use App\Services\JobOrders\JobOrderWorkflow;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    /** A technician's id, or '' for every technician. */
    public string $technicianFilter = '';

    /** '' | 'unassigned' | 'overdue' — the two queues the counter chases. */
    public string $assignmentFilter = '';

    public ?JobOrder $selectedJobOrder = null;
    public bool $showViewModal = false;

    // Take payment
    public bool $showPaymentModal = false;
    public $paymentAmount = null;
    public string $paymentMethod = 'cash';
    public string $paymentReference = '';
    public string $paymentNote = '';

    public function viewJobOrder(int $id): void
    {
        // parts and services carry their own names and prices now, so there
        // is nothing left to hydrate by hand.
        $job = JobOrder::with(['receivedBy', 'assignedTo', 'parts', 'services'])
            ->visibleTo(auth()->user())
            ->findOrFail($id);

        $this->selectedJobOrder = $job;
        $this->showViewModal = true;
    }

    /**
     * The selected repair's history, for the modal's timeline.
     *
     * A method rather than a query in the Blade so it runs when the modal is
     * rendered, not on every keystroke elsewhere on the page.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\JobOrderEvent>
     */
    /** @return \Illuminate\Support\Collection<int, Payment> */
    public function selectedJobOrderPayments()
    {
        if (! $this->selectedJobOrder) {
            return collect();
        }

        return $this->selectedJobOrder->payments()->with('receivedBy')->get();
    }

    public function selectedJobOrderEvents()
    {
        if (! $this->selectedJobOrder) {
            return collect();
        }

        return $this->selectedJobOrder->events()->with('user')->get();
    }

    public function openPaymentModal(int $id): void
    {
        $jobOrder = $this->visible()->findOrFail($id);

        $this->selectedJobOrder = $jobOrder;
        // Pre-filled with the balance, which is what is taken most of the
        // time; staff can type less for a deposit.
        $this->paymentAmount = $jobOrder->balance() > 0 ? $jobOrder->balance() : null;
        $this->paymentMethod = PaymentMethod::CASH->value;
        $this->paymentReference = '';
        $this->paymentNote = '';
        $this->resetErrorBag();
        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->resetErrorBag();
    }

    public function takePayment(): void
    {
        if (! $this->selectedJobOrder) {
            return;
        }

        $validated = $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'paymentReference' => 'nullable|string|max:255',
            'paymentNote' => 'nullable|string|max:255',
        ], [
            'paymentAmount.min' => 'A payment must be for more than zero.',
        ]);

        $payment = app(PaymentService::class)->take(
            jobOrder: $this->selectedJobOrder,
            amount: (float) $validated['paymentAmount'],
            method: PaymentMethod::from($validated['paymentMethod']),
            referenceNo: $validated['paymentReference'] ?: null,
            note: $validated['paymentNote'] ?: null,
        );

        $this->selectedJobOrder->refresh();
        $this->showPaymentModal = false;

        $this->dispatch('success', message: "Payment recorded — receipt {$payment->receipt_number}.");
    }

    public function downloadPaymentReceipt(int $paymentId)
    {
        $payment = Payment::with(['jobOrder', 'receivedBy'])->findOrFail($paymentId);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.payment-receipt', [
            'payment' => $payment,
            'jobOrder' => $payment->jobOrder,
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'receipt-' . $payment->receipt_number . '.pdf',
        );
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->selectedJobOrder = null;
    }

    public function downloadReceipt(int $id)
    {
        $jobOrder = JobOrder::with(['receivedBy', 'assignedTo', 'parts', 'services'])->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.job-order-receipt', [
            'jobOrder' => $jobOrder
        ]);
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, 'job-order-' . $jobOrder->job_order_number . '.pdf');
    }

    public function sendQuoteApproval(int $id): void
    {
        $jobOrder = JobOrder::with(['receivedBy', 'assignedTo', 'parts', 'services'])->findOrFail($id);
        
        if (!$jobOrder->customer_email) {
            $this->dispatch('error', message: 'Customer email is not available.');
            return;
        }

        // Totals come off the lines' own snapshots, so the figure quoted here
        // is the one the customer will be billed.
        $partsTotal = $jobOrder->partsTotal();
        $laborTotal = $jobOrder->laborTotal();
        $estimatedTotal = $jobOrder->lineTotal();
        
        // Send email
        try {
            \Illuminate\Support\Facades\Mail::to($jobOrder->customer_email)
                ->send(new \App\Mail\QuoteApprovalMail($jobOrder, $partsTotal, $laborTotal, $estimatedTotal));
            
            // Update status to awaiting approval
            app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::AWAITING_APPROVAL);
            
            $this->dispatch('success', message: 'Quote approval email sent successfully to ' . $jobOrder->customer_email);
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to send email: ' . $e->getMessage());
        }
    }

    public function manualApproval(int $id): void
    {
        $jobOrder = JobOrder::findOrFail($id);
        
        app(JobOrderWorkflow::class)->approveManually($jobOrder);
        
        $this->dispatch('success', message: 'Job order manually approved successfully.');
    }

    public function markCompleted(int $id): void
    {
        $jobOrder = JobOrder::findOrFail($id);

        if ($jobOrder->status !== JobOrderStatus::DONE) {
            $this->dispatch('error', message: 'Job order must be marked as done by technician first.');
            return;
        }

        app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::COMPLETED);

        if ($jobOrder->customer_email) {
            try {
                Mail::to($jobOrder->customer_email)->send(new JobCompletedMail($jobOrder));
            } catch (\Exception $e) {
                // Log but don't block the status update
                \Log::error('Failed to send job completed email: ' . $e->getMessage());
            }
        }

        $this->dispatch('success', message: 'Job order marked as completed. Customer has been notified.');
    }

    public function markDelivered(int $id): void
    {
        $jobOrder = JobOrder::findOrFail($id);

        if ($jobOrder->status !== JobOrderStatus::COMPLETED) {
            $this->dispatch('error', message: 'Job order must be completed first.');
            return;
        }

        try {
            app(JobOrderWorkflow::class)->transitionTo(
                $jobOrder,
                JobOrderStatus::DELIVERED,
                // An administrator can release an unpaid device; counter staff
                // take the balance first.
                allowUnpaidDelivery: auth()->user()->isAdministrator(),
            );
        } catch (UnpaidBalance $e) {
            $this->dispatch('error', message: $e->getMessage());

            return;
        }

        $message = 'Job order marked as delivered.';

        if ($jobOrder->fresh()->balance() > 0) {
            $message .= ' Note: PHP ' . number_format($jobOrder->fresh()->balance(), 2) . ' is still owed.';
        }

        $this->dispatch('success', message: $message);
    }

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function title()
    {
        return __('Job Orders');
    }

    /**
     * Job orders this user may see, before any filter is applied.
     *
     * A fresh builder each call, because Eloquent builders are mutable and the
     * stats below would otherwise inherit the list's filters.
     */
    private function visible()
    {
        return JobOrder::query()->visibleTo(auth()->user());
    }

    public function with(): array
    {
        $query = $this->visible()->with(['receivedBy', 'assignedTo']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('job_order_number', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $this->search . '%')
                  ->orWhere('device_brand', 'like', '%' . $this->search . '%')
                  ->orWhere('device_model', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->technicianFilter !== '') {
            $query->where('assigned_to', $this->technicianFilter);
        }

        if ($this->assignmentFilter === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($this->assignmentFilter === 'overdue') {
            $this->scopeOverdue($query);
        }

        $jobOrders = $query->latest()->paginate(15);

        return [
            'jobOrders' => $jobOrders,
            'technicians' => User::where('role', Role::TECHNICIAN)->orderBy('name')->get(),
            'stats' => [
                'total' => $this->visible()->count(),
                'pending' => $this->visible()->where('status', JobOrderStatus::PENDING)->count(),
                'awaiting_approval' => $this->visible()->where('status', JobOrderStatus::AWAITING_APPROVAL)->count(),
                'approved' => $this->visible()->where('status', JobOrderStatus::APPROVED)->count(),
                'in_progress' => $this->visible()->where('status', JobOrderStatus::IN_PROGRESS)->count(),
                'done' => $this->visible()->where('status', JobOrderStatus::DONE)->count(),
                'completed' => $this->visible()->where('status', JobOrderStatus::COMPLETED)->count(),
                'unassigned' => $this->visible()->whereNull('assigned_to')->count(),
                'overdue' => $this->scopeOverdue($this->visible())->count(),
            ],
        ];
    }

    /**
     * Past its promised date and still on the bench.
     *
     * A finished repair is never overdue, however late it was — chasing it
     * would tell the counter to act on something already dealt with.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<JobOrder>  $query
     */
    private function scopeOverdue($query)
    {
        return $query->whereNotNull('expected_completion_date')
            ->whereDate('expected_completion_date', '<', today())
            ->whereNotIn('status', [
                JobOrderStatus::COMPLETED,
                JobOrderStatus::DELIVERED,
                JobOrderStatus::CANCELLED,
            ]);
    }

    public function delete(int $id): void
    {
        $jobOrder = JobOrder::findOrFail($id);
        
        if (!$jobOrder->canBeEdited()) {
            $this->dispatch('error', message: 'Cannot delete completed or delivered job orders.');
            return;
        }
        
        $jobOrder->delete();
        $this->dispatch('success', message: 'Job order deleted successfully.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTechnicianFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAssignmentFilter(): void
    {
        $this->resetPage();
    }

    /** Jump straight to one of the chase queues from its stat tile. */
    public function showQueue(string $queue): void
    {
        $this->assignmentFilter = $this->assignmentFilter === $queue ? '' : $queue;
        $this->resetPage();
    }
}; ?>


<div>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-800 bg-clip-text text-transparent">Job Orders</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Manage cellphone repair and service job orders</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6">
            <!-- Total Orders Card -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Orders</p>
                        <p class="text-4xl font-bold text-zinc-900 dark:text-white">{{ $stats['total'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">all time orders</p>
                    </div>
                </div>
            </div>

            <!-- Pending Card -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30 rounded-full">Pending</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pending</p>
                        <p class="text-4xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">awaiting assignment</p>
                    </div>
                </div>
            </div>

            <!-- In Progress Card -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold text-blue-700 bg-blue-100 dark:text-blue-300 dark:bg-blue-900/30 rounded-full">Active</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">In Progress</p>
                        <p class="text-4xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['in_progress'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">being repaired</p>
                    </div>
                </div>
            </div>

            <!-- Awaiting Approval Card -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold text-yellow-700 bg-yellow-100 dark:text-yellow-300 dark:bg-yellow-900/30 rounded-full">Waiting</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Awaiting Approval</p>
                        <p class="text-4xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['awaiting_approval'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">quote sent</p>
                    </div>
                </div>
            </div>

            <!-- Approved Card -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/30 rounded-full">Approved</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Approved</p>
                        <p class="text-4xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['approved'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">ready to start</p>
                    </div>
                </div>
            </div>

            <!-- Completed Card -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-green-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/30 rounded-full">Done</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Completed</p>
                        <p class="text-4xl font-bold text-green-600 dark:text-green-400">{{ $stats['completed'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">finished repairs</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-800 dark:to-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wide">Filters</h3>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div class="space-y-2">
                        <label for="search" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            Search
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="search"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search by order#, customer, phone, device..."
                                class="w-full pl-10 pr-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="statusFilter" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            Status Filter
                        </label>
                        <select 
                            id="statusFilter"
                            wire:model.live="statusFilter"
                            class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="assigned">Assigned</option>
                            <option value="awaiting_approval">Awaiting Approval</option>
                            <option value="approved">Approved</option>
                            <option value="in_progress">In Progress</option>
                            <option value="done">Done</option>
                            <option value="completed">Completed</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="technicianFilter" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            Technician
                        </label>
                        <select
                            id="technicianFilter"
                            wire:model.live="technicianFilter"
                            class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                            <option value="">All Technicians</option>
                            @foreach($technicians as $technician)
                                <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- The two queues the counter actually chases. --}}
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 me-1">Quick filters:</span>

                    <button type="button" wire:click="showQueue('unassigned')"
                        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors cursor-pointer {{ $assignmentFilter === 'unassigned' ? 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700' : 'bg-white text-zinc-700 border-zinc-300 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700 dark:hover:bg-zinc-700' }}">
                        Unassigned
                        <span class="px-1.5 py-0.5 text-xs font-bold rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ $stats['unassigned'] }}</span>
                    </button>

                    <button type="button" wire:click="showQueue('overdue')"
                        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors cursor-pointer {{ $assignmentFilter === 'overdue' ? 'bg-red-100 text-red-800 border-red-300 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700' : 'bg-white text-zinc-700 border-zinc-300 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700 dark:hover:bg-zinc-700' }}">
                        Overdue
                        <span class="px-1.5 py-0.5 text-xs font-bold rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ $stats['overdue'] }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-800 dark:to-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wide">All Job Orders</h3>
                    <span class="px-2 py-0.5 text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 rounded-full">
                        {{ $jobOrders->total() }}
                    </span>
                </div>
                <a href="{{ route('job-orders.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create Job Order
                </a>
            </div>

            @if($jobOrders->isEmpty())
                <div class="p-12 text-center">
                    <div class="flex justify-center mb-4">
                        <div class="p-4 bg-zinc-100 dark:bg-zinc-800 rounded-full">
                            <svg class="w-12 h-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">No Job Orders Found</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">
                        @if($search || $statusFilter || $technicianFilter || $assignmentFilter)
                            Try adjusting your filters
                        @else
                            Get started by creating your first job order
                        @endif
                    </p>
                    @if(!$search && !$statusFilter && !$technicianFilter && !$assignmentFilter)
                        <a href="{{ route('job-orders.create') }}"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Create Your First Job Order
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Job Order
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Customer
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Device
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Technician
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach($jobOrders as $jobOrder)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $jobOrder->job_order_number }}</div>
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $jobOrder->created_at->format('M d, Y') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $jobOrder->customer_name }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $jobOrder->customer_phone }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-zinc-900 dark:text-white font-medium">{{ $jobOrder->device_brand }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $jobOrder->device_model }} • {{ $jobOrder->device_type }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'pending' => 'amber',
                                                'assigned' => 'blue',
                                                'awaiting_approval' => 'yellow',
                                                'approved' => 'emerald',
                                                'in_progress' => 'indigo',
                                                'done' => 'cyan',
                                                'completed' => 'green',
                                                'delivered' => 'teal',
                                                'cancelled' => 'red',
                                            ];
                                            $color = $statusColors[$jobOrder->status->value] ?? 'zinc';
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-{{ $color }}-700 bg-{{ $color }}-100 dark:text-{{ $color }}-300 dark:bg-{{ $color }}-900/30 rounded-full">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="3"/>
                                            </svg>
                                            {{ $jobOrder->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($jobOrder->assignedTo)
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">
                                                    {{ strtoupper(substr($jobOrder->assignedTo->name, 0, 1)) }}
                                                </div>
                                                <span class="text-sm text-zinc-900 dark:text-white">{{ $jobOrder->assignedTo->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-zinc-400 italic">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $jobOrder->created_at->format('M d, Y') }}</div>
                                        <div class="text-xs text-zinc-400 dark:text-zinc-500">{{ $jobOrder->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            {{-- View --}}
                                            <button wire:click="viewJobOrder({{ $jobOrder->id }})" wire:loading.attr="disabled" wire:target="viewJobOrder" class="inline-flex items-center gap-1 px-3 py-1.5 bg-zinc-600 hover:bg-zinc-700 text-white text-xs font-medium rounded-lg transition-colors duration-150 shadow-sm hover:shadow cursor-pointer disabled:opacity-50 disabled:cursor-wait">
                                                <svg wire:loading.remove wire:target="viewJobOrder" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <svg wire:loading wire:target="viewJobOrder" class="w-3.5 h-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span wire:loading.remove wire:target="viewJobOrder">View</span>
                                                <span wire:loading wire:target="viewJobOrder">Loading...</span>
                                            </button>
                                            @if($jobOrder->canBeEdited())
                                                {{-- Edit --}}
                                                <a href="{{ route('job-orders.edit', $jobOrder) }}" wire:navigate
                                                    x-data="{ loading: false }" x-on:click="loading = true"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-150 shadow-sm hover:shadow cursor-pointer"
                                                    x-bind:class="loading && 'opacity-50 pointer-events-none cursor-wait'">
                                                    <svg x-show="!loading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                    <svg x-show="loading" x-cloak class="w-3.5 h-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Edit
                                                </a>
                                                {{-- Delete --}}
                                                <button
                                                    wire:loading.attr="disabled" wire:target="delete({{ $jobOrder->id }})"
                                                    x-on:click="$dispatch('open-delete-dialog', {
                                                        title: 'Delete Job Order',
                                                        message: 'Are you sure you want to delete {{ addslashes($jobOrder->job_order_number) }}? This action cannot be undone.',
                                                        confirmText: 'Delete',
                                                        cancelText: 'Cancel',
                                                        callback: () => $wire.delete({{ $jobOrder->id }})
                                                    })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors duration-150 shadow-sm hover:shadow cursor-pointer disabled:opacity-50 disabled:cursor-wait">
                                                    <svg wire:loading.remove wire:target="delete({{ $jobOrder->id }})" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    <svg wire:loading wire:target="delete({{ $jobOrder->id }})" class="w-3.5 h-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($jobOrders->hasPages())
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800">
                        {{ $jobOrders->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Notification Toast -->
    <x-notification-toast />
    
    <!-- Delete Confirmation Dialog -->
    <x-delete-confirmation />

    <!-- View Job Order Modal -->
        <div
            x-data="{ show: @entangle('showViewModal').live }"
            x-show="show"
            x-cloak
            x-on:keydown.escape.window="show = false"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;">

            <!-- Backdrop -->
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                x-on:click="$wire.closeViewModal()"></div>

            <!-- Modal Container -->
            <div class="flex items-center justify-center min-h-screen p-4">
                <div
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden"
                    x-on:click.stop>

                    @if($selectedJobOrder)
                    <!-- Modal Header -->
                    <div class="sticky top-0 z-10 bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 border-b border-indigo-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/10 rounded-lg backdrop-blur-sm">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-white">Job Order Details</h3>
                                    <p class="text-sm text-indigo-100">{{ $selectedJobOrder->job_order_number }}</p>
                                    @php
                                        $debug_showSend = in_array($selectedJobOrder->status->value, ['pending', 'assigned']);
                                        $debug_showApprove = in_array($selectedJobOrder->status->value, ['awaiting_approval', 'assigned', 'pending']);
                                    @endphp
                                </div>
                            </div>
                            <button wire:click="closeViewModal" class="p-2 hover:bg-white/10 rounded-lg transition-colors cursor-pointer">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 pb-24">
                        <div class="flex flex-col lg:flex-row gap-6">

                            <!-- Left Column: Summary Card (static, non-scrolling) -->
                            <div class="w-full lg:w-80 flex-none space-y-6">
                                <!-- Status Card -->
                                <div class="bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-800 dark:to-zinc-900 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
                                    <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-4">Status</h4>
                                    @php
                                        $statusColors = [
                                            'pending' => 'amber',
                                            'assigned' => 'blue',
                                            'awaiting_approval' => 'yellow',
                                            'approved' => 'emerald',
                                            'in_progress' => 'indigo',
                                            'done' => 'cyan',
                                            'completed' => 'green',
                                            'delivered' => 'teal',
                                            'cancelled' => 'red',
                                        ];
                                        $color = $statusColors[$selectedJobOrder->status->value] ?? 'zinc';
                                    @endphp
                                    <div class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-{{ $color }}-700 bg-{{ $color }}-100 dark:text-{{ $color }}-300 dark:bg-{{ $color }}-900/30 rounded-full">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3"/>
                                        </svg>
                                        {{ $selectedJobOrder->status->label() }}
                                    </div>
                                </div>

                                <!-- Cost Summary -->
                                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-6 border border-emerald-200 dark:border-emerald-800">
                                    <h4 class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wide mb-4">Cost Summary</h4>
                                    @php
                                        $partsTotal = $selectedJobOrder->partsTotal();
                                        $laborTotal = $selectedJobOrder->laborTotal();
                                        $displayTotal = $selectedJobOrder->final_cost ?? $selectedJobOrder->estimated_cost ?? $selectedJobOrder->lineTotal();
                                    @endphp

                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Parts</span>
                                            <span class="text-lg font-bold text-zinc-900 dark:text-white">₱{{ number_format($partsTotal, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Labor</span>
                                            <span class="text-lg font-bold text-zinc-900 dark:text-white">₱{{ number_format($laborTotal, 2) }}</span>
                                        </div>
                                        <div class="pt-3 border-t border-emerald-200 dark:border-emerald-800">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Estimated Total</span>
                                                <span class="text-xl font-bold text-emerald-700 dark:text-emerald-400">₱{{ number_format($displayTotal, 2) }}</span>
                                            </div>
                                        </div>
                                        @if($selectedJobOrder->final_cost)
                                            <div class="pt-3 border-t border-emerald-200 dark:border-emerald-800">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Final Cost</span>
                                                    <span class="text-xl font-bold text-emerald-700 dark:text-emerald-400">₱{{ number_format($selectedJobOrder->final_cost, 2) }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Timeline -->
                                <div class="bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-800 dark:to-zinc-900 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
                                    <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-4">Timeline</h4>
                                    <div class="space-y-4">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-0.5 p-1.5 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Created</p>
                                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedJobOrder->created_at->format('M d, Y h:i A') }}</p>
                                            </div>
                                        </div>
                                        @if($selectedJobOrder->expected_completion_date)
                                            <div class="flex items-start gap-3">
                                                <div class="mt-0.5 p-1.5 bg-amber-100 dark:bg-amber-900/30 rounded-full">
                                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Expected Completion</p>
                                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedJobOrder->expected_completion_date->format('M d, Y') }}</p>
                                                </div>
                                            </div>
                                        @endif
                                        @if($selectedJobOrder->completed_at)
                                            <div class="flex items-start gap-3">
                                                <div class="mt-0.5 p-1.5 bg-green-100 dark:bg-green-900/30 rounded-full">
                                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <div>
                                            @if($selectedJobOrder->completed_at)
                                                <div class="flex items-start gap-3">
                                                    <div class="mt-0.5 p-1.5 bg-green-100 dark:bg-green-900/30 rounded-full">
                                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Completed</p>
                                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedJobOrder->completed_at->format('M d, Y h:i A') }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Delivered</p>
                                    
                                        
                                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedJobOrder->delivered_at->format('M d, Y h:i A') }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <!-- GET RECEIPT BUTTON (left column) -->
                                <div class="mt-4 lg:mt-0 lg:mb-6">
                                    <button wire:click="downloadReceipt({{ $selectedJobOrder->id }})" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span class="hidden lg:inline">GET RECEIPT</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="hidden lg:block w-px bg-zinc-100 dark:bg-zinc-800"></div>

                            <!-- Right Column: Details (scrollable) -->
                            <div class="flex-1 overflow-y-auto max-h-[70vh] pr-4 pb-10">
                                <div class="space-y-6">
                                
                                <!-- Customer Information -->
                                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 px-5 py-3 border-b border-blue-100 dark:border-blue-800">
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            Customer Information
                                        </h4>
                                    </div>
                                    <div class="p-5 grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Name</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->customer_name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Phone</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->customer_phone }}</p>
                                        </div>
                                        @if($selectedJobOrder->customer_email)
                                            <div>
                                                <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Email</p>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->customer_email }}</p>
                                            </div>
                                        @endif
                                        @if($selectedJobOrder->customer_address)
                                            <div class="col-span-2">
                                                <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Address</p>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->customer_address }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Device Information -->
                                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 px-5 py-3 border-b border-purple-100 dark:border-purple-800">
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                            Device Information
                                        </h4>
                                    </div>
                                    <div class="p-5 grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Brand</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->device_brand }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Model</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->device_model }}</p>
                                        </div>
                                        @if($selectedJobOrder->serial_number)
                                            <div class="col-span-2">
                                                <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Serial / IMEI</p>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white font-mono">{{ $selectedJobOrder->serial_number }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Issue Description -->
                                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                    <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 px-5 py-3 border-b border-red-100 dark:border-red-800">
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            Issue Description
                                        </h4>
                                    </div>
                                    <div class="p-5">
                                        <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ $selectedJobOrder->issue_description }}</p>
                                    </div>
                                </div>

                                <!-- Services Required -->
                                @if($selectedJobOrder->services->isNotEmpty())
                                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 px-5 py-3 border-b border-indigo-100 dark:border-indigo-800">
                                            <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                                Services Required
                                            </h4>
                                        </div>
                                        <div class="p-5">
                                            <div class="space-y-3">
                                                @foreach($selectedJobOrder->services as $issue)
                                                    <div class="flex items-start gap-3 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                                        <div class="mt-0.5">
                                                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1">
                                                            <div class="flex items-center justify-between">
                                                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $issue->service_name }}</p>
                                                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">
                                                                    @if((float) $issue->labor_price > 0)
                                                                        Labor: ₱{{ number_format((float) $issue->labor_price, 2) }}
                                                                    @else
                                                                        —
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            @if($issue->diagnosis)
                                                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">{{ $issue->diagnosis }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Parts Needed -->
                                @if($selectedJobOrder->parts->isNotEmpty())
                                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 px-5 py-3 border-b border-emerald-100 dark:border-emerald-800">
                                            <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                </svg>
                                                Parts Needed
                                            </h4>
                                        </div>
                                        <div class="p-5">
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                                            <th class="text-left py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Part</th>
                                                            <th class="text-center py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Qty</th>
                                                            <th class="text-right py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Unit Price</th>
                                                            <th class="text-right py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                                        @foreach($selectedJobOrder->parts as $part)
                                                            @php
                                                                $partName = $part->part_name;
                                                                $unitPrice = (float) $part->unit_sale_price;
                                                                $qty = $part->quantity;
                                                            @endphp
                                                            <tr>
                                                                <td class="py-2 font-medium text-zinc-900 dark:text-white">{{ $partName }}</td>
                                                                <td class="py-2 text-center text-zinc-700 dark:text-zinc-300">{{ $qty }}</td>
                                                                <td class="py-2 text-right text-zinc-700 dark:text-zinc-300">₱{{ number_format($unitPrice, 2) }}</td>
                                                                <td class="py-2 text-right font-semibold text-zinc-900 dark:text-white">₱{{ number_format($unitPrice * $qty, 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Work Performed -->
                                @if($selectedJobOrder->work_performed)
                                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 px-5 py-3 border-b border-green-100 dark:border-green-800">
                                            <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Work Performed
                                            </h4>
                                        </div>
                                        <div class="p-5">
                                            <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ $selectedJobOrder->work_performed }}</p>
                                        </div>
                                    </div>
                                @endif

                                <!-- Payments -->
                                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 px-5 py-3 border-b border-emerald-100 dark:border-emerald-800 flex items-center justify-between gap-3">
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Payments
                                        </h4>
                                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full {{ $selectedJobOrder->paymentStatus()->badgeClasses() }}">
                                            {{ $selectedJobOrder->paymentStatus()->label() }}
                                        </span>
                                    </div>

                                    <div class="p-5 space-y-4">
                                        <div class="grid grid-cols-3 gap-4 text-center">
                                            <div>
                                                <p class="text-xs uppercase text-zinc-500 dark:text-zinc-400 font-semibold">Billed</p>
                                                <p class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">₱{{ number_format($selectedJobOrder->amountDue(), 2) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs uppercase text-zinc-500 dark:text-zinc-400 font-semibold">Paid</p>
                                                <p class="mt-1 text-lg font-bold text-emerald-600 dark:text-emerald-400">₱{{ number_format($selectedJobOrder->amountPaid(), 2) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs uppercase text-zinc-500 dark:text-zinc-400 font-semibold">Balance</p>
                                                <p class="mt-1 text-lg font-bold {{ $selectedJobOrder->balance() > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
                                                    ₱{{ number_format($selectedJobOrder->balance(), 2) }}
                                                </p>
                                            </div>
                                        </div>

                                        @php $payments = $this->selectedJobOrderPayments(); @endphp

                                        @if($payments->isNotEmpty())
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                                            <th class="text-left py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Receipt</th>
                                                            <th class="text-left py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Method</th>
                                                            <th class="text-left py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Taken</th>
                                                            <th class="text-right py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Amount</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                                        @foreach($payments as $payment)
                                                            <tr class="{{ $payment->isVoided() ? 'opacity-50 line-through' : '' }}">
                                                                <td class="py-2 font-medium text-zinc-900 dark:text-white">{{ $payment->receipt_number }}</td>
                                                                <td class="py-2 text-zinc-700 dark:text-zinc-300">
                                                                    {{ $payment->method->label() }}
                                                                    @if($payment->reference_no)
                                                                        <span class="text-xs text-zinc-500">({{ $payment->reference_no }})</span>
                                                                    @endif
                                                                </td>
                                                                <td class="py-2 text-zinc-600 dark:text-zinc-400 text-xs">
                                                                    {{ $payment->paid_at->format('d M Y, g:ia') }}
                                                                    @if($payment->receivedBy)
                                                                        &middot; {{ $payment->receivedBy->name }}
                                                                    @endif
                                                                </td>
                                                                <td class="py-2 text-right font-semibold text-zinc-900 dark:text-white">₱{{ number_format((float) $payment->amount, 2) }}</td>
                                                                <td class="py-2 text-right">
                                                                    @unless($payment->isVoided())
                                                                        <button type="button" wire:click="downloadPaymentReceipt({{ $payment->id }})"
                                                                            class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer">
                                                                            Receipt
                                                                        </button>
                                                                    @endunless
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No payments taken yet.</p>
                                        @endif

                                        @if($selectedJobOrder->balance() > 0)
                                            <button type="button" wire:click="openPaymentModal({{ $selectedJobOrder->id }})"
                                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                                Take Payment
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <!-- Repair History -->
                                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 px-5 py-3 border-b border-indigo-100 dark:border-indigo-800">
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Repair History
                                        </h4>
                                    </div>
                                    <div class="p-5">
                                        @include('partials.job-order-timeline', [
                                            'timelineEvents' => $this->selectedJobOrderEvents(),
                                            'timelineTitle' => '',
                                        ])
                                    </div>
                                </div>

                                <!-- Assignment Information -->
                                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                    <div class="bg-gradient-to-r from-slate-50 to-zinc-50 dark:from-slate-900/20 dark:to-zinc-900/20 px-5 py-3 border-b border-slate-100 dark:border-slate-800">
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            Assignment Information
                                        </h4>
                                    </div>
                                    <div class="p-5 grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">Received By</p>
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">
                                                    {{ strtoupper(substr($selectedJobOrder->receivedBy->name ?? 'N', 0, 1)) }}
                                                </div>
                                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->receivedBy->name ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">Assigned To</p>
                                            @if($selectedJobOrder->assignedTo)
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">
                                                        {{ strtoupper(substr($selectedJobOrder->assignedTo->name, 0, 1)) }}
                                                    </div>
                                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->assignedTo->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-sm text-zinc-400 italic">Unassigned</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="sticky bottom-0 z-50 bg-zinc-50 dark:bg-zinc-800 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-end gap-2">
                            @php
                                $showSend = in_array($selectedJobOrder->status->value, ['pending', 'assigned', 'awaiting_approval']);
                                $sendLabel = $selectedJobOrder->status->value === 'awaiting_approval' ? 'Send Quote' : 'Resend Quote';
                            @endphp
                            @if($showSend)
                                <button 
                                    wire:click="sendQuoteApproval({{ $selectedJobOrder->id }})"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white text-xs font-semibold rounded-lg shadow-sm hover:shadow-md transition-all duration-200 transform hover:scale-105 cursor-pointer"
                                    title="{{ $sendLabel }}"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $sendLabel }}
                                </button>
                            @endif
                            
                            @if(in_array($selectedJobOrder->status->value, ['awaiting_approval', 'assigned', 'pending']))
                                <button 
                                    wire:click="manualApproval({{ $selectedJobOrder->id }})"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white text-xs font-semibold rounded-lg shadow-sm hover:shadow-md transition-all duration-200 transform hover:scale-105 cursor-pointer"
                                    title="Manually approve this job order"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Approve
                                </button>
                            @endif

                            @if($selectedJobOrder->status->value === 'done')
                                <button 
                                    wire:click="markCompleted({{ $selectedJobOrder->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="markCompleted"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white text-xs font-semibold rounded-lg shadow-sm hover:shadow-md transition-all duration-200 transform hover:scale-105 cursor-pointer"
                                    title="Mark as completed and notify customer">
                                    <svg wire:loading.remove wire:target="markCompleted" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <svg wire:loading wire:target="markCompleted" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Mark Completed
                                </button>
                            @endif

                            @if($selectedJobOrder->status->value === 'completed')
                                <button 
                                    wire:click="markDelivered({{ $selectedJobOrder->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="markDelivered"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white text-xs font-semibold rounded-lg shadow-sm hover:shadow-md transition-all duration-200 transform hover:scale-105 cursor-pointer"
                                    title="Mark as delivered to customer">
                                    <svg wire:loading.remove wire:target="markDelivered" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <svg wire:loading wire:target="markDelivered" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Mark Delivered
                                </button>
                            @endif
                            
                            <button 
                                wire:click="closeViewModal" 
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-zinc-600 hover:bg-zinc-700 text-white text-xs font-semibold rounded-lg shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Close
                            </button>
                        </div>
                    </div>

                    @endif
                </div>
            </div>
        </div>
    {{-- Take payment --}}
    @if($showPaymentModal && $selectedJobOrder)
        <div class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closePaymentModal"></div>

                <div class="relative w-full max-w-md bg-white dark:bg-zinc-800 rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Take Payment</h3>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $selectedJobOrder->job_order_number }} &middot; {{ $selectedJobOrder->customer_name }}
                        </p>
                    </div>

                    <form wire:submit="takePayment" class="p-6 space-y-4">
                        <div class="flex justify-between items-baseline rounded-xl bg-zinc-50 dark:bg-zinc-900 px-4 py-3">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Balance due</span>
                            <span class="text-xl font-bold text-zinc-900 dark:text-white">₱{{ number_format($selectedJobOrder->balance(), 2) }}</span>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Amount <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" wire:model="paymentAmount"
                                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" />
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Enter less than the balance to record a deposit.</p>
                            @error('paymentAmount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Method <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="paymentMethod"
                                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                @foreach(\App\Enums\PaymentMethod::options() as $method)
                                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                @endforeach
                            </select>
                            @error('paymentMethod') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Cash has nothing to reference; everything else does. --}}
                        @if(\App\Enums\PaymentMethod::from($paymentMethod)->expectsReference())
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Reference number
                                </label>
                                <input type="text" wire:model="paymentReference" placeholder="GCash ref, bank txn, card auth"
                                    class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" />
                                @error('paymentReference') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Note</label>
                            <input type="text" wire:model="paymentNote"
                                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" />
                            @error('paymentNote') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="closePaymentModal"
                                class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition-colors cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors cursor-pointer">
                                Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
