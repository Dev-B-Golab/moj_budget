<template>
    <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="logo-bg" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#6366f1"/>
                <stop offset="100%" stop-color="#4338ca"/>
            </linearGradient>
            <linearGradient id="logo-coin" x1="20%" y1="0%" x2="80%" y2="100%">
                <stop offset="0%" stop-color="#fde68a"/>
                <stop offset="40%" stop-color="#fbbf24"/>
                <stop offset="100%" stop-color="#d97706"/>
            </linearGradient>
        </defs>
        <rect x="2" y="2" width="60" height="60" rx="15" fill="url(#logo-bg)"/>
        <circle cx="32" cy="32" r="21" fill="url(#logo-coin)" stroke="#b45309" stroke-width="2"/>
        <circle cx="32" cy="32" r="17" fill="none" stroke="#d97706" stroke-width="1.5" opacity="0.6"/>
        <path d="M32 17v3M32 44v3" stroke="#78350f" stroke-width="2.5" stroke-linecap="round"/>
        <path d="M27 24.5c0-2.5 2.2-4.5 5-4.5s5 2 5 4.5c0 3-3 3.5-5 4.5-2 1-5 1.5-5 4.5 0 2.5 2.2 4.5 5 4.5s5-2 5-4.5" fill="none" stroke="#78350f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</template>
