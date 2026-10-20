<?php
// Local vector illustrations; decorative because each has an adjacent text label.
$paths = [
    'sun' => '<circle cx="32" cy="32" r="11"/><path d="M32 4v8m0 40v8M4 32h8m40 0h8M12 12l6 6m28 28 6 6M12 52l6-6m28-28 6-6"/>',
    'panel' => '<path d="M14 12h36l8 34H6zM20 12l-4 34m16-34v34m12-34 4 34M11 23h42M8 35h48M25 46v10m14-10v10M18 56h28"/>',
    'inverter' => '<rect x="14" y="7" width="36" height="48" rx="5"/><rect x="22" y="16" width="20" height="12" rx="2"/><path d="m29 18-3 5h10l-3 4M23 38h18m-18 6h18M24 55v5m16-5v5"/>',
    'battery' => '<rect x="12" y="14" width="40" height="43" rx="5"/><path d="M22 14V7h20v7m-9 10-9 13h15L30 49M18 19h3m22 0h3"/>',
    'mount' => '<path d="m8 32 37-20 11 16-37 20zM17 28l11 16m1-23 11 16m-31 2v14h14M48 32v21h10M6 57h52"/>',
    'wire' => '<path d="M24 45H14a8 8 0 0 1 0-16h28a9 9 0 0 0 0-18H25m0 10h17a9 9 0 0 1 0 18H24m0 0v14h13V39m-9 14v7m5-7v7M25 6v10m-7-7h7"/>',
    'document' => '<path d="M16 5h22l12 12v42H16zM38 5v14h12M24 30h18m-18 9h18m-18 9h10"/>',
    'tool' => '<path d="M38 9a15 15 0 0 0-16 19L6 44a7 7 0 0 0 10 10l16-16A15 15 0 0 0 51 22L40 30l-8-8z"/><circle cx="12" cy="49" r="1"/>',
    'monitor' => '<rect x="5" y="10" width="54" height="36" rx="4"/><path d="M25 46v10m14-10v10M18 56h28M13 35l10-10 9 6 16-14m-9 0h9v9"/>',
    'leaf' => '<path d="M50 7C20 6 8 20 12 38c4 18 30 19 37 0 3-8 0-18 1-31zM11 58l27-32M23 44V30m0 14h14"/>',
];
?>
<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $paths[$icon] ?? $paths['sun'] ?></svg>
