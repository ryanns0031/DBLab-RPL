@switch($type)
    @case('social-media')
        {{-- Smartphone with Social Icons, Heart, and Database --}}
        <svg viewBox="0 0 160 140" fill="none" class="topic-svg">
            <defs>
                <linearGradient id="phoneGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#475569" />
                    <stop offset="100%" stop-color="#1e293b" />
                </linearGradient>
                <linearGradient id="igGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#f43f5e" />
                    <stop offset="50%" stop-color="#d946ef" />
                    <stop offset="100%" stop-color="#8b5cf6" />
                </linearGradient>
                <linearGradient id="ttGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#06b6d4" />
                    <stop offset="100%" stop-color="#0f172a" />
                </linearGradient>
            </defs>
            {{-- Phone Base --}}
            <rect x="25" y="15" width="60" height="95" rx="10" fill="url(#phoneGrad)" stroke="#64748b" stroke-width="2" />
            <rect x="30" y="24" width="50" height="78" rx="6" fill="#0f172a" />

            {{-- Social Icon 1: Instagram Style --}}
            <rect x="35" y="32" width="18" height="18" rx="5" fill="url(#igGrad)" />
            <rect x="39" y="36" width="10" height="10" rx="3" fill="none" stroke="#ffffff" stroke-width="1.2" />
            <circle cx="44" cy="41" r="2.5" fill="none" stroke="#ffffff" stroke-width="1.2" />
            <circle cx="47" cy="38" r="0.8" fill="#ffffff" />

            {{-- Social Icon 2: TikTok Style --}}
            <rect x="57" y="32" width="18" height="18" rx="5" fill="url(#ttGrad)" />
            <path d="M66 36v8a2.5 2.5 0 1 1-2.5-2.5m5-5.5a4 4 0 0 0 3 3" stroke="#38bdf8" stroke-width="1.5" stroke-linecap="round" fill="none" />

            {{-- Floating Heart / Like Badge --}}
            <g transform="translate(62, 58)">
                <circle cx="10" cy="10" r="10" fill="#f43f5e" />
                <path d="M10 14.5l-3.2-3.2a2 2 0 1 1 2.8-2.8L10 9l.4-.5a2 2 0 1 1 2.8 2.8z" fill="#ffffff" />
            </g>

            {{-- Database Discs Right --}}
            <g transform="translate(85, 70)">
                <path d="M0 26 C0 20 42 20 42 26 L42 38 C42 44 0 44 0 38 Z" fill="#1e3a8a" />
                <ellipse cx="21" cy="26" rx="21" ry="6" fill="#2563eb" />
                <path d="M0 13 C0 7 42 7 42 13 L42 25 C42 31 0 31 0 25 Z" fill="#2563eb" />
                <ellipse cx="21" cy="13" rx="21" ry="6" fill="#3b82f6" />
                <path d="M0 0 C0 -6 42 -6 42 0 L42 12 C42 18 0 18 0 12 Z" fill="#3b82f6" />
                <ellipse cx="21" cy="0" rx="21" ry="6" fill="#93c5fd" />
            </g>
        </svg>
        @break

    @case('gaming')
        {{-- 3D Gamepad Controller and Database --}}
        <svg viewBox="0 0 160 140" fill="none" class="topic-svg">
            <defs>
                <linearGradient id="padGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#ffffff" />
                    <stop offset="100%" stop-color="#cbd5e1" />
                </linearGradient>
            </defs>
            {{-- Gamepad Body --}}
            <g transform="translate(15, 20)">
                <path d="M18 18 C30 14 55 14 67 18 C76 22 84 36 78 54 C72 70 58 64 54 50 C50 46 35 46 31 50 C27 64 13 70 7 54 C1 36 9 22 18 18 Z" fill="url(#padGrad)" stroke="#94a3b8" stroke-width="1.5" />
                {{-- D-Pad --}}
                <path d="M21 28h6v-6h4v6h6v4h-6v6h-4v-6h-6z" fill="#475569" />
                {{-- Action Buttons --}}
                <circle cx="56" cy="28" r="2.5" fill="#3b82f6" />
                <circle cx="63" cy="33" r="2.5" fill="#ef4444" />
                <circle cx="56" cy="38" r="2.5" fill="#10b981" />
                <circle cx="49" cy="33" r="2.5" fill="#f59e0b" />
            </g>

            {{-- Database Cylinders Bottom Right --}}
            <g transform="translate(85, 62)">
                <path d="M0 28 C0 22 46 22 46 28 L46 42 C46 48 0 48 0 42 Z" fill="#1e3a8a" />
                <ellipse cx="23" cy="28" rx="23" ry="6.5" fill="#2563eb" />
                <path d="M0 14 C0 8 46 8 46 14 L46 28 C46 34 0 34 0 28 Z" fill="#2563eb" />
                <ellipse cx="23" cy="14" rx="23" ry="6.5" fill="#3b82f6" />
                <path d="M0 0 C0 -6 46 -6 46 0 L46 14 C46 20 0 20 0 14 Z" fill="#3b82f6" />
                <ellipse cx="23" cy="0" rx="23" ry="6.5" fill="#93c5fd" />
            </g>
        </svg>
        @break

    @case('ecommerce')
        {{-- Shopping Cart and Database --}}
        <svg viewBox="0 0 160 140" fill="none" class="topic-svg">
            <defs>
                <linearGradient id="cartGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#f97316" />
                    <stop offset="100%" stop-color="#ea580c" />
                </linearGradient>
            </defs>
            {{-- Shopping Cart --}}
            <g transform="translate(20, 15)">
                <path d="M5 8h10l8 32h32l6-24H20" stroke="url(#cartGrad)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                <circle cx="27" cy="46" r="4.5" fill="#f97316" />
                <circle cx="51" cy="46" r="4.5" fill="#f97316" />
                {{-- Box in Cart --}}
                <rect x="25" y="14" width="16" height="14" rx="2" fill="#fed7aa" stroke="#f97316" stroke-width="1" />
                <rect x="37" y="10" width="14" height="14" rx="2" fill="#fdba74" stroke="#ea580c" stroke-width="1" />
            </g>

            {{-- Database Cylinders --}}
            <g transform="translate(85, 65)">
                <path d="M0 26 C0 20 44 20 44 26 L44 38 C44 44 0 44 0 38 Z" fill="#1e3a8a" />
                <ellipse cx="22" cy="26" rx="22" ry="6" fill="#2563eb" />
                <path d="M0 13 C0 7 44 7 44 13 L44 25 C44 31 0 31 0 25 Z" fill="#2563eb" />
                <ellipse cx="22" cy="13" rx="22" ry="6" fill="#3b82f6" />
                <path d="M0 0 C0 -6 44 -6 44 0 L44 12 C44 18 0 18 0 12 Z" fill="#3b82f6" />
                <ellipse cx="22" cy="0" rx="22" ry="6" fill="#93c5fd" />
            </g>
        </svg>
        @break

    @case('music')
        {{-- Musical Note and Database --}}
        <svg viewBox="0 0 160 140" fill="none" class="topic-svg">
            <defs>
                <linearGradient id="musicGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#38bdf8" />
                    <stop offset="100%" stop-color="#0284c7" />
                </linearGradient>
                <linearGradient id="purpleDb" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#a855f7" />
                    <stop offset="100%" stop-color="#6b21a8" />
                </linearGradient>
            </defs>
            {{-- Musical Note Badge --}}
            <g transform="translate(25, 20)">
                <rect width="46" height="50" rx="12" fill="url(#musicGrad)" />
                <circle cx="18" cy="36" r="4.5" fill="#ffffff" />
                <circle cx="32" cy="32" r="4.5" fill="#ffffff" />
                <path d="M22 36V18l14-4v18" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                <path d="M22 22l14-4" stroke="#ffffff" stroke-width="2.5" />
            </g>

            {{-- Database Cylinders (Purple Theme) --}}
            <g transform="translate(85, 65)">
                <path d="M0 26 C0 20 44 20 44 26 L44 38 C44 44 0 44 0 38 Z" fill="#581c87" />
                <ellipse cx="22" cy="26" rx="22" ry="6" fill="#7e22ce" />
                <path d="M0 13 C0 7 44 7 44 13 L44 25 C44 31 0 31 0 25 Z" fill="#7e22ce" />
                <ellipse cx="22" cy="13" rx="22" ry="6" fill="#a855f7" />
                <path d="M0 0 C0 -6 44 -6 44 0 L44 12 C44 18 0 18 0 12 Z" fill="#a855f7" />
                <ellipse cx="22" cy="0" rx="22" ry="6" fill="#d8b4fe" />
            </g>
        </svg>
        @break

    @case('school')
        {{-- School Building and Database --}}
        <svg viewBox="0 0 160 140" fill="none" class="topic-svg">
            <defs>
                <linearGradient id="roofGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#f43f5e" />
                    <stop offset="100%" stop-color="#be123c" />
                </linearGradient>
            </defs>
            {{-- School Building --}}
            <g transform="translate(15, 20)">
                {{-- Main Wall --}}
                <rect x="8" y="24" width="56" height="36" rx="3" fill="#fef2f2" stroke="#e2e8f0" stroke-width="1.5" />
                {{-- Roof --}}
                <polygon points="36,4 4,24 68,24" fill="url(#roofGrad)" />
                {{-- Flag --}}
                <line x1="36" y1="4" x2="36" y2="0" stroke="#94a3b8" stroke-width="1.5" />
                <polygon points="36,0 44,2 36,5" fill="#ef4444" />
                {{-- Windows & Door --}}
                <rect x="15" y="30" width="8" height="9" rx="1.5" fill="#60a5fa" />
                <rect x="49" y="30" width="8" height="9" rx="1.5" fill="#60a5fa" />
                <rect x="31" y="44" width="10" height="16" rx="2" fill="#b91c1c" />
            </g>

            {{-- Database Cylinders --}}
            <g transform="translate(85, 68)">
                <path d="M0 24 C0 18 42 18 42 24 L42 36 C42 42 0 42 0 36 Z" fill="#1e3a8a" />
                <ellipse cx="21" cy="24" rx="21" ry="6" fill="#2563eb" />
                <path d="M0 12 C0 6 42 6 42 12 L42 24 C42 30 0 30 0 24 Z" fill="#2563eb" />
                <ellipse cx="21" cy="12" rx="21" ry="6" fill="#3b82f6" />
                <path d="M0 0 C0 -6 42 -6 42 0 L42 12 C42 18 0 18 0 12 Z" fill="#3b82f6" />
                <ellipse cx="21" cy="0" rx="21" ry="6" fill="#93c5fd" />
            </g>
        </svg>
        @break

    @case('transport')
        {{-- Map, Pin, and Database --}}
        <svg viewBox="0 0 160 140" fill="none" class="topic-svg">
            <defs>
                <linearGradient id="mapGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#f8fafc" />
                    <stop offset="100%" stop-color="#e2e8f0" />
                </linearGradient>
            </defs>
            {{-- Map Base --}}
            <g transform="translate(15, 18)">
                <rect width="58" height="74" rx="8" fill="url(#mapGrad)" stroke="#cbd5e1" stroke-width="1.5" transform="rotate(-6 29 37)" />
                {{-- Roads --}}
                <path d="M8 22 Q28 26 34 50 T52 68" stroke="#38bdf8" stroke-width="3" stroke-linecap="round" fill="none" transform="rotate(-6 29 37)" />
                {{-- Location Pin --}}
                <g transform="translate(26, 12)">
                    <path d="M12 0 C5.4 0 0 5.4 0 12 C0 19 12 30 12 30 C12 30 24 19 24 12 C24 5.4 18.6 0 12 0 Z" fill="#ef4444" />
                    <circle cx="12" cy="11" r="4.5" fill="#ffffff" />
                </g>
            </g>

            {{-- Database Cylinders --}}
            <g transform="translate(85, 68)">
                <path d="M0 24 C0 18 44 18 44 24 L44 36 C44 42 0 42 0 36 Z" fill="#1e3a8a" />
                <ellipse cx="22" cy="24" rx="22" ry="6" fill="#2563eb" />
                <path d="M0 12 C0 6 44 6 44 12 L44 24 C44 30 0 30 0 24 Z" fill="#2563eb" />
                <ellipse cx="22" cy="12" rx="22" ry="6" fill="#3b82f6" />
                <path d="M0 0 C0 -6 44 -6 44 0 L44 12 C44 18 0 18 0 12 Z" fill="#3b82f6" />
                <ellipse cx="22" cy="0" rx="22" ry="6" fill="#93c5fd" />
            </g>
        </svg>
        @break

    @default
        {{-- Generic Tech / Innovation / Database Discs --}}
        <svg viewBox="0 0 160 140" fill="none" class="topic-svg">
            <defs>
                <linearGradient id="genGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#38bdf8" />
                    <stop offset="100%" stop-color="#2563eb" />
                </linearGradient>
            </defs>
            {{-- Glowing Sparkles --}}
            <circle cx="45" cy="35" r="16" fill="#2563eb" opacity="0.25" />
            <path d="M45 23v24m-12-12h24" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" />

            {{-- Database Cylinders --}}
            <g transform="translate(65, 45)">
                <path d="M0 32 C0 25 52 25 52 32 L52 48 C52 55 0 55 0 48 Z" fill="#1e3a8a" />
                <ellipse cx="26" cy="32" rx="26" ry="7" fill="#2563eb" />
                <path d="M0 16 C0 9 52 9 52 16 L52 32 C52 39 0 39 0 32 Z" fill="#2563eb" />
                <ellipse cx="26" cy="16" rx="26" ry="7" fill="#3b82f6" />
                <path d="M0 0 C0 -7 52 -7 52 0 L52 16 C52 23 0 23 0 16 Z" fill="#3b82f6" />
                <ellipse cx="26" cy="0" rx="26" ry="7" fill="#93c5fd" />
            </g>
        </svg>
@endswitch

