<?php

$envPath = env('BROWSERSHOT_CHROME_PATH');

if (!empty($envPath)) {
    return [
        'chrome_path' => $envPath,
    ];
}

$candidates = [
    // Puppeteer-managed Chromium (preferred if installed via npm/yarn)
    base_path('node_modules/puppeteer/.local-chromium/linux-*/chrome-linux/chrome'),
    base_path('node_modules/puppeteer/.local-chromium/linux-*/chrome-linux64/chrome'),
    base_path('node_modules/puppeteer/.local-chromium/mac-*/chrome-mac/Chromium.app/Contents/MacOS/Chromium'),
    base_path('node_modules/puppeteer/.local-chromium/win64-*/chrome-win/chrome.exe'),
    base_path('node_modules/puppeteer/.local-chromium/win32-*/chrome-win/chrome.exe'),
    // Common system paths
    '/usr/bin/google-chrome',
    '/usr/bin/google-chrome-stable',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
];

$resolved = null;

foreach ($candidates as $candidate) {
    if (str_contains($candidate, '*')) {
        $matches = glob($candidate);
        if (!empty($matches)) {
            $resolved = $matches[0];
            break;
        }
        continue;
    }

    if (is_file($candidate)) {
        $resolved = $candidate;
        break;
    }
}

return [
    'chrome_path' => $resolved,
];
