<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cattleya Memorials & Perpetual Care | Official Catalog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1c5f66',      /* Heritage Teal */
                        primaryLight: '#2e8089',
                        primaryDark: '#114146',  /* Teal Dark */
                        gold: '#a6ce39',         /* Living Lime */
                        goldDeep: '#6c8625',     /* Lime, deepened for legible fine print on light backgrounds */
                        goldLight: '#d9ea9e',
                        canvas: '#f8fafc',       /* Main background */
                        dark: '#10262a',
                        accent: '#eef5f3'        /* Soft Sage-Teal Tint */
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        serif: ['Fraunces', 'serif'],
                        display: ['Fraunces', 'serif'], /* For ultra-premium headers */
                    },
                    boxShadow: {
                        'premium': '0 20px 45px -12px rgba(17, 65, 70, 0.14)',
                        'soft': '0 8px 30px rgba(17, 65, 70, 0.05)',
                        'gold-glow': '0 0 24px rgba(166, 206, 57, 0.30)',
                        'lift': '0 26px 48px -14px rgba(17, 65, 70, 0.24)',
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --c-teal: #1c5f66;
            --c-teal-dark: #114146;
            --c-lime: #a6ce39;
            --bg-main: #f8fafc;
            /* extended tokens, derived from the palette above */
            --c-teal-light: #2e8089;
            --c-lime-deep: #6c8625;
            --c-lime-light: #d9ea9e;
            --c-ink: #10262a;
            --c-sage: #eef5f3;
        }
        html {
            scroll-behavior: smooth;
        }
        body {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%231c5f66' fill-opacity='0.025'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .glass {
            background: rgba(248, 250, 252, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .gradient-text {
            background: linear-gradient(135deg, #114146 0%, #1c5f66 55%, #a6ce39 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #eef5f3;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #a6ce39;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #7d9b2b;
        }
        .gold-border-top {
            position: relative;
        }
        .gold-border-top::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #114146, #a6ce39, #114146);
        }
        /* Visible keyboard focus */
        a:focus-visible, button:focus-visible {
            outline: 2px solid #a6ce39;
            outline-offset: 3px;
            border-radius: 2px;
        }
        /* Scroll-triggered reveal */
        .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.8s cubic-bezier(0.16,1,0.3,1), transform 0.8s cubic-bezier(0.16,1,0.3,1);
        }
        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        @media (prefers-reduced-motion: reduce) {
            .reveal { opacity: 1; transform: none; transition: none; }
            html { scroll-behavior: auto; }
        }
        /* Mobile navigation panel */
        #mobileMenu {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.16,1,0.3,1), opacity 0.3s ease;
        }
        #mobileMenu.is-open {
            max-height: 34rem;
            opacity: 1;
        }
    </style>
</head>
<body class="bg-canvas text-dark font-sans selection:bg-gold/30 selection:text-primary overflow-x-hidden antialiased">

    <div class="bg-primary text-goldLight text-[10px] sm:text-[11px] tracking-[0.25em] text-center py-3 font-semibold px-4 uppercase border-b border-gold/20 relative z-50 flex items-center justify-center gap-2">
        <svg class="w-3 h-3 shrink-0 text-gold" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.9 6.4L20 10l-6.1 1.6L12 18l-1.9-6.4L4 10l6.1-1.6L12 2z"/></svg>
        <span>Plan Ahead, Prepare Ahead &amp; Pay Ahead. Every Someday Needs A Plan. Catalog Valid: July 1 – June 30, 2026.</span>
    </div>

    <header class="sticky top-0 z-50 glass border-b border-primary/5 shadow-soft transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-24 flex items-center justify-between">
            <div class="flex items-center space-x-4 group cursor-pointer">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary to-primaryDark flex items-center justify-center text-gold font-display font-bold text-2xl shadow-lg ring-1 ring-gold/30 transition-transform duration-500 group-hover:rotate-12">
                    C
                </div>
                <div>
                    <span class="font-display text-2xl font-bold tracking-wider text-primary block leading-none">CATTLEYA</span>
                    <span class="text-[9px] tracking-[0.3em] uppercase text-goldDeep block font-semibold mt-1.5">Garden &amp; Memorial Park</span>
                </div>
            </div>
            
            <nav class="hidden xl:flex items-center space-x-8 text-[12px] font-semibold tracking-widest uppercase text-primary/70">
                <a href="#lawn-plots" class="hover:text-gold transition-colors relative after:absolute after:-bottom-2 after:left-0 after:w-0 after:h-[1px] after:bg-gold hover:after:w-full after:transition-all after:duration-300">Lawn Plots</a>
                <a href="#wall-niches" class="hover:text-gold transition-colors relative after:absolute after:-bottom-2 after:left-0 after:w-0 after:h-[1px] after:bg-gold hover:after:w-full after:transition-all after:duration-300">Wall Niches</a>
                <a href="#ossuaries" class="hover:text-gold transition-colors relative after:absolute after:-bottom-2 after:left-0 after:w-0 after:h-[1px] after:bg-gold hover:after:w-full after:transition-all after:duration-300">Ossuaries</a>
                <a href="#cinerarium" class="hover:text-gold transition-colors relative after:absolute after:-bottom-2 after:left-0 after:w-0 after:h-[1px] after:bg-gold hover:after:w-full after:transition-all after:duration-300">Cinerarium</a>
                <a href="#garden-estates" class="hover:text-gold transition-colors relative after:absolute after:-bottom-2 after:left-0 after:w-0 after:h-[1px] after:bg-gold hover:after:w-full after:transition-all after:duration-300">Estates</a>
                <a href="#chapels" class="hover:text-gold transition-colors relative after:absolute after:-bottom-2 after:left-0 after:w-0 after:h-[1px] after:bg-gold hover:after:w-full after:transition-all after:duration-300">Chapels</a>
            </nav>

            <div class="flex items-center space-x-2 sm:space-x-4">
                <a href="tel:09479999374" class="hidden sm:inline-flex items-center text-xs font-bold text-primary px-4 py-2 hover:text-gold transition-all tracking-wider">
                    0947-9999-374
                </a>
                <a href="/login" class="bg-gold text-primary font-bold px-6 py-3 rounded-full border border-gold hover:bg-transparent hover:text-gold transition-all duration-300 text-[11px] tracking-[0.15em] uppercase shadow-gold-glow hover:shadow-lift">
                    Login Now
                </a>
                <button id="menuToggle" type="button" aria-controls="mobileMenu" aria-expanded="false" aria-label="Open menu" class="xl:hidden relative w-11 h-11 shrink-0 flex items-center justify-center rounded-full border border-primary/15 text-primary hover:border-gold/50 hover:text-gold transition-colors">
                    <svg id="iconMenuOpen" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    <svg id="iconMenuClose" class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
        </div>

        <div id="mobileMenu" class="xl:hidden border-t border-primary/10 bg-canvas/98">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 py-6 flex flex-col text-sm font-semibold tracking-wide uppercase text-primary/80 divide-y divide-primary/5">
                <a href="#lawn-plots" class="py-3.5 hover:text-gold transition-colors">Lawn Plots</a>
                <a href="#wall-niches" class="py-3.5 hover:text-gold transition-colors">Wall Niches</a>
                <a href="#ossuaries" class="py-3.5 hover:text-gold transition-colors">Ossuaries</a>
                <a href="#cinerarium" class="py-3.5 hover:text-gold transition-colors">Cinerarium</a>
                <a href="#garden-estates" class="py-3.5 hover:text-gold transition-colors">Estates</a>
                <a href="#chapels" class="py-3.5 hover:text-gold transition-colors">Chapels</a>
                <a href="tel:09479999374" class="py-3.5 text-gold flex items-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    0947-9999-374
                </a>
            </nav>
        </div>
    </header>

    <section class="relative overflow-hidden pt-24 pb-32 lg:pt-32 lg:pb-40">
        <div class="absolute inset-0 z-0 opacity-20 pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[500px] h-[500px] bg-gold rounded-full mix-blend-multiply filter blur-[100px] opacity-30 animate-pulse"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[500px] h-[500px] bg-primary rounded-full mix-blend-multiply filter blur-[100px] opacity-20"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-16 lg:gap-12 items-center">
            <div class="lg:col-span-7 space-y-8 text-center lg:text-left reveal is-visible">
                <div class="inline-flex items-center space-x-2 bg-white/50 backdrop-blur-sm border border-gold/30 px-5 py-2 rounded-full text-[10px] font-bold text-primary tracking-[0.2em] uppercase shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-gold"></span>
                    <span>Secure Your Family’s Peace of Mind</span>
                </div>
                <h1 class="font-display text-5xl sm:text-6xl lg:text-7xl text-primary font-bold tracking-tight leading-[1.1]">
                    A Legacy of Love, <br><span class="italic font-serif font-light gradient-text">Preserved Forever</span>
                </h1>
                <p class="text-base sm:text-lg text-dark/70 max-w-2xl mx-auto lg:mx-0 leading-relaxed font-light">
                    Providing experiential memorial celebrations and cost-effective real estate choices. Pre-plan today with continuous security, exceptional layouts, and zero-interest protections.
                </p>
                
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 max-w-xl mx-auto lg:mx-0">
                    <div class="bg-white p-5 rounded-2xl border border-primary/10 shadow-sm hover:shadow-premium hover:-translate-y-1 hover:border-gold/50 transition-all duration-300">
                        <svg class="w-5 h-5 text-gold mb-2 mx-auto lg:mx-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M19 5L5 19M7.5 8a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM16.5 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/></svg>
                        <span class="block font-display font-bold text-primary text-xl">0% APR</span>
                        <span class="block text-[10px] uppercase tracking-wider text-dark/50 mt-1 font-semibold">Up to 36 Mos</span>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-primary/10 shadow-sm hover:shadow-premium hover:-translate-y-1 hover:border-gold/50 transition-all duration-300">
                        <svg class="w-5 h-5 text-gold mb-2 mx-auto lg:mx-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/></svg>
                        <span class="block font-display font-bold text-primary text-xl">120 Mos</span>
                        <span class="block text-[10px] uppercase tracking-wider text-dark/50 mt-1 font-semibold">Max Term Terms</span>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-primary/10 shadow-sm hover:shadow-premium hover:-translate-y-1 hover:border-gold/50 transition-all duration-300">
                        <svg class="w-5 h-5 text-gold mb-2 mx-auto lg:mx-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5.2-7-11a7 7 0 0114 0c0 5.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.4"/></svg>
                        <span class="block font-display font-bold text-gold text-xl">2.5 SQM</span>
                        <span class="block text-[10px] uppercase tracking-wider text-dark/50 mt-1 font-semibold">Lawn Plot Size</span>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-primary/10 shadow-sm hover:shadow-premium hover:-translate-y-1 hover:border-gold/50 transition-all duration-300">
                        <svg class="w-5 h-5 text-gold mb-2 mx-auto lg:mx-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 4v6c0 5-3.4 8.4-8 10-4.6-1.6-8-5-8-10V6l8-4z"/></svg>
                        <span class="block font-display font-bold text-primary text-xl">Perpetual</span>
                        <span class="block text-[10px] uppercase tracking-wider text-dark/50 mt-1 font-semibold">Care Included</span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-5 pt-6">
                    <a href="#all-products" class="w-full sm:w-auto text-center bg-primary text-white font-semibold px-8 py-4 rounded-none shadow-premium hover:bg-primaryLight transition-all duration-300 text-xs tracking-widest uppercase border border-primary">
                        Explore Catalog
                    </a>
                    <a href="#lock-in" class="w-full sm:w-auto text-center bg-transparent text-primary font-bold px-8 py-4 rounded-none border border-primary hover:bg-primary hover:text-white transition-all duration-300 text-xs tracking-widest uppercase">
                        Lock-In Price
                    </a>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="bg-white p-10 rounded-none border border-gold/20 shadow-premium relative">
                    <div class="absolute -top-3 -right-3 bg-gold text-primary text-[9px] font-bold px-4 py-2 uppercase tracking-[0.2em] shadow-lg">
                        Live Catalog
                    </div>
                    <h3 class="font-display text-2xl text-primary font-bold border-b border-primary/10 pb-5 mb-6">Investment Principles</h3>
                    <ul class="space-y-5 text-sm text-dark/80 font-light">
                        <li class="flex items-start space-x-4">
                            <span class="flex-shrink-0 mt-0.5 w-1.5 h-1.5 rounded-full bg-gold"></span>
                            <span><strong class="text-primary font-semibold">A Memorial Estate Plan:</strong> A stable real estate investment.</span>
                        </li>
                        <li class="flex items-start space-x-4">
                            <span class="flex-shrink-0 mt-0.5 w-1.5 h-1.5 rounded-full bg-gold"></span>
                            <span><strong class="text-primary font-semibold">A Good Life-End Celebration:</strong> Honorable, compassionate care.</span>
                        </li>
                        <li class="flex items-start space-x-4">
                            <span class="flex-shrink-0 mt-0.5 w-1.5 h-1.5 rounded-full bg-gold"></span>
                            <span><strong class="text-primary font-semibold">A Good Family Investment Priority:</strong> Protect against future cost inflation.</span>
                        </li>
                        <li class="flex items-start space-x-4">
                            <span class="flex-shrink-0 mt-0.5 w-1.5 h-1.5 rounded-full bg-gold"></span>
                            <span><strong class="text-primary font-semibold">Savings & Cost-Effective:</strong> Clear terms mapped down to the lowest tiers.</span>
                        </li>
                    </ul>
                    <div class="mt-8 bg-accent p-5 border border-primary/5 flex items-center space-x-5">
                        <div class="text-2xl text-gold">📞</div>
                        <div class="text-xs text-dark/80 leading-relaxed font-light">
                            <strong class="text-primary font-semibold block mb-1">Need Immediate Help?</strong> Call our official support line at <a href="tel:4962822" class="text-gold font-bold hover:text-primary transition-colors">496-2822</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div id="all-products" class="bg-primary py-20 text-center text-white relative border-y border-gold/30">
        <div class="absolute inset-0 bg-[radial-gradient(rgba(197,160,89,0.15)_1px,transparent_1px)] [background-size:24px_24px]"></div>
        <div class="relative z-10 max-w-7xl mx-auto px-4">
            <span class="text-gold font-bold text-[10px] uppercase tracking-[0.3em] block mb-4">Official Inventory</span>
            <h2 class="font-display text-3xl md:text-5xl font-bold tracking-tight">Product & Financing Packages</h2>
            <p class="text-white/60 max-w-xl mx-auto mt-4 text-sm font-light tracking-wide">Flexible programs spanning up to 10 years (120 months) term models.</p>
        </div>
    </div>

    <section id="lawn-plots" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 scroll-mt-24">
        <div class="flex flex-col lg:flex-row lg:items-end justify-between mb-12 border-b border-primary/10 pb-8 gap-6">
            <div>
                <div class="text-[10px] font-bold text-gold uppercase tracking-[0.3em] mb-2">Premium Inventory</div>
                <h3 class="font-display text-3xl md:text-4xl font-bold text-primary">1. Lawn Plot Packages</h3>
                <p class="text-sm text-dark/60 mt-3 font-light">Lot configuration: 2.5 SQM | Accommodates 2 Fresh Bodies & 4 Sets of Bones/Urns total (2 Tiers).</p>
            </div>
            <div class="flex flex-wrap gap-3 lg:justify-end">
                <span class="bg-primary/5 text-primary border border-primary/10 text-[10px] uppercase tracking-wider font-semibold px-4 py-2">Available: 10, 11, 12A, 17, 18, 19, 39</span>
                <span class="bg-dark/5 text-dark/50 text-[10px] uppercase tracking-wider px-4 py-2 border border-dark/5">Sold Out: 1-9, 14-16, 10A</span>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <div class="xl:col-span-2 bg-white border border-primary/10 shadow-premium overflow-hidden rounded-2xl">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm border-collapse min-w-[650px]">
                        <thead>
                            <tr class="bg-canvas text-primary font-semibold border-b border-primary/10 uppercase tracking-wider text-[10px]">
                                <th class="p-6">Plot Category</th>
                                <th class="p-6">TCP</th>
                                <th class="p-6">Cash Option</th>
                                <th class="p-6">24-36 Mos (0%)</th>
                                <th class="p-6 bg-gold/10 text-primary border-x border-gold/20 gold-border-top text-center">
                                    <span class="block text-gold font-bold mb-1">RECOMMENDED</span>
                                    60 Mos (6%)
                                </th>
                                <th class="p-6">120 Mos (8%)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-primary/5 text-dark/80 font-light">
                            <tr class="hover:bg-accent/30 transition-colors">
                                <td class="p-6 font-semibold text-primary">Regular Plot</td>
                                <td class="p-6 font-mono">₱399,300</td>
                                <td class="p-6 font-mono text-primary font-bold">₱319,440</td>
                                <td class="p-6 font-mono">₱16,638 / ₱11,092</td>
                                <td class="p-6 font-mono bg-gold/5 font-bold text-primary border-x border-gold/10 text-center shadow-inner">
                                    <span class="text-lg block text-primary mb-1">₱428,050</span>
                                    <span class="block text-[10px] text-gold font-sans font-semibold tracking-wider bg-white py-1 px-2 inline-block border border-gold/20 rounded">(₱7,134/mo)</span>
                                </td>
                                <td class="p-6 font-mono font-semibold">₱514,298 <span class="block text-[10px] text-dark/40 font-sans mt-1">(₱4,286/mo)</span></td>
                            </tr>
                            <tr class="hover:bg-accent/30 transition-colors">
                                <td class="p-6 font-semibold text-primary">Premium Plot</td>
                                <td class="p-6 font-mono">₱421,534</td>
                                <td class="p-6 font-mono text-primary font-bold">₱337,227</td>
                                <td class="p-6 font-mono">₱17,564 / ₱11,709</td>
                                <td class="p-6 font-mono bg-gold/5 font-bold text-primary border-x border-gold/10 text-center shadow-inner">
                                    <span class="text-lg block text-primary mb-1">₱451,885</span>
                                    <span class="block text-[10px] text-gold font-sans font-semibold tracking-wider bg-white py-1 px-2 inline-block border border-gold/20 rounded">(₱7,531/mo)</span>
                                </td>
                                <td class="p-6 font-mono font-semibold">₱542,936 <span class="block text-[10px] text-dark/40 font-sans mt-1">(₱4,524/mo)</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-primary text-white p-10 shadow-premium flex flex-col justify-between relative overflow-hidden rounded-2xl border border-gold/20">
                <div class="absolute top-0 right-0 w-40 h-40 bg-gold rounded-full filter blur-[60px] opacity-20 pointer-events-none"></div>
                <div class="relative z-10">
                    <span class="border border-gold/50 text-gold text-[9px] font-bold tracking-[0.2em] uppercase px-3 py-1.5 inline-block mb-6">Complete Package Bundle</span>
                    <h4 class="font-display text-2xl font-bold text-white mb-4">Lawn Plot + 2 Interment Services</h4>
                    <p class="text-sm text-white/60 leading-relaxed font-light">
                        Combine the baseline 120-month regular tier installment alongside pre-secured interment operations.
                    </p>
                    <div class="mt-8 space-y-4 border-t border-white/10 pt-6 font-mono text-sm">
                        <div class="flex justify-between text-white/80"><span class="font-sans text-xs uppercase tracking-wider">Base Plot:</span><span class="font-semibold text-gold">₱4,286 / mo</span></div>
                        <div class="flex justify-between text-white/80"><span class="font-sans text-xs uppercase tracking-wider">2 Services:</span><span class="font-semibold text-gold">+ ₱908 / mo</span></div>
                    </div>
                </div>
                <div class="mt-10 bg-primaryLight/50 p-6 border border-white/10 text-center relative z-10">
                    <span class="block text-[9px] uppercase text-white/50 tracking-[0.2em] font-semibold mb-2">Total Monthly All-In</span>
                    <span class="text-4xl font-display font-bold text-white block">₱5,194<span class="text-xs font-sans font-light text-gold ml-2 tracking-normal">/ mo</span></span>
                </div>
            </div>
        </div>
    </section>

    <section id="wall-niches" class="bg-white border-y border-primary/5 py-24 scroll-mt-24 relative">
        <div class="absolute left-0 top-0 w-64 h-full bg-accent/30 pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between mb-12 border-b border-primary/10 pb-8 gap-6">
                <div>
                    <div class="text-[10px] font-bold text-gold uppercase tracking-[0.3em] mb-2">Practical Alternatives</div>
                    <h3 class="font-display text-3xl md:text-4xl font-bold text-primary">2. Wall Niches (Tiers A, B, C, D)</h3>
                    <p class="text-sm text-dark/60 mt-3 font-light">Specially structured design for 1 Fresh Body + 2 Sets of Bones or Urns. More affordable setup.</p>
                </div>
                <div class="flex flex-wrap gap-3 lg:justify-end">
                    <span class="bg-canvas text-primary border border-primary/10 text-[10px] font-semibold tracking-wider uppercase px-4 py-2">Blocks: 32A, 33, 34, 35</span>
                    <span class="bg-gold text-primary text-[10px] font-bold px-4 py-2 tracking-wider uppercase shadow-md border border-gold">🔥 Promo: Invest 2 = 10 Yrs 0% Int</span>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <div class="xl:col-span-2 bg-white border border-primary/10 shadow-premium overflow-hidden rounded-2xl">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left text-sm border-collapse min-w-[650px]">
                            <thead>
                                <tr class="bg-canvas text-primary font-semibold border-b border-primary/10 uppercase tracking-wider text-[10px]">
                                    <th class="p-6">Tier / Model</th>
                                    <th class="p-6">TCP</th>
                                    <th class="p-6">Cash Option</th>
                                    <th class="p-6">24-36 Mos (0%)</th>
                                    <th class="p-6 bg-gold/10 text-primary border-x border-gold/20 gold-border-top text-center">
                                        <span class="block text-gold font-bold mb-1">RECOMMENDED</span>
                                        60 Mos (6%)
                                    </th>
                                    <th class="p-6">120 Mos (8%)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-primary/5 text-dark/80 font-light">
                                <tr class="hover:bg-accent/30 transition-colors">
                                    <td class="p-6 font-semibold text-primary">Regular Niche</td>
                                    <td class="p-6 font-mono">₱157,981</td>
                                    <td class="p-6 font-mono text-primary font-bold">₱126,385</td>
                                    <td class="p-6 font-mono">₱6,583 / ₱4,388</td>
                                    <td class="p-6 font-mono bg-gold/5 font-bold text-primary border-x border-gold/10 text-center shadow-inner">
                                        <span class="text-lg block text-primary mb-1">₱169,356</span>
                                        <span class="block text-[10px] text-gold font-sans font-semibold tracking-wider bg-white py-1 px-2 inline-block border border-gold/20 rounded">(₱2,823/mo)</span>
                                    </td>
                                    <td class="p-6 font-mono font-semibold">₱203,479 <span class="block text-[10px] text-dark/40 font-sans mt-1">(₱1,696/mo)</span></td>
                                </tr>
                                <tr class="hover:bg-accent/30 transition-colors">
                                    <td class="p-6 font-semibold text-primary">Premium Niche</td>
                                    <td class="p-6 font-mono">₱173,782</td>
                                    <td class="p-6 font-mono text-primary font-bold">₱139,026</td>
                                    <td class="p-6 font-mono">₱7,241 / ₱4,827</td>
                                    <td class="p-6 font-mono bg-gold/5 font-bold text-primary border-x border-gold/10 text-center shadow-inner">
                                        <span class="text-lg block text-primary mb-1">₱186,295</span>
                                        <span class="block text-[10px] text-gold font-sans font-semibold tracking-wider bg-white py-1 px-2 inline-block border border-gold/20 rounded">(₱3,105/mo)</span>
                                    </td>
                                    <td class="p-6 font-mono font-semibold">₱223,832 <span class="block text-[10px] text-dark/40 font-sans mt-1">(₱1,865/mo)</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-primaryLight text-white p-10 shadow-premium flex flex-col justify-between rounded-2xl border border-primary/20 relative">
                    <div class="absolute bottom-0 right-0 opacity-10">
                         <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M12 2L2 22h20L12 2z"/></svg>
                    </div>
                    <div class="relative z-10">
                        <span class="border border-white/20 text-white text-[9px] font-bold tracking-[0.2em] uppercase px-3 py-1.5 inline-block mb-6">Tier D Package Target</span>
                        <h4 class="font-display text-2xl font-bold mb-4">Tier D + 1 Interment</h4>
                        <p class="text-sm text-white/60 leading-relaxed font-light">
                            Budget-wise package matching baseline 120-month regular tier installment alongside individual service.
                        </p>
                        <div class="mt-8 space-y-4 border-t border-white/10 pt-6 font-mono text-sm">
                            <div class="flex justify-between text-white/90"><span class="font-sans text-xs uppercase tracking-wider">Niche Base:</span><span class="font-semibold text-gold">₱1,696 / mo</span></div>
                            <div class="flex justify-between text-white/90"><span class="font-sans text-xs uppercase tracking-wider">1 Service:</span><span class="font-semibold text-gold">+ ₱251 / mo</span></div>
                            <div class="flex justify-between text-xs text-white/40 border-t border-white/10 pt-4 font-sans tracking-wide"><span>Alternative D+D:</span><span>₱2,634 / mo total</span></div>
                        </div>
                    </div>
                    <div class="mt-10 bg-primary/40 p-6 border border-white/5 text-center relative z-10">
                        <span class="block text-[9px] uppercase text-white/50 tracking-[0.2em] font-semibold mb-2">Combined Payment</span>
                        <span class="text-4xl font-display font-bold text-white block">₱1,947<span class="text-xs font-sans font-light text-gold ml-2 tracking-normal">/ mo</span></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 grid grid-cols-1 lg:grid-cols-2 gap-10 xl:gap-14">
    
    <div id="ossuaries" class="scroll-mt-24 space-y-6 flex flex-col justify-between">
        <div class="space-y-6">
            <div class="border-b border-primary/10 pb-5">
                <span class="text-[10px] text-gold font-bold uppercase tracking-[0.3em] block mb-1">5-Tier Premium Storage</span>
                <h3 class="font-display text-2xl sm:text-3xl font-bold text-primary">3. Bone Ossuaries <span class="text-lg text-primary/50 font-sans tracking-normal font-normal">(Tiers A–E)</span></h3>
                <p class="text-xs sm:text-sm text-dark/60 mt-2 font-light">An elegant, space-efficient option designed to maximize existing lawn plots. Comfortably accommodates up to 6 sets of skeletal remains.</p>
            </div>

            <div class="bg-white border border-primary/10 shadow-sm overflow-hidden rounded-2xl">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-xs border-collapse min-w-[500px]">
                        <thead>
                            <tr class="bg-canvas text-primary font-semibold border-b border-primary/10 uppercase tracking-wider text-[9px]">
                                <th class="p-4 whitespace-nowrap">Package Tier</th>
                                <th class="p-4 whitespace-nowrap">TCP</th>
                                <th class="p-4 whitespace-nowrap">Spot Cash</th>
                                <th class="p-4 whitespace-nowrap">24 & 36 Mos <span class="text-[8px] text-dark/40 block lowercase font-normal">(0% Int.)</span></th>
                                <th class="p-4 text-primary/80 whitespace-nowrap bg-primary/[0.01]">60 Mos <span class="text-[8px] text-dark/40 block lowercase font-normal">(6% Int.)</span></th>
                                <th class="p-4 text-gold bg-gold/5 border-l border-gold/10 whitespace-nowrap">120 Mos <span class="text-[8px] text-gold/50 block lowercase font-normal">(8% Int.)</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-primary/5 text-dark/80 font-light">
                            <tr class="hover:bg-accent/30 transition-colors">
                                <td class="p-4 font-semibold text-primary whitespace-nowrap">Regular Tier</td>
                                <td class="p-4 font-mono text-dark/60">₱120,256</td>
                                <td class="p-4 font-mono text-primary font-semibold">₱96,205</td>
                                <td class="p-4 font-mono text-dark/70 whitespace-nowrap">₱5,011 <span class="text-[10px] text-dark/30">/</span> ₱3,340</td>
                                <td class="p-4 font-mono text-primary bg-primary/[0.01] whitespace-nowrap">
                                    <span class="font-medium block">₱128,914</span>
                                    <span class="text-[10px] text-dark/50 block mt-0.5">₱2,149/mo</span>
                                </td>
                                <td class="p-4 font-mono text-primary bg-primary/[0.01] whitespace-nowrap">
                                    <span class="font-medium block">₱154,890</span>
                                    <span class="text-[10px] text-dark/50 block mt-0.5">₱1,291/mo</span>
                                </td>
                            </tr>
                            <tr class="hover:bg-accent/30 transition-colors">
                                <td class="p-4 font-semibold text-primary whitespace-nowrap">Premium Tier</td>
                                <td class="p-4 font-mono text-dark/60">₱145,507</td>
                                <td class="p-4 font-mono text-primary font-semibold">₱116,406</td>
                                <td class="p-4 font-mono text-dark/70 whitespace-nowrap">₱6,063 <span class="text-[10px] text-dark/30">/</span> ₱4,042</td>
                                <td class="p-4 font-mono text-primary bg-primary/[0.01] whitespace-nowrap">
                                    <span class="font-medium block">₱155,983</span>
                                    <span class="text-[10px] text-dark/50 block mt-0.5">₱2,600/mo</span>
                                </td>
                                <td class="p-4 font-mono text-primary bg-primary/[0.01] whitespace-nowrap">
                                    <span class="font-medium block">₱187,413</span>
                                    <span class="text-[10px] text-dark/50 block mt-0.5">₱1,562/mo</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-primary text-white p-4 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 rounded-xl mt-auto">
            <div class="flex flex-col">
                <span class="font-semibold text-xs tracking-wide uppercase text-gold">Complete Value Package Plan</span>
                <span class="font-light text-[11px] text-white/70 mt-0.5">Includes: Regular Tier Structure + 1 Care Service Plan Add-on (@ ₱219/mo value)</span>
            </div>
            <div class="text-right self-end sm:self-auto shrink-0">
                <span class="text-gold font-display font-bold text-xl sm:text-2xl">₱1,510 <span class="text-[11px] font-sans font-light text-white/60">/ month</span></span>
            </div>
        </div>
    </div>

    <div id="cinerarium" class="scroll-mt-24 space-y-6 flex flex-col justify-between">
        <div class="space-y-6">
            <div class="border-b border-primary/10 pb-5">
                <span class="text-[10px] text-gold font-bold uppercase tracking-[0.3em] block mb-1">Urn Specialized Interments</span>
                <h3 class="font-display text-2xl sm:text-3xl font-bold text-primary">4. Cinerarium Niches <span class="text-xl text-primary/50 font-sans tracking-normal font-normal">(Tiers A–E)</span></h3>
                <p class="text-xs sm:text-sm text-dark/60 mt-2 font-light">Dimensions: 0.43 × 0.43 × 0.56m. Securely holds up to 6 sets of urns. Blocks 27, 29, 31.</p>
            </div>

            <div class="bg-white border border-primary/10 shadow-sm overflow-hidden rounded-2xl">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-xs border-collapse min-w-[500px]">
                        <thead>
                            <tr class="bg-canvas text-primary font-semibold border-b border-primary/10 uppercase tracking-wider text-[9px]">
                                <th class="p-4 whitespace-nowrap">Model</th>
                                <th class="p-4 whitespace-nowrap">TCP</th>
                                <th class="p-4 whitespace-nowrap">Cash</th>
                                <th class="p-4 whitespace-nowrap">24-36M <span class="text-[8px] text-dark/40 block lowercase font-normal">(0% Int.)</span></th>
                                <th class="p-4 text-primary/80 whitespace-nowrap bg-primary/[0.01]">60 Mos <span class="text-[8px] text-dark/40 block lowercase font-normal">(6% Int.)</span></th>
                                <th class="p-4 text-gold bg-gold/5 border-l border-gold/10 whitespace-nowrap">120M <span class="text-[8px] text-gold/50 block lowercase font-normal">(8% Int.)</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-primary/5 text-dark/80 font-light">
                            <tr class="hover:bg-accent/30 transition-colors">
                                <td class="p-4 font-semibold text-primary whitespace-nowrap">Regular</td>
                                <td class="p-4 font-mono text-dark/60">₱96,675</td>
                                <td class="p-4 font-mono text-primary font-semibold">₱77,340</td>
                                <td class="p-4 font-mono text-dark/70 whitespace-nowrap">₱4,028 <span class="text-[10px] text-dark/30">/</span> ₱2,685</td>
                                <td class="p-4 font-mono text-primary bg-primary/[0.01] whitespace-nowrap">
                                    <span class="font-medium block">₱103,636</span>
                                    <span class="text-[10px] text-dark/50 block mt-0.5">₱1,727/mo</span>
                                </td>
                                <td class="p-4 font-mono text-primary bg-primary/[0.01] whitespace-nowrap">
                                    <span class="font-medium block">₱124,518</span>
                                    <span class="text-[10px] text-dark/50 block mt-0.5">₱1,038/mo</span>
                                </td>
                            </tr>
                            <tr class="hover:bg-accent/30 transition-colors">
                                <td class="p-4 font-semibold text-primary whitespace-nowrap">Premium</td>
                                <td class="p-4 font-mono text-dark/60">₱106,343</td>
                                <td class="p-4 font-mono text-primary font-semibold">₱85,074</td>
                                <td class="p-4 font-mono text-dark/70 whitespace-nowrap">₱4,431 <span class="text-[10px] text-dark/30">/</span> ₱2,954</td>
                                <td class="p-4 font-mono text-primary bg-primary/[0.01] whitespace-nowrap">
                                    <span class="font-medium block">₱113,999</span>
                                    <span class="text-[10px] text-dark/50 block mt-0.5">₱1,900/mo</span>
                                </td>
                                <td class="p-4 font-mono text-primary bg-primary/[0.01] whitespace-nowrap">
                                    <span class="font-medium block">₱136,969</span>
                                    <span class="text-[10px] text-dark/50 block mt-0.5">₱1,141/mo</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-primary text-white p-4 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 rounded-xl mt-auto">
            <div class="flex flex-col">
                <span class="font-semibold text-xs tracking-wide uppercase text-gold">Complete Value Package Plan</span>
                <span class="font-light text-[11px] text-white/70 mt-0.5">Includes: Regular Tier Structure + 1 Care Service Plan Add-on (@ ₱219/mo value)</span>
            </div>
            <div class="text-right self-end sm:self-auto shrink-0">
                <span class="text-gold font-display font-bold text-xl sm:text-2xl">₱1,257 <span class="text-[11px] font-sans font-light text-white/60">/ month</span></span>
            </div>
        </div>
    </div>
</section>

<section class="bg-accent/40 border-y border-primary/5 py-24">
        <div class="max-w-4xl mx-auto px-4 text-center space-y-6">
            <span class="text-[10px] font-bold text-gold uppercase tracking-[0.3em] block">Compassionate Care For Companions</span>
            <h3 class="font-display text-3xl md:text-4xl font-bold text-primary">5. Pet Memorial Sanctuary</h3>
            <p class="text-base text-dark/60 max-w-2xl mx-auto font-light leading-relaxed">
                Dedicated placement customized according to lot owner burial preferences & maximizing long-term capacity allowances.
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-3xl mx-auto text-sm text-left pt-8">
                <div class="bg-white border border-primary/10 p-8 shadow-premium hover:border-gold/30 transition-all duration-300 flex flex-col justify-between">
                    <div>
                        <h5 class="font-display font-bold text-primary border-b border-primary/10 pb-4 mb-5 text-xl flex justify-between items-center">
                            <span>Regular Tier</span>
                        </h5>
                        <div class="space-y-3 text-dark/70 font-light font-mono text-xs sm:text-sm">
                            <div class="flex justify-between border-b border-dashed border-primary/5 pb-1"><span>Base TCP:</span><span class="font-medium text-dark">₱60,517</span></div>
                            <div class="flex justify-between text-primary font-semibold pb-1"><span>Cash Purchase:</span><span>₱48,413</span></div>
                            
                            <div class="flex justify-between text-dark/60">
                                <span>24-Month (0% Int):</span>
                                <div class="text-right">
                                    <span class="font-medium text-dark">₱2,522 / mo</span>
                                </div>
                            </div>

                            <div class="flex justify-between text-dark/50">
                                <span>36-Month (0% Int):</span>
                                <div class="text-right">
                                    <span>₱1,681 / mo</span>
                                </div>
                            </div>

                            <div class="flex justify-between text-dark/60">
                                <span>60-Month (6% Int):</span>
                                <div class="text-right">
                                    <span class="text-[10px] text-dark/40 block">TCP: ₱64,874</span>
                                    <span class="font-medium text-dark">₱1,081 / mo</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gold/10 text-primary p-3 mt-5 font-sans border border-gold/20 flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-[9px] uppercase tracking-wider font-bold">120-Month Term Plan (8% Int)</span>
                            <span class="font-mono text-[10px] text-primary/70">TCP: ₱77,946</span>
                        </div>
                        <span class="text-lg font-bold font-mono">₱650 / mo</span>
                    </div>
                </div>

                <div class="bg-white border border-primary/10 p-8 shadow-premium hover:border-gold/30 transition-all duration-300 relative overflow-hidden rounded-2xl flex flex-col justify-between">
                    <div class="absolute top-0 right-0 w-1 h-full bg-gold"></div>
                    <div>
                        <h5 class="font-display font-bold text-primary border-b border-primary/10 pb-4 mb-5 text-xl flex justify-between items-center">
                            <span>Premium Tier</span>
                        </h5>
                        <div class="space-y-3 text-dark/70 font-light font-mono text-xs sm:text-sm">
                            <div class="flex justify-between border-b border-dashed border-primary/5 pb-1"><span>Base TCP:</span><span class="font-medium text-dark">₱65,468</span></div>
                            <div class="flex justify-between text-primary font-semibold pb-1"><span>Cash Purchase:</span><span>₱52,375</span></div>
                            
                            <div class="flex justify-between text-dark/60">
                                <span>24-Month (0% Int):</span>
                                <div class="text-right">
                                    <span class="font-medium text-dark">₱2,728 / mo</span>
                                </div>
                            </div>

                            <div class="flex justify-between text-dark/50">
                                <span>36-Month (0% Int):</span>
                                <div class="text-right">
                                    <span>₱1,819 / mo</span>
                                </div>
                            </div>

                            <div class="flex justify-between text-dark/60">
                                <span>60-Month (6% Int):</span>
                                <div class="text-right">
                                    <span class="text-[10px] text-dark/40 block">TCP: ₱70,182</span>
                                    <span class="font-medium text-dark">₱1,170 / mo</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gold/10 text-primary p-3 mt-5 font-sans border border-gold/20 flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-[9px] uppercase tracking-wider font-bold">120-Month Term Plan (8% Int)</span>
                            <span class="font-mono text-[10px] text-primary/70">TCP: ₱84,323</span>
                        </div>
                        <span class="text-lg font-bold font-mono">₱703 / mo</span>
                    </div>
                </div>
            </div>
            <p class="text-[12px] text-dark/80 font-mono pt-4 tracking-widest uppercase">Add-on 1 interment service calculate at <b class="text-gold fw-bold text-[16px]">₱266</b> per monthly cycle.</p>
        </div>
    </section>

    <section id="garden-estates" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 scroll-mt-24">
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            <span class="text-[10px] font-bold text-gold uppercase tracking-[0.3em] block">Grand Structural Real Estate</span>
            <h3 class="font-display text-4xl md:text-5xl font-bold text-primary">6 & 7. Family Estates</h3>
            <p class="text-base text-dark/60 font-light leading-relaxed">Comprehensive regional blocks engineered with extended continuous footprints for multigenerational security.</p>
        </div>

        <div class="space-y-16">
            <div class="bg-white border border-primary/10 shadow-premium overflow-hidden rounded-2xl">
                <div class="bg-canvas px-8 py-6 border-b border-primary/10 flex justify-between items-center flex-wrap gap-4">
                    <h4 class="font-display text-xl font-bold text-primary">Family Garden Lots — Blocks 20, 21, 22, 24, 25</h4>
                    <span class="text-[9px] border border-gold text-gold px-3 py-1 font-bold tracking-[0.2em] uppercase">0% Int Model Included</span>
                </div>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm border-collapse font-mono min-w-[900px]">
                        <thead>
                            <tr class="bg-white text-primary/70 font-semibold border-b border-primary/5 text-center text-[10px] uppercase tracking-wider font-sans">
                                <th class="p-5 text-left text-primary">Lot Area Size</th>
                                <th class="p-5">TCP</th>
                                <th class="p-5">Cash Rate</th>
                                <th class="p-5">24-Month (0%)</th>
                                <th class="p-5">36-Month (0%)</th>
                                <th class="p-5 bg-gold/10 text-primary border-x border-gold/20 gold-border-top">
                                    <span class="block text-gold font-bold mb-1">RECOMMENDED</span>
                                    60-Month Base
                                </th>
                                <th class="p-5 text-primary">120-Month Amort</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-primary/5 text-center text-dark/70 font-light">
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">20.00 SQM</td><td>₱1,641,110</td><td class="text-primary font-medium">₱1,394,944</td><td>₱68,380</td><td>₱45,586</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱1,759,270 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱29,321/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱2,113,750<span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱17,615/mo)</span></td></tr>
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">23.16 SQM</td><td>₱1,900,405</td><td class="text-primary font-medium">₱1,615,344</td><td>₱79,184</td><td>₱52,789</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱2,037,235 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱33,954/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱2,447,722<span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱20,398/mo)</span></td></tr>
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">24.17 SQM</td><td>₱1,983,281</td><td class="text-primary font-medium">₱1,685,789</td><td>₱82,637</td><td>₱55,091</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱2,126,078 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱35,435/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱2,554,466<span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱21,287/mo)</span></td></tr>
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">30.00 SQM</td><td>₱2,461,665</td><td class="text-primary font-medium">₱2,092,415</td><td>₱102,569</td><td>₱68,380</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱2,638,905 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱43,982/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱3,170,625<span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱26,422/mo)</span></td></tr>
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">31.66 SQM</td><td>₱2,597,877</td><td class="text-primary font-medium">₱2,208,195</td><td>₱108,245</td><td>₱72,163</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱2,784,924 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱46,415/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱3,346,066<span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱27,884/mo)</span></td></tr>
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">36.94 SQM</td><td>₱3,031,130</td><td class="text-primary font-medium">₱2,576,461</td><td>₱126,297</td><td>₱84,198</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱3,249,372 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱54,156/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱3,904,096<span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱32,534/mo)</span></td></tr>
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">37.10 SQM</td><td>₱3,044,259</td><td class="text-primary font-medium">₱2,587,620</td><td>₱126,844</td><td>₱84,563</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱3,263,446 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱54,391/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱3,921,006<span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱32,675/mo)</span></td></tr>
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">40.00 SQM</td><td>₱3,282,220</td><td class="text-primary font-medium">₱2,789,887</td><td>₱136,759</td><td>₱91,173</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱3,518,540 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱58,642/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱4,227,499<span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱35,229/mo)</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white border border-primary/10 shadow-premium overflow-hidden rounded-2xl">
                <div class="bg-accent/50 px-8 py-6 border-b border-primary/10">
                    <h4 class="font-display text-xl font-bold text-primary">Family Garden Plots Exclusive — Blocks 28 and 30</h4>
                </div>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm border-collapse font-mono text-center min-w-[900px]">
                        <thead>
                            <tr class="bg-white text-primary/70 font-semibold border-b border-primary/5 text-[10px] uppercase tracking-wider font-sans">
                                <th class="p-5 text-left text-primary">Lot Area Size</th>
                                <th class="p-5">TCP</th>
                                <th class="p-5">Cash Pricing</th>
                                <th class="p-5">24-Mo (0%)</th>
                                <th class="p-5">36-Mo (0%)</th>
                                <th class="p-5 bg-gold/10 text-primary border-x border-gold/20 gold-border-top">
                                    <span class="block text-gold font-bold mb-1">RECOMMENDED</span>
                                    60-Month Base
                                </th>
                                <th class="p-5 text-primary">120-Month Model</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-primary/5 text-dark/70 font-light">
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">23.14 SQM</td><td>₱1,898,764</td><td class="text-primary font-medium">₱1,613,950</td><td>₱79,115</td><td>₱52,743</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱2,067,121 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱34,452/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱2,477,254 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱20,644/mo)</span></td></tr>
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">25.57 SQM</td><td>₱2,098,159</td><td class="text-primary font-medium">₱1,783,435</td><td>₱87,423</td><td>₱58,282</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱2,249,227 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱37,487/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱2,702,429 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱22,520/mo)</span></td></tr>
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">30.00 SQM</td><td>₱2,461,665</td><td class="text-primary font-medium">₱2,092,415</td><td>₱102,569</td><td>₱68,380</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱2,638,905 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱43,982/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱3,170,625 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱26,422/mo)</span></td></tr>
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">31.66 SQM</td><td>₱2,597,877</td><td class="text-primary font-medium">₱2,208,195</td><td>₱108,245</td><td>₱72,163</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱2,784,924 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱46,415/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱3,346,066 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱27,884/mo)</span></td></tr>
                            <tr class="hover:bg-accent/30 transition-colors"><td class="p-5 text-left font-sans font-semibold text-primary">40.00 SQM</td><td>₱3,282,220</td><td class="text-primary font-medium">₱2,789,887</td><td>₱136,759</td><td>₱91,173</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-primary shadow-inner">₱3,518,540 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱58,642/mo)</span></td><td class="font-bold border-x border-gold/10 text-primary shadow-inner">₱4,227,499 <span class="block text-[10px] text-gold font-sans font-semibold mt-1">(₱35,229/mo)</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-primary text-white border border-gold/30 shadow-2xl overflow-hidden rounded-2xl relative">
                <div class="absolute top-0 right-0 w-64 h-64 bg-gold rounded-full filter blur-[100px] opacity-10 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-gold rounded-full filter blur-[100px] opacity-10 pointer-events-none"></div>
                
                <div class="p-8 border-b border-gold/20 bg-primaryLight/30 text-center md:text-left relative z-10">
                    <span class="text-gold font-bold text-[10px] uppercase tracking-[0.3em] block mb-2">Ultimate Tier</span>
                    <h4 class="font-display text-2xl font-bold text-white">Crown Jewel Family Estates — Blocks 12, 23A–23B & 32</h4>
                    <p class="text-sm text-white/60 mt-2 font-light">Luxury memorial plots with expanded footprint customization capacities.</p>
                </div>
                <div class="overflow-x-auto custom-scrollbar relative z-10">
                    <table class="w-full text-left text-sm border-collapse font-mono text-center text-white/80 min-w-[900px]">
                        <thead>
                            <tr class="bg-primary/50 text-gold/70 font-semibold text-[10px] uppercase tracking-wider font-sans border-b border-gold/20">
                                <th class="p-5 text-left text-gold font-bold">Footprint Size</th>
                                <th class="p-5">TCP</th>
                                <th class="p-5">Cash (Discount)</th>
                                <th class="p-5">36-Mos (0%)</th>
                                <th class="p-5 bg-gold/10 text-white border-x border-gold/20 gold-border-top">
                                    <span class="block text-gold font-bold mb-1">RECOMMENDED</span>
                                    60-Month Base
                                </th>
                                <th class="p-5 text-gold font-bold">120-Month Longest</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gold/10 font-light">
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">30.84 SQM</td><td>₱2,592,366</td><td class="text-gold">₱2,203,511</td><td>₱72,010</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-white shadow-inner">₱2,635,572 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱43,926/mo)</span></td><td class="font-bold border-x border-gold/10 text-white shadow-inner">₱2,779,016 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱23,158/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">37.50 SQM</td><td>₱3,152,196</td><td class="text-gold">₱2,679,367</td><td>₱87,561</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-white shadow-inner">₱3,379,154 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱56,319/mo)</span></td><td class="font-bold border-x border-gold/10 text-white shadow-inner">₱4,060,029 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱33,834/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">40.00 SQM</td><td>₱3,362,343</td><td class="text-gold">₱2,857,992</td><td>₱93,398</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-white shadow-inner">₱3,604,431 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱60,074/mo)</span></td><td class="font-bold border-x border-gold/10 text-white shadow-inner">₱4,330,697 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱36,089/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">42.22 SQM</td><td>₱3,548,953</td><td class="text-gold">₱3,016,610</td><td>₱98,582</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-white shadow-inner">₱3,804,477 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱63,408/mo)</span></td><td class="font-bold border-x border-gold/10 text-white shadow-inner">₱4,571,051 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱38,092/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">49.60 SQM</td><td>₱4,169,305</td><td class="text-gold">₱3,543,909</td><td>₱115,814</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-white shadow-inner">₱4,469,495 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱74,492/mo)</span></td><td class="font-bold border-x border-gold/10 text-white shadow-inner">₱5,370,064 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱44,751/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">49.70 SQM</td><td>₱4,177,711</td><td class="text-gold">₱3,551,054</td><td>₱116,048</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-white shadow-inner">₱4,478,506 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱74,642/mo)</span></td><td class="font-bold border-x border-gold/10 text-white shadow-inner">₱5,380,891 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱44,841/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">50.00 SQM</td><td>₱4,202,928</td><td class="text-gold">₱3,572,489</td><td>₱116,748</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-white shadow-inner">₱4,505,539 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱75,092/mo)</span></td><td class="font-bold border-x border-gold/10 text-white shadow-inner">₱5,413,371 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱45,111/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">51.86 SQM</td><td>₱4,359,277</td><td class="text-gold">₱3,705,385</td><td>₱121,091</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-white shadow-inner">₱4,673,145 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱77,886/mo)</span></td><td class="font-bold border-x border-gold/10 text-white shadow-inner">₱4,614,749 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱38,456/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">53.30 SQM</td><td>₱4,480,321</td><td class="text-gold">₱3,808,273</td><td>₱124,453</td><td class="bg-gold/5 font-bold border-x border-gold/10 text-white shadow-inner">₱4,802,905 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱80,048/mo)</span></td><td class="font-bold border-x border-gold/10 text-white shadow-inner">₱5,770,654 <span class="block text-[10px] text-gold/80 font-sans font-semibold mt-1">(₱48,089/mo)</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section id="lock-in" class="bg-primaryLight text-white py-24 scroll-mt-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(rgba(197,160,89,0.1)_1px,transparent_1px)] [background-size:24px_24px]"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                <span class="text-gold font-bold text-[10px] uppercase tracking-[0.3em] block">Cost-Protection Guarantee</span>
                <h3 class="font-display text-4xl font-bold">Lock-In Today's Internment Fees</h3>
                <p class="text-base text-white/60 font-light leading-relaxed">
                    Prepay individual internment operations to legally hedge against future operational inflation spikes.
                </p>
            </div>

            <div class="bg-primary/50 border border-gold/20 shadow-2xl overflow-hidden rounded-2xl backdrop-blur-sm">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm border-collapse font-mono text-center min-w-[900px]">
                        <thead>
                            <tr class="bg-primary text-gold/70 font-semibold text-[10px] uppercase tracking-wider font-sans border-b border-gold/20">
                                <th class="p-5 text-left text-gold">Service Classification</th>
                                <th class="p-5"># of Services</th>
                                <th class="p-5">Immediate Cash</th>
                                <th class="p-5">36 Mos Amort</th>
                                <th class="p-5 bg-gold/10 text-white border-x border-gold/20 gold-border-top">
                                    <span class="block text-gold font-bold mb-1">RECOMMENDED</span>
                                    60 Mos Amort
                                </th>
                                <th class="p-5 text-gold bg-primary/80">120 Mos Extended</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-white/80 font-light">
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">Lawn Plots</td><td>2</td><td class="font-semibold">₱29,000</td><td>₱37,120 <span class="text-[10px] block text-white/40 font-sans mt-1">(₱1,031/mo)</span></td><td class="bg-gold/5 border-x border-gold/10 font-bold text-white shadow-inner">₱42,920 <span class="text-[10px] block text-gold font-sans font-semibold mt-1">(₱715/mo)</span></td><td class="bg-primary/40 font-bold text-gold">₱54,520 <span class="block text-[10px] text-white/50 font-sans mt-1">(₱454/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">Wall Niches</td><td>1</td><td class="font-semibold">₱16,000</td><td>₱20,480 <span class="text-[10px] block text-white/40 font-sans mt-1">(₱569/mo)</span></td><td class="bg-gold/5 border-x border-gold/10 font-bold text-white shadow-inner">₱23,680 <span class="text-[10px] block text-gold font-sans font-semibold mt-1">(₱395/mo)</span></td><td class="bg-primary/40 font-bold text-gold">₱30,080 <span class="block text-[10px] text-white/50 font-sans mt-1">(₱251/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">Bone Ossuary</td><td>6</td><td class="font-semibold">₱14,000</td><td>₱17,920 <span class="text-[10px] block text-white/40 font-sans mt-1">(₱498/mo)</span></td><td class="bg-gold/5 border-x border-gold/10 font-bold text-white shadow-inner">₱20,720 <span class="text-[10px] block text-gold font-sans font-semibold mt-1">(₱345/mo)</span></td><td class="bg-primary/40 font-bold text-gold">₱26,320 <span class="block text-[10px] text-white/50 font-sans mt-1">(₱219/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">Cinerarium</td><td>6</td><td class="font-semibold">₱14,000</td><td>₱17,920 <span class="text-[10px] block text-white/40 font-sans mt-1">(₱498/mo)</span></td><td class="bg-gold/5 border-x border-gold/10 font-bold text-white shadow-inner">₱20,720 <span class="text-[10px] block text-gold font-sans font-semibold mt-1">(₱345/mo)</span></td><td class="bg-primary/40 font-bold text-gold">₱26,320 <span class="block text-[10px] text-white/50 font-sans mt-1">(₱219/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">Pet Memorial (Max Use)</td><td>1</td><td class="font-semibold">₱17,000</td><td>₱21,760 <span class="text-[10px] block text-white/40 font-sans mt-1">(₱604/mo)</span></td><td class="bg-gold/5 border-x border-gold/10 font-bold text-white shadow-inner">₱25,160 <span class="text-[10px] block text-gold font-sans font-semibold mt-1">(₱419/mo)</span></td><td class="bg-primary/40 font-bold text-gold">₱31,960 <span class="block text-[10px] text-white/50 font-sans mt-1">(₱266/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">Garden Plot Upper Tier</td><td>—</td><td class="font-semibold">₱20,750</td><td>₱26,560 <span class="text-[10px] block text-white/40 font-sans mt-1">(₱738/mo)</span></td><td class="bg-gold/5 border-x border-gold/10 font-bold text-white shadow-inner">₱30,710 <span class="text-[10px] block text-gold font-sans font-semibold mt-1">(₱512/mo)</span></td><td class="bg-primary/40 font-bold text-gold">₱39,010 <span class="block text-[10px] text-white/50 font-sans mt-1">(₱325/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">Garden Plot Under Tier</td><td>—</td><td class="font-semibold">₱29,000</td><td>₱37,120 <span class="text-[10px] block text-white/40 font-sans mt-1">(₱1,031/mo)</span></td><td class="bg-gold/5 border-x border-gold/10 font-bold text-white shadow-inner">₱42,920 <span class="text-[10px] block text-gold font-sans font-semibold mt-1">(₱715/mo)</span></td><td class="bg-primary/40 font-bold text-gold">₱54,520 <span class="block text-[10px] text-white/50 font-sans mt-1">(₱454/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">Family Estate Upper Tier</td><td>—</td><td class="font-semibold">₱20,750</td><td>₱26,560 <span class="text-[10px] block text-white/40 font-sans mt-1">(₱738/mo)</span></td><td class="bg-gold/5 border-x border-gold/10 font-bold text-white shadow-inner">₱30,710 <span class="text-[10px] block text-gold font-sans font-semibold mt-1">(₱512/mo)</span></td><td class="bg-primary/40 font-bold text-gold">₱39,010 <span class="block text-[10px] text-white/50 font-sans mt-1">(₱325/mo)</span></td></tr>
                            <tr class="hover:bg-white/5 transition-colors"><td class="p-5 text-left font-sans font-semibold text-white">Family Estate Under Tier</td><td>—</td><td class="font-semibold">₱29,000</td><td>₱37,120 <span class="text-[10px] block text-white/40 font-sans mt-1">(₱1,031/mo)</span></td><td class="bg-gold/5 border-x border-gold/10 font-bold text-white shadow-inner">₱42,920 <span class="text-[10px] block text-gold font-sans font-semibold mt-1">(₱715/mo)</span></td><td class="bg-primary/40 font-bold text-gold">₱54,520 <span class="block text-[10px] text-white/50 font-sans mt-1">(₱454/mo)</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section id="chapels" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 scroll-mt-24">
        <div class="text-center max-w-2xl mx-auto mb-16 space-y-4">
            <span class="text-[10px] font-bold text-gold uppercase tracking-[0.3em] block">Elegant Spaces</span>
            <h3 class="font-display text-4xl font-bold text-primary">Viewing Chapel Care Plans</h3>
            <p class="text-base text-dark/60 font-light">Fully furnished premium environment rooms tailored for comfort and solemnity.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
            <div class="bg-white border border-primary/10 p-10 shadow-premium flex flex-col justify-between rounded-2xl hover:border-gold/30 transition-all duration-300 group">
                <div>
                    <div class="flex justify-between items-start mb-6">
                        <h4 class="font-display text-2xl font-bold text-primary">Deluxe Suite</h4>
                        <span class="text-[9px] bg-canvas text-primary font-bold border border-primary/10 px-3 py-1.5 uppercase tracking-wider">3 Nights / 4 Days</span>
                    </div>
                    <div class="font-mono space-y-4 text-sm text-dark/70 font-light">
                        <div class="flex justify-between border-b border-primary/5 pb-3"><span>Immediate Cash:</span><strong class="text-dark font-medium">₱26,400</strong></div>
                        <div class="flex justify-between border-b border-primary/5 pb-3"><span>3-Year Financing:</span><span>₱30,096 <span class="text-primary font-semibold font-sans ml-1">(₱836/mo)</span></span></div>
                        <div class="flex justify-between bg-gold/5 p-4 text-primary border border-gold/20 font-bold items-center">
                            <span class="font-sans text-[11px] uppercase tracking-wider">5-Year Financing:</span>
                            <span class="text-lg">₱33,528 <span class="font-sans text-xs text-gold font-semibold block text-right mt-0.5">(₱559/mo)</span></span>
                        </div>
                    </div>
                </div>
                <div class="mt-8 pt-6 border-t border-primary/10 grid grid-cols-2 gap-4 text-xs font-sans tracking-wide">
                    <div class="bg-canvas p-4 border border-primary/5">Overnight Stay:<br><strong class="text-primary text-base font-bold block mt-1">₱12,000</strong></div>
                    <div class="bg-canvas p-4 border border-primary/5">3-Hour Rental:<br><strong class="text-primary text-base font-bold block mt-1">₱3,500</strong></div>
                </div>
            </div>

            <div class="bg-primary text-white border border-gold/30 p-10 shadow-premium flex flex-col justify-between relative overflow-hidden rounded-2xl group">
                <div class="absolute top-0 right-0 bg-gold text-primary text-[9px] font-extrabold tracking-[0.2em] px-5 py-2 uppercase shadow-lg">
                    Elite Tier
                </div>
                <div>
                    <div class="flex justify-between items-start mb-6">
                        <h4 class="font-display text-2xl font-bold text-gold">Premium Suite</h4>
                        <span class="text-[9px] bg-white/10 text-white font-bold px-3 py-1.5 border border-white/20 uppercase tracking-wider mt-1 md:mt-0">3 Nights / 4 Days</span>
                    </div>
                    <div class="font-mono space-y-4 text-sm text-white/70 font-light">
                        <div class="flex justify-between border-b border-white/10 pb-3"><span>Immediate Cash:</span><strong class="text-white font-medium">₱72,600</strong></div>
                        <div class="flex justify-between border-b border-white/10 pb-3"><span>3-Year Financing:</span><span>₱82,764 <span class="text-gold font-semibold font-sans ml-1">(₱2,299/mo)</span></span></div>
                        <div class="flex justify-between bg-gold/10 p-4 text-white border border-gold/30 font-bold items-center">
                            <span class="font-sans text-[11px] uppercase tracking-wider">5-Year Financing:</span>
                            <span class="text-lg">₱92,202 <span class="font-sans text-xs text-gold font-semibold block text-right mt-0.5">(₱1,537/mo)</span></span>
                        </div>
                    </div>
                </div>
                <div class="mt-8 pt-6 border-t border-white/10 grid grid-cols-2 gap-4 text-xs font-sans tracking-wide">
                    <div class="bg-white/5 p-4 border border-white/10">Overnight Stay:<br><strong class="text-gold text-base font-bold block mt-1">₱30,000</strong></div>
                    <div class="bg-white/5 p-4 border border-white/10">3-Hour Rental:<br><strong class="text-gold text-base font-bold block mt-1">₱5,000</strong></div>
                </div>
            </div>
        </div>
    </section>

    <section id="cta" class="bg-primary text-white py-24 text-center relative overflow-hidden border-t border-gold/20">
        <div class="absolute inset-0 z-0 opacity-10 pointer-events-none">
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gold rounded-full mix-blend-screen filter blur-[120px] animate-pulse"></div>
        </div>
        <div class="max-w-3xl mx-auto px-4 space-y-8 relative z-10">
            <span class="text-gold font-bold text-[10px] uppercase tracking-[0.3em] block">Secure Your Legacy</span>
            <h2 class="font-display text-4xl md:text-5xl font-bold tracking-tight">Ready to Lock-In Your Preferred Location?</h2>
            <p class="text-base text-white/70 max-w-xl mx-auto leading-relaxed font-light">
                Connect directly with our dedicated planners via Facebook Messenger or landline phone channels. Don't wait until prices adjust next cycle.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-6 pt-6">
                <a href="https://m.me/CattleyaMemorials" target="_blank" class="w-full sm:w-auto bg-gold text-primary font-bold px-8 py-4 shadow-premium hover:bg-goldLight transition-all duration-300 text-xs tracking-[0.15em] uppercase flex items-center justify-center">
                    Connect on Messenger
                </a>
                <a href="tel:09479999374" class="w-full sm:w-auto bg-transparent text-white border border-gold font-bold px-8 py-4 hover:bg-gold/10 transition-all duration-300 text-xs tracking-[0.15em] uppercase">
                    Hotline: 0947-9999-374
                </a>
            </div>
            <div class="text-[10px] text-white/40 pt-8 font-mono tracking-[0.2em] uppercase">
                Landline Routing: <span class="text-gold">496-2822</span>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white/40 text-[11px] py-12 border-t border-gold/10 font-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center space-x-4">
                <span class="font-display text-xl font-bold text-gold tracking-widest">CATTLEYA</span>
                <span class="w-[1px] h-4 bg-white/20 block"></span>
                <span class="text-[9px] text-white/30 tracking-[0.3em] uppercase font-semibold">Price Archive 2026</span>
            </div>
            <p class="text-center md:text-right tracking-wider">
                &copy; 2026 Cattleya Memorial Gardens. All Rights Reserved. Prices subject to verified terms.
            </p>
        </div>
    </footer>

</body>
</html>