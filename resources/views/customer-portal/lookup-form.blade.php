<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-md border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-3">
        <h2 class="text-sm sm:text-base font-semibold text-white flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="truncate">Enter Job Order Number</span>
        </h2>
    </div>
    
    <div class="p-4 sm:p-6">
        @if(session('success'))
            <div class="mb-4 p-3 sm:p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-3 sm:p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <form action="{{ route('customer.portal.lookup') }}" method="POST" class="space-y-4">
            @csrf
            
            <div>
                <label for="job_order_number" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                    Job Order Number
                </label>
                <input 
                    type="text" 
                    id="job_order_number" 
                    name="job_order_number"
                    placeholder="e.g., JO-20251231-0001"
                    required
                    class="w-full px-3 py-2 text-base border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all"
                    value="{{ old('job_order_number') }}"
                />
                @error('job_order_number')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button 
                type="submit"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-base font-semibold rounded-lg shadow-sm hover:shadow-md transition-all duration-150 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="text-sm">Track My Repair</span>
            </button>
        </form>

        <!-- Compact Help Text (keep light styling even in dark mode) -->
        <div class="mt-4 text-sm text-blue-700 bg-blue-50 border border-blue-100 rounded-md p-3">
            <p class="font-medium text-blue-900 mb-1">Where to find your job order number?</p>
            <p class="leading-tight">On your drop-off receipt or in the confirmation email we sent.</p>
        </div>
    </div>
</div>
