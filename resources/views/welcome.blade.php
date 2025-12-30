<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="antialiased bg-white dark:bg-zinc-900">
        <x-navbar />

        <!-- Hero Section -->
        <section class="pt-24 pb-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-50 to-white dark:from-zinc-800 dark:to-zinc-900">
            <div class="max-w-7xl mx-auto">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-zinc-900 dark:text-white leading-tight mb-6">
                            Get Your Devices <span class="text-blue-600">Repaired</span> Hassle-Free
                        </h1>
                        <p class="text-lg text-zinc-600 dark:text-zinc-300 mb-8">
                            Track your appliance and gadget repairs in real-time. Get instant updates, approve costs, and stay connected with our intelligent repair management platform.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="#quote-form" class="px-8 py-4 text-center text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold text-lg transition-colors shadow-lg hover:shadow-xl">
                                Get a Free Quote
                            </a>
                            <a href="#features" class="px-8 py-4 text-center text-blue-600 dark:text-blue-400 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 border border-blue-200 dark:border-blue-700 rounded-lg font-semibold text-lg transition-colors">
                                Learn More
                            </a>
                        </div>
                    </div>
                    <div class="relative">
                        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl p-6 border border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            </div>
                            
                            <!-- Dashboard Preview -->
                            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-4 space-y-4">
                                <!-- Header Stats -->
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm opacity-90">My Repairs</p>
                                            <p class="text-2xl font-bold">3 Active</p>
                                        </div>
                                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recent Repair Card -->
                                <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <p class="font-semibold text-zinc-900 dark:text-white text-sm">iPhone 13 Pro</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Screen Replacement</p>
                                        </div>
                                        <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded text-xs font-medium">In Progress</span>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                            <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                                            <span>Received & Assessed</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                            <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                                            <span>Cost Approved</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400 font-medium">
                                            <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                                            <span>Repair in Progress</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Stats Grid -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-white dark:bg-zinc-800 rounded-lg p-3 border border-zinc-200 dark:border-zinc-700">
                                        <div class="flex items-center gap-2 mb-1">
                                            <div class="w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded flex items-center justify-center">
                                                <svg class="w-3 h-3 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Completed</span>
                                        </div>
                                        <p class="text-xl font-bold text-zinc-900 dark:text-white">12</p>
                                    </div>
                                    <div class="bg-white dark:bg-zinc-800 rounded-lg p-3 border border-zinc-200 dark:border-zinc-700">
                                        <div class="flex items-center gap-2 mb-1">
                                            <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900/30 rounded flex items-center justify-center">
                                                <svg class="w-3 h-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Avg Time</span>
                                        </div>
                                        <p class="text-xl font-bold text-zinc-900 dark:text-white">2.5d</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Floating Elements -->
                        <div class="absolute -top-4 -right-4 w-20 h-20 bg-blue-500 rounded-full blur-3xl opacity-30 animate-pulse"></div>
                        <div class="absolute -bottom-4 -left-4 w-20 h-20 bg-purple-500 rounded-full blur-3xl opacity-30 animate-pulse" style="animation-delay: 1s"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 px-4 sm:px-6 lg:px-8 bg-white dark:bg-zinc-900">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-4">
                        Why Choose Our Repair Service Platform?
                    </h2>
                    <p class="text-lg text-zinc-600 dark:text-zinc-300 max-w-2xl mx-auto">
                        Transparent, efficient, and customer-focused repair service management
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div tabindex="0" class="p-6 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 hover:border-blue-500 dark:hover:border-blue-500 transform transition duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-zinc-200/50 dark:hover:shadow-black/30 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Easy Service Request</h3>
                        <p class="text-zinc-600 dark:text-zinc-300">
                            Submit your repair request online with device details and issue description - no need to wait in line or make phone calls.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div tabindex="0" class="p-6 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 hover:border-blue-500 dark:hover:border-blue-500 transform transition duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-zinc-200/50 dark:hover:shadow-black/30 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Real-Time Status Tracking</h3>
                        <p class="text-zinc-600 dark:text-zinc-300">
                            Know exactly where your device is at all times - from initial assessment to quality check to ready for pickup notifications.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div tabindex="0" class="p-6 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 hover:border-blue-500 dark:hover:border-blue-500 transform transition duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-zinc-200/50 dark:hover:shadow-black/30 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Expert Technicians</h3>
                        <p class="text-zinc-600 dark:text-zinc-300">
                            Your device is automatically assigned to the most qualified technician specialized in your specific device type.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div tabindex="0" class="p-6 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 hover:border-blue-500 dark:hover:border-blue-500 transform transition duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-zinc-200/50 dark:hover:shadow-black/30 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Quality Parts Guarantee</h3>
                        <p class="text-zinc-600 dark:text-zinc-300">
                            We maintain a well-stocked inventory of genuine spare parts to ensure your device gets the best components.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div tabindex="0" class="p-6 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 hover:border-blue-500 dark:hover:border-blue-500 transform transition duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-zinc-200/50 dark:hover:shadow-black/30 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Transparent Pricing</h3>
                        <p class="text-zinc-600 dark:text-zinc-300">
                            Receive instant notifications via SMS/Email and approve repair costs before any work begins - no surprises.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div tabindex="0" class="p-6 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 hover:border-blue-500 dark:hover:border-blue-500 transform transition duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-zinc-200/50 dark:hover:shadow-black/30 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Detailed Service Reports</h3>
                        <p class="text-zinc-600 dark:text-zinc-300">
                            Get a complete digital report of your repair including diagnosis, parts replaced, and maintenance recommendations.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quote Request Section -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-50 to-white dark:from-zinc-900 dark:to-zinc-800">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-4">
                        Request a Free Repair Quote
                    </h2>
                    <p class="text-lg text-zinc-600 dark:text-zinc-300 max-w-2xl mx-auto">
                        Tell us about your device and the issue you're experiencing. We'll get back to you with a detailed quote within 24 hours.
                    </p>
                </div>
                @livewire('quote.request-form')
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-8 px-4 sm:px-6 lg:px-8 bg-zinc-100 dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700">
            <div class="max-w-7xl mx-auto text-center text-zinc-600 dark:text-zinc-400">
                <p>&copy; {{ date('Y') }} IRF-CES. All rights reserved.</p>
                <p class="mt-2 text-sm">Intelligent Repair Flow & Client Engagement System</p>
            </div>
        </footer>
        <script>
            document.addEventListener('click', function (e) {
                const anchor = e.target.closest('a[href^="#"]');
                if (!anchor) return;
                const href = anchor.getAttribute('href');
                if (!href || href === '#') return;
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    // Prefer scrolling to the parent <section> so the section background (gradient) lines up under the navbar
                    const section = target.closest('section') || target;
                    const navbar = document.querySelector('nav.fixed');
                    const navHeight = navbar ? navbar.getBoundingClientRect().height : 64;
                    const extraGap = 0; // remove breathing room so background meets navbar
                    const targetY = section.getBoundingClientRect().top + window.pageYOffset - (navHeight + extraGap);
                    window.scrollTo({ top: Math.max(0, Math.round(targetY)), behavior: 'smooth' });
                    try { history.pushState(null, '', href); } catch (err) { /* ignore */ }
                }
            });
        </script>
    </body>
</html>
