<!doctype html>

<html class="light" lang="en">
    <head>
        <meta charset="utf-8" />
        <meta content="width=device-width, initial-scale=1.0" name="viewport" />
        <title>Docs Api</title>
        <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
        <link
            href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&amp;display=swap"
            rel="stylesheet"
        />
        <link
            href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
            rel="stylesheet"
        />
        <link
            href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
            rel="stylesheet"
        />
        <script id="tailwind-config">
            tailwind.config = {
                darkMode: "class",
                theme: {
                    extend: {
                        colors: {
                            primary: "#137fec",
                            "background-light": "#f6f7f8",
                            "background-dark": "#101922",
                        },
                        fontFamily: {
                            display: ["Manrope", "sans-serif"],
                        },
                        borderRadius: {
                            DEFAULT: "0.25rem",
                            lg: "0.5rem",
                            xl: "0.75rem",
                            full: "9999px",
                        },
                    },
                },
            };
        </script>
        <style>
            body {
                font-family: "Manrope", sans-serif;
            }
            .material-symbols-outlined {
                font-variation-settings:
                    "FILL" 0,
                    "wght" 400,
                    "GRAD" 0,
                    "opsz" 24;
            }
        </style>
    </head>
    <body
        class="bg-background-light dark:bg-background-dark text-[#111418] dark:text-white transition-colors duration-300"
    >
        <!-- TopNavBar -->
        <header
            class="sticky top-0 z-50 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md border-b border-solid border-[#f0f2f4] dark:border-[#2a343d]"
        >
            <div
                class="max-w-[1280px] mx-auto flex items-center justify-between px-10 py-3"
            >
                <div class="flex items-center gap-4 text-primary">
                    <div class="size-6">
                        <span
                            class="material-symbols-outlined text-3xl font-bold"
                            >bolt</span
                        >
                    </div>
                    <h2
                        class="text-[#111418] dark:text-white text-xl font-extrabold leading-tight tracking-[-0.015em]"
                    >
                        KiosPay API
                    </h2>
                </div>
                <div class="flex flex-1 justify-end gap-8 items-center">
                    <nav class="hidden md:flex items-center gap-9">
                        <a
                            class="text-[#111418] dark:text-gray-300 text-sm font-semibold hover:text-primary transition-colors"
                            href="#"
                            >Features</a
                        >
                        <a
                            class="text-[#111418] dark:text-gray-300 text-sm font-semibold hover:text-primary transition-colors"
                            href="/docs/api"
                            >Documentation</a
                        >
                        <a
                            class="text-[#111418] dark:text-gray-300 text-sm font-semibold hover:text-primary transition-colors"
                            href="#"
                            >Pricing</a
                        >
                        <a
                            class="text-[#111418] dark:text-gray-300 text-sm font-semibold hover:text-primary transition-colors"
                            href="#"
                            >About</a
                        >
                    </nav>
                    <div class="flex gap-3">
                        <button
                            class="flex min-w-[84px] cursor-pointer items-center justify-center rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold hover:bg-primary/90 transition-all"
                        >
                            Get Started
                        </button>
                        <button
                            class="flex min-w-[84px] cursor-pointer items-center justify-center rounded-lg h-10 px-4 bg-[#f0f2f4] dark:bg-[#2a343d] text-[#111418] dark:text-white text-sm font-bold hover:bg-[#e2e5e9] dark:hover:bg-[#36434e] transition-all"
                        >
                            Login
                        </button>
                    </div>
                </div>
            </div>
        </header>
        <main class="max-w-[1280px] mx-auto px-4 sm:px-10 lg:px-20">
            <!-- HeroSection -->
            <section class="py-12 md:py-20 @container">
                <div class="flex flex-col gap-10 lg:flex-row items-center">
                    <div class="flex flex-col gap-6 lg:w-1/2">
                        <div class="flex flex-col gap-4 text-left">
                            <span
                                class="bg-primary/10 text-primary px-3 py-1 rounded-full text-xs font-bold w-fit uppercase tracking-wider"
                                >New V3.0 is out</span
                            >
                            <h1
                                class="text-[#111418] dark:text-white text-4xl font-extrabold leading-tight tracking-[-0.033em] md:text-6xl"
                            >
                                Empower Your Business with Our Robust PPOB API
                            </h1>
                            <p
                                class="text-[#4f5b66] dark:text-gray-400 text-lg font-normal leading-relaxed max-w-[540px]"
                            >
                                Seamless integration for airtime, data,
                                electricity, and Kios payments. One API to
                                access all Indonesian Kiosers. Fast, secure, and
                                built for scale.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-4 pt-4">
                            <button
                                class="flex min-w-[160px] cursor-pointer items-center justify-center rounded-lg h-14 px-6 bg-primary text-white text-base font-bold shadow-lg shadow-primary/20 hover:scale-105 transition-transform"
                            >
                                Get Started Free
                            </button>
                            <a 
                                href="/docs/api"
                                class="flex min-w-[160px] cursor-pointer items-center justify-center rounded-lg h-14 px-6 bg-white dark:bg-[#1a2632] border border-[#dbe0e6] dark:border-[#2a343d] text-[#111418] dark:text-white text-base font-bold hover:bg-gray-50 dark:hover:bg-[#233140] transition-colors"
                            >
                                View API Docs
                            </a>
                        </div>
                        <div class="flex items-center gap-2 mt-4">
                            <div class="flex -space-x-2">
                                <img
                                    class="size-8 rounded-full border-2 border-white dark:border-background-dark bg-gray-200"
                                    data-alt="User avatar 1"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuBdRhHkBythle7d6Y1O2ITaOxQpDJb3-owkAOqlZwBqAFURTC5KTwrMuJ4pWRrI13Ckx_s7kd_DCKwUZ-pFHQWoBfVduTpPylba9pbVRdy-MyWA-RRODxwU1-mNLN-wkIeZ1BV4Rs2SRH1Qi_RKMgCPw5fkybt_SRZvtIvhBcAfAxkACvpZ4_YVZQptfhS752D5KxwmcbUOA61fXN67ZLcHxX_4jc2f-at2zeAQliY5H90h9SWnKGW97LwdsCC9FCZA68soIrFUW_RP"
                                />
                                <img
                                    class="size-8 rounded-full border-2 border-white dark:border-background-dark bg-gray-200"
                                    data-alt="User avatar 2"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuDrF1Z9xFL6N8DNOE3W27Bmq0ObkL3zN7BgYa3iUifFlFkdqnGYEQiibIyXg9Ulhca_oq7kK8jaNtIdYo1Bh5mxYn9Dww_JeFgPDIbqvAdacybJd2lnAH-nFgwbewnnUcVuB4P_95eIX3HsHANViR2W13zolwuK7KdLFdV665gXswdXCDU7HtCqvK34mQMtvhUX9sT5ZqqmS8lWvnPdwlKGlqqzBVa2xmY-SNbuTZd5ndp3bpTblwsUfbrVbWTPxwlCzv7KpSWJfp55"
                                />
                                <img
                                    class="size-8 rounded-full border-2 border-white dark:border-background-dark bg-gray-200"
                                    data-alt="User avatar 3"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuDWXCkmFYyK-KVhwLRE3Ks5nMzCsbJLyr8WJwy4Ah0-AHBPU1MklHiKyHDxmg8K1srhIgqnkkXsBux4zyu_pbtGE9Eb3bdWfPeX8afJ4-gOBm3F4MxBX2t-xfsY3awYjzhx_cHrASbiOozGZFo-IfpyY92crcE8AxSlZBWg80SmcBQEcVB5Lz-19GbHQH_Cv672M3x1ue8Gis_R9FmOfztyM6cYeeLnNZdjXr_9ixy0BBJuYrIBy7LNlTgfV-jEFnjj4sVt9YWynZk4"
                                />
                            </div>
                            <p
                                class="text-sm text-[#4f5b66] dark:text-gray-400 font-medium"
                            >
                                Joined by 12,000+ developers globally
                            </p>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 flex justify-center">
                        <div
                            class="relative w-full max-w-[500px] aspect-square rounded-2xl bg-gradient-to-br from-primary to-[#0e5db3] p-6 shadow-2xl overflow-hidden group"
                        >
                            <!-- Abstract Code Graphic -->
                            <div
                                class="absolute inset-0 opacity-20 pointer-events-none"
                                style="
                                    background-image: radial-gradient(
                                        circle at 2px 2px,
                                        white 1px,
                                        transparent 0
                                    );
                                    background-size: 24px 24px;
                                "
                            ></div>
                            <div
                                class="relative h-full bg-[#0d1117] rounded-xl p-5 font-mono text-sm overflow-hidden border border-white/10"
                            >
                                <div class="flex gap-2 mb-4">
                                    <div
                                        class="size-3 rounded-full bg-red-500"
                                    ></div>
                                    <div
                                        class="size-3 rounded-full bg-yellow-500"
                                    ></div>
                                    <div
                                        class="size-3 rounded-full bg-green-500"
                                    ></div>
                                </div>
                                <div class="text-[#c9d1d9] space-y-2">
                                    <p>
                                        <span class="text-[#ff7b72]">POST</span>
                                        /api/v3/transactions
                                        <span class="text-[#79c0ff]">{</span>
                                    </p>
                                    <p class="pl-4">
                                        <span class="text-[#7ee787]"
                                            >"service"</span
                                        >:
                                        <span class="text-[#a5d6ff]"
                                            >"PLN_POSTPAID"</span
                                        >,
                                    </p>
                                    <p class="pl-4">
                                        <span class="text-[#7ee787]"
                                            >"customer_id"</span
                                        >:
                                        <span class="text-[#a5d6ff]"
                                            >"530012345678"</span
                                        >,
                                    </p>
                                    <p class="pl-4">
                                        <span class="text-[#7ee787]"
                                            >"amount"</span
                                        >:
                                        <span class="text-[#a5d6ff]"
                                            >150000</span
                                        >,
                                    </p>
                                    <p class="pl-4">
                                        <span class="text-[#7ee787]"
                                            >"callback_url"</span
                                        >:
                                        <span class="text-[#a5d6ff]"
                                            >"https://your.app/webhook"</span
                                        >
                                    </p>
                                    <p><span class="text-[#79c0ff]">}</span></p>
                                    <div
                                        class="pt-4 border-t border-white/10 mt-4"
                                    >
                                        <p class="text-gray-500">
                                            // Response 200 OK
                                        </p>
                                        <p>
                                            <span class="text-[#79c0ff]"
                                                >{</span
                                            >
                                        </p>
                                        <p class="pl-4">
                                            <span class="text-[#7ee787]"
                                                >"status"</span
                                            >:
                                            <span class="text-[#a5d6ff]"
                                                >"SUCCESS"</span
                                            >,
                                        </p>
                                        <p class="pl-4">
                                            <span class="text-[#7ee787]"
                                                >"ref_id"</span
                                            >:
                                            <span class="text-[#a5d6ff]"
                                                >"TX-99120"</span
                                            >
                                        </p>
                                        <p>
                                            <span class="text-[#79c0ff]"
                                                >}</span
                                            >
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!-- Floating Card -->
                            <div
                                class="absolute bottom-10 -right-4 bg-white dark:bg-[#1a2632] p-4 rounded-xl shadow-xl flex items-center gap-3 border border-[#f0f2f4] dark:border-[#2a343d]"
                            >
                                <div
                                    class="size-10 rounded-full bg-green-100 flex items-center justify-center"
                                >
                                    <span
                                        class="material-symbols-outlined text-green-600"
                                        >check_circle</span
                                    >
                                </div>
                                <div>
                                    <p
                                        class="text-xs text-gray-500 uppercase font-bold tracking-tight"
                                    >
                                        System Status
                                    </p>
                                    <p
                                        class="text-[#111418] dark:text-white font-bold"
                                    >
                                        Operational
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Stats Section -->
            <section
                class="py-10 border-y border-[#f0f2f4] dark:border-[#2a343d]"
            >
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        class="flex flex-col gap-2 rounded-xl p-8 bg-white dark:bg-[#1a2632] border border-[#dbe0e6] dark:border-[#2a343d] hover:border-primary transition-colors"
                    >
                        <p
                            class="text-[#4f5b66] dark:text-gray-400 text-sm font-semibold uppercase tracking-wider"
                        >
                            Businesses Trust Us
                        </p>
                        <p
                            class="text-[#111418] dark:text-white tracking-tighter text-4xl font-extrabold"
                        >
                            1,500+
                        </p>
                        <div
                            class="w-10 h-1 bg-primary rounded-full mt-2"
                        ></div>
                    </div>
                    <div
                        class="flex flex-col gap-2 rounded-xl p-8 bg-white dark:bg-[#1a2632] border border-[#dbe0e6] dark:border-[#2a343d] hover:border-primary transition-colors"
                    >
                        <p
                            class="text-[#4f5b66] dark:text-gray-400 text-sm font-semibold uppercase tracking-wider"
                        >
                            Monthly Volume
                        </p>
                        <p
                            class="text-[#111418] dark:text-white tracking-tighter text-4xl font-extrabold"
                        >
                            2.4M+
                        </p>
                        <div
                            class="w-10 h-1 bg-primary rounded-full mt-2"
                        ></div>
                    </div>
                    <div
                        class="flex flex-col gap-2 rounded-xl p-8 bg-white dark:bg-[#1a2632] border border-[#dbe0e6] dark:border-[#2a343d] hover:border-primary transition-colors"
                    >
                        <p
                            class="text-[#4f5b66] dark:text-gray-400 text-sm font-semibold uppercase tracking-wider"
                        >
                            Uptime Guarantee
                        </p>
                        <p
                            class="text-[#111418] dark:text-white tracking-tighter text-4xl font-extrabold"
                        >
                            99.98%
                        </p>
                        <div
                            class="w-10 h-1 bg-primary rounded-full mt-2"
                        ></div>
                    </div>
                </div>
            </section>
            <!-- FeatureSection -->
            <section class="py-20 @container" id="features">
                <div class="flex flex-col gap-12">
                    <div class="flex flex-col gap-4 text-center items-center">
                        <h2
                            class="text-[#111418] dark:text-white tracking-tight text-3xl font-extrabold md:text-5xl max-w-[800px]"
                        >
                            Engineered for Technical Excellence
                        </h2>
                        <p
                            class="text-[#4f5b66] dark:text-gray-400 text-lg font-normal leading-relaxed max-w-[640px]"
                        >
                            Built by developers for developers, our
                            infrastructure ensures reliability at every scale
                            with enterprise-grade tooling.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div
                            class="flex flex-col gap-4 rounded-xl border border-[#dbe0e6] dark:border-[#2a343d] bg-white dark:bg-[#1a2632] p-8 shadow-sm hover:shadow-xl transition-all group"
                        >
                            <div
                                class="size-14 rounded-lg bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors"
                            >
                                <span class="material-symbols-outlined text-3xl"
                                    >cloud_done</span
                                >
                            </div>
                            <div class="flex flex-col gap-2">
                                <h3
                                    class="text-[#111418] dark:text-white text-xl font-bold"
                                >
                                    Ultra-High Availability
                                </h3>
                                <p
                                    class="text-[#617589] dark:text-gray-400 text-base font-normal leading-relaxed"
                                >
                                    Multi-region deployment across top tier data
                                    centers ensures your services never go
                                    offline.
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex flex-col gap-4 rounded-xl border border-[#dbe0e6] dark:border-[#2a343d] bg-white dark:bg-[#1a2632] p-8 shadow-sm hover:shadow-xl transition-all group"
                        >
                            <div
                                class="size-14 rounded-lg bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors"
                            >
                                <span class="material-symbols-outlined text-3xl"
                                    >lock</span
                                >
                            </div>
                            <div class="flex flex-col gap-2">
                                <h3
                                    class="text-[#111418] dark:text-white text-xl font-bold"
                                >
                                    Secure Transactions
                                </h3>
                                <p
                                    class="text-[#617589] dark:text-gray-400 text-base font-normal leading-relaxed"
                                >
                                    Bank-grade encryption for every API request
                                    with PCI-DSS level security protocols.
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex flex-col gap-4 rounded-xl border border-[#dbe0e6] dark:border-[#2a343d] bg-white dark:bg-[#1a2632] p-8 shadow-sm hover:shadow-xl transition-all group"
                        >
                            <div
                                class="size-14 rounded-lg bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors"
                            >
                                <span class="material-symbols-outlined text-3xl"
                                    >terminal</span
                                >
                            </div>
                            <div class="flex flex-col gap-2">
                                <h3
                                    class="text-[#111418] dark:text-white text-xl font-bold"
                                >
                                    Developer SDKs
                                </h3>
                                <p
                                    class="text-[#617589] dark:text-gray-400 text-base font-normal leading-relaxed"
                                >
                                    Official libraries for Node.js, Python, PHP,
                                    and Go to get you integrated in minutes.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Supported Services Section Header -->
            <section class="pb-16">
                <div
                    class="bg-gray-50 dark:bg-[#1a2632] rounded-2xl p-10 lg:p-16 border border-[#dbe0e6] dark:border-[#2a343d]"
                >
                    <h2
                        class="text-[#111418] dark:text-white text-3xl font-extrabold leading-tight tracking-tight mb-8 text-center"
                    >
                        Comprehensive Service Coverage
                    </h2>
                    <div
                        class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-8"
                    >
                        <div
                            class="flex flex-col items-center gap-3 group grayscale hover:grayscale-0 transition-all cursor-pointer"
                        >
                            <div
                                class="size-16 rounded-full bg-white dark:bg-background-dark shadow-sm border border-gray-100 dark:border-gray-800 flex items-center justify-center"
                            >
                                <span
                                    class="material-symbols-outlined text-primary text-3xl"
                                    >cell_tower</span
                                >
                            </div>
                            <span
                                class="text-sm font-bold text-[#111418] dark:text-white"
                                >Telkomsel</span
                            >
                        </div>
                        <div
                            class="flex flex-col items-center gap-3 group grayscale hover:grayscale-0 transition-all cursor-pointer"
                        >
                            <div
                                class="size-16 rounded-full bg-white dark:bg-background-dark shadow-sm border border-gray-100 dark:border-gray-800 flex items-center justify-center"
                            >
                                <span
                                    class="material-symbols-outlined text-primary text-3xl"
                                    >bolt</span
                                >
                            </div>
                            <span
                                class="text-sm font-bold text-[#111418] dark:text-white"
                                >PLN Prepaid</span
                            >
                        </div>
                        <div
                            class="flex flex-col items-center gap-3 group grayscale hover:grayscale-0 transition-all cursor-pointer"
                        >
                            <div
                                class="size-16 rounded-full bg-white dark:bg-background-dark shadow-sm border border-gray-100 dark:border-gray-800 flex items-center justify-center"
                            >
                                <span
                                    class="material-symbols-outlined text-primary text-3xl"
                                    >water_drop</span
                                >
                            </div>
                            <span
                                class="text-sm font-bold text-[#111418] dark:text-white"
                                >PDAM</span
                            >
                        </div>
                        <div
                            class="flex flex-col items-center gap-3 group grayscale hover:grayscale-0 transition-all cursor-pointer"
                        >
                            <div
                                class="size-16 rounded-full bg-white dark:bg-background-dark shadow-sm border border-gray-100 dark:border-gray-800 flex items-center justify-center"
                            >
                                <span
                                    class="material-symbols-outlined text-primary text-3xl"
                                    >wifi</span
                                >
                            </div>
                            <span
                                class="text-sm font-bold text-[#111418] dark:text-white"
                                >Internet</span
                            >
                        </div>
                        <div
                            class="flex flex-col items-center gap-3 group grayscale hover:grayscale-0 transition-all cursor-pointer"
                        >
                            <div
                                class="size-16 rounded-full bg-white dark:bg-background-dark shadow-sm border border-gray-100 dark:border-gray-800 flex items-center justify-center"
                            >
                                <span
                                    class="material-symbols-outlined text-primary text-3xl"
                                    >stethoscope</span
                                >
                            </div>
                            <span
                                class="text-sm font-bold text-[#111418] dark:text-white"
                                >BPJS</span
                            >
                        </div>
                        <div
                            class="flex flex-col items-center gap-3 group grayscale hover:grayscale-0 transition-all cursor-pointer"
                        >
                            <div
                                class="size-16 rounded-full bg-white dark:bg-background-dark shadow-sm border border-gray-100 dark:border-gray-800 flex items-center justify-center"
                            >
                                <span
                                    class="material-symbols-outlined text-primary text-3xl"
                                    >live_tv</span
                                >
                            </div>
                            <span
                                class="text-sm font-bold text-[#111418] dark:text-white"
                                >TV Cable</span
                            >
                        </div>
                    </div>
                    <div class="mt-12 text-center">
                        <p
                            class="text-[#617589] dark:text-gray-400 font-medium mb-6 italic"
                        >
                            ...and 200+ more Kiosers across all categories.
                        </p>
                       
                    </div>
                </div>
            </section>
            <!-- CTA Final Section -->
            <section class="py-20">
                <div
                    class="bg-primary rounded-3xl p-10 md:p-20 relative overflow-hidden flex flex-col items-center text-center gap-8"
                >
                    <div
                        class="absolute -top-10 -right-10 size-60 rounded-full bg-white/10 blur-3xl"
                    ></div>
                    <div
                        class="absolute -bottom-10 -left-10 size-60 rounded-full bg-black/10 blur-3xl"
                    ></div>
                    <h2
                        class="text-white text-3xl md:text-5xl font-extrabold max-w-[720px] relative z-10"
                    >
                        Ready to Scale Your Payment Infrastructure?
                    </h2>
                    <p
                        class="text-white/80 text-lg font-medium max-w-[600px] relative z-10"
                    >
                        Join thousands of companies leveraging KiosPay API to
                        power their transactions. Create a free developer
                        account and start building today.
                    </p>
                    <div
                        class="flex flex-wrap justify-center gap-4 relative z-10"
                    >
                        <button
                            class="bg-white text-primary px-8 h-14 rounded-xl font-extrabold hover:scale-105 transition-transform shadow-xl"
                        >
                            Start Building for Free
                        </button>
                        <button
                            class="bg-primary/20 backdrop-blur-md text-white border border-white/20 px-8 h-14 rounded-xl font-extrabold hover:bg-white/10 transition-colors"
                        >
                            Talk to Sales
                        </button>
                    </div>
                </div>
            </section>
        </main>
        <!-- Footer -->
        <footer
            class="bg-white dark:bg-background-dark border-t border-[#f0f2f4] dark:border-[#2a343d] py-16"
        >
            <div class="max-w-[1280px] mx-auto px-10">
                <div
                    class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-12"
                >
                    <div class="col-span-2 lg:col-span-2">
                        <div class="flex items-center gap-2 text-primary mb-6">
                            <span
                                class="material-symbols-outlined text-2xl font-bold"
                                >bolt</span
                            >
                            <h2
                                class="text-xl font-extrabold text-[#111418] dark:text-white"
                            >
                                KiosPay API
                            </h2>
                        </div>
                        <p
                            class="text-[#617589] dark:text-gray-400 max-w-[320px] mb-6"
                        >
                            The ultimate infrastructure for digital payments and
                            Kioss in Indonesia. Built for scale, security, and
                            developer happiness.
                        </p>
                        <div class="flex gap-4">
                            <a
                                class="size-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center hover:bg-primary hover:text-white transition-all"
                                href="#"
                            >
                                <span class="material-symbols-outlined text-xl"
                                    >share</span
                                >
                            </a>
                            <a
                                class="size-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center hover:bg-primary hover:text-white transition-all"
                                href="#"
                            >
                                <span class="material-symbols-outlined text-xl"
                                    >public</span
                                >
                            </a>
                        </div>
                    </div>
                    <div>
                        <h4
                            class="font-bold text-[#111418] dark:text-white mb-6"
                        >
                            Product
                        </h4>
                        <ul
                            class="space-y-4 text-sm text-[#617589] dark:text-gray-400 font-medium"
                        >
                            <li>
                                <a
                                    class="hover:text-primary transition-colors"
                                    href="#"
                                    >API Pricing</a
                                >
                            </li>
                            <li>
                                <a
                                    class="hover:text-primary transition-colors"
                                    href="/docs/api"
                                    >Documentation</a
                                >
                            </li>
                            <li>
                                <a
                                    class="hover:text-primary transition-colors"
                                    href="#"
                                    >Service List</a
                                >
                            </li>
                            <li>
                                <a
                                    class="hover:text-primary transition-colors"
                                    href="#"
                                    >Status Page</a
                                >
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4
                            class="font-bold text-[#111418] dark:text-white mb-6"
                        >
                            Company
                        </h4>
                        <ul
                            class="space-y-4 text-sm text-[#617589] dark:text-gray-400 font-medium"
                        >
                            <li>
                                <a
                                    class="hover:text-primary transition-colors"
                                    href="#"
                                    >About Us</a
                                >
                            </li>
                            <li>
                                <a
                                    class="hover:text-primary transition-colors"
                                    href="#"
                                    >Customers</a
                                >
                            </li>
                            <li>
                                <a
                                    class="hover:text-primary transition-colors"
                                    href="#"
                                    >Press Kit</a
                                >
                            </li>
                            <li>
                                <a
                                    class="hover:text-primary transition-colors"
                                    href="#"
                                    >Careers</a
                                >
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4
                            class="font-bold text-[#111418] dark:text-white mb-6"
                        >
                            Legal
                        </h4>
                        <ul
                            class="space-y-4 text-sm text-[#617589] dark:text-gray-400 font-medium"
                        >
                            <li>
                                <a
                                    class="hover:text-primary transition-colors"
                                    href="#"
                                    >Privacy Policy</a
                                >
                            </li>
                            <li>
                                <a
                                    class="hover:text-primary transition-colors"
                                    href="#"
                                    >Terms of Service</a
                                >
                            </li>
                            <li>
                                <a
                                    class="hover:text-primary transition-colors"
                                    href="#"
                                    >Security</a
                                >
                            </li>
                        </ul>
                    </div>
                </div>
                <div
                    class="mt-16 pt-8 border-t border-[#f0f2f4] dark:border-[#2a343d] flex flex-col md:flex-row justify-between items-center gap-4"
                >
                    <p class="text-sm text-[#617589] dark:text-gray-400">
                        © 2023 KiosPay API Solutions. All rights reserved.
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="size-2 rounded-full bg-green-500"></span>
                        <p
                            class="text-xs font-bold text-green-600 uppercase tracking-widest"
                        >
                            All Systems Operational
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>
