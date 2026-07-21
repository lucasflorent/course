<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Pictogrammes inline (style trait, 24x24, cf. Phosphor Icons) pour le
 * sous-ensemble utilise par l'appli. Pas de police d'icones complete : trop
 * lourde (~800 Ko) pour un hebergement mutualise a 17 icones pres.
 */
final class Icone
{
    private const TRACES = [
        'gear' => '<path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/><path d="M19.4 13.5a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V19.5a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1.08-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H4.5a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1.08 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H10.5a1.65 1.65 0 0 0 1-1.51V4.5a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09c.24.63.83 1.06 1.51 1.06h.09a2 2 0 1 1 0 4h-.09c-.68 0-1.27.43-1.51 1Z"/>',
        'warning-circle' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><circle cx="12" cy="16.2" r="1" fill="currentColor" stroke="none"/>',
        'trash' => '<path d="M4 6.5h16"/><path d="M8.5 6.5V4.8c0-.7.6-1.3 1.3-1.3h4.4c.7 0 1.3.6 1.3 1.3v1.7"/><path d="M6 6.5 6.9 19a2 2 0 0 0 2 1.8h6.2a2 2 0 0 0 2-1.8l.9-12.5"/><path d="M10 10.5v6"/><path d="M14 10.5v6"/>',
        'scales' => '<path d="M12 3v18"/><path d="M7 6.2 17 6.2"/><path d="M9.2 20h5.6"/><path d="m4.2 9.8 2.8-4 2.8 4-2.8 4.2-2.8-4.2Z"/><path d="m14.2 9.8 2.8-4 2.8 4-2.8 4.2-2.8-4.2Z"/>',
        'file-pdf' => '<path d="M7 3.5h7l4 4V19a1.3 1.3 0 0 1-1.3 1.3H7A1.3 1.3 0 0 1 5.7 19V4.8A1.3 1.3 0 0 1 7 3.5Z"/><path d="M14 3.5V8h4"/><path d="M8.3 17.5v-4.2h1.2c.7 0 1.3.5 1.3 1.2s-.6 1.2-1.3 1.2H8.3"/><path d="M12.5 17.5v-4.2h1c1 0 1.8.9 1.8 2.1s-.8 2.1-1.8 2.1h-1Z"/><path d="M17.3 13.3h-1.9v4.2"/><path d="M15.4 15.4h1.7"/>',
        'arrow-left' => '<path d="M19 12H5"/><path d="m11 6-6 6 6 6"/>',
        'house' => '<path d="m3.5 10.5 8-6.4 8 6.4"/><path d="M5.3 9.2V19a1 1 0 0 0 1 1H10v-5.2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1V20h3.7a1 1 0 0 0 1-1V9.2"/>',
        'timer' => '<circle cx="12" cy="13.5" r="7.5"/><path d="M12 9v4.5l3 2"/><path d="M10 2.5h4"/><path d="m18.5 5-1.2 1.2"/>',
        'chart-line' => '<path d="M4 4v15a1 1 0 0 0 1 1h15"/><path d="m6.5 14.5 4-4.3 3.3 2.8L19 7"/>',
        'chalkboard-teacher' => '<rect x="3.5" y="4" width="17" height="11" rx="1"/><path d="M9 20h6"/><path d="M12 15v5"/><circle cx="8" cy="8.3" r="1.6"/><path d="M5.7 12c.4-1.5 1.6-2.4 2.3-2.4s1.9.9 2.3 2.4"/><path d="M14 8h4"/><path d="M14 11h4"/>',
        'users-three' => '<circle cx="9" cy="8" r="2.6"/><path d="M4 18c.5-2.8 2.4-4.5 5-4.5s4.5 1.7 5 4.5"/><circle cx="17.3" cy="8.6" r="2.1"/><path d="M15.8 13.7c1.9.2 3.3 1.7 3.7 4"/>',
        'lock-key' => '<rect x="5" y="10.5" width="14" height="10" rx="1.5"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/><circle cx="12" cy="15" r="1.4"/><path d="M12 16.4V18"/>',
        'key' => '<circle cx="8" cy="15.5" r="4"/><path d="m11 12.5 8.5-8.5"/><path d="m16.5 6 2.3 2.3"/><path d="m14 8.5 2.3 2.3"/>',
        'box-arrow-left' => '<path d="M14 4H6.3A1.3 1.3 0 0 0 5 5.3v13.4A1.3 1.3 0 0 0 6.3 20H14"/><path d="M20 12H10"/><path d="m13.5 8.5-3.5 3.5 3.5 3.5"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'upload-simple' => '<path d="M12 15.5V4.5"/><path d="m7.5 8.5 4.5-4 4.5 4"/><path d="M4.5 15.5V18a1.5 1.5 0 0 0 1.5 1.5h12A1.5 1.5 0 0 0 19.5 18v-2.5"/>',
        'caret-down' => '<path d="m6 9 6 6 6-6"/>',
        'pencil-simple' => '<path d="m14.3 6.2 3.5 3.5"/><path d="M5.5 18.5 6 15l9.5-9.5a1.6 1.6 0 0 1 2.3 0l1.7 1.7a1.6 1.6 0 0 1 0 2.3L10 19l-3.5.5-1-1Z"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7"/>',
    ];

    public static function svg(string $nom, int $taille = 20): string
    {
        $traces = self::TRACES[$nom] ?? '';

        return '<svg class="icone" width="' . $taille . '" height="' . $taille
            . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" '
            . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . $traces . '</svg>';
    }
}
