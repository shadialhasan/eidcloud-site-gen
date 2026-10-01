<?php

declare(strict_types=1);

namespace EidCloud\SiteGen\I18n;

/**
 * Manages language localization, RTL/LTR bi-directional scripts, and metadata.
 */
class LanguageManager
{
    /**
     * Set of standard RTL language codes (ISO 639-1 / BCP 47).
     *
     * @var array<string, bool>
     */
    private const RTL_LANGUAGES = [
        'ar' => true, // Arabic
        'fa' => true, // Persian
        'ur' => true, // Urdu
        'he' => true, // Hebrew
        'ps' => true, // Pashto
        'sd' => true, // Sindhi
        'ug' => true, // Uyghur
        'yi' => true, // Yiddish
        'arc' => true, // Aramaic
        'syr' => true, // Syriac
    ];

    /**
     * Language display names and native labels.
     *
     * @var array<string, array{name: string, native: string, dir: string}>
     */
    private const KNOWN_LANGUAGES = [
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'dir' => 'rtl'],
        'fr' => ['name' => 'French', 'native' => 'Français', 'dir' => 'ltr'],
        'de' => ['name' => 'German', 'native' => 'Deutsch', 'dir' => 'ltr'],
        'es' => ['name' => 'Spanish', 'native' => 'Español', 'dir' => 'ltr'],
        'tr' => ['name' => 'Turkish', 'native' => 'Türkçe', 'dir' => 'ltr'],
        'ur' => ['name' => 'Urdu', 'native' => 'اردو', 'dir' => 'rtl'],
        'fa' => ['name' => 'Persian', 'native' => 'فارسی', 'dir' => 'rtl'],
        'zh' => ['name' => 'Chinese', 'native' => '中文', 'dir' => 'ltr'],
        'ja' => ['name' => 'Japanese', 'native' => '日本語', 'dir' => 'ltr'],
    ];

    public function __construct(
        private readonly string $defaultLanguage = 'en'
    ) {}

    /**
     * Determine directionality ('ltr' or 'rtl') based on language tag.
     */
    public function getDirection(string $lang): string
    {
        $primary = strtolower(explode('-', $lang)[0]);
        return isset(self::RTL_LANGUAGES[$primary]) ? 'rtl' : 'ltr';
    }

    /**
     * Check if a language tag is RTL.
     */
    public function isRtl(string $lang): bool
    {
        return $this->getDirection($lang) === 'rtl';
    }

    /**
     * Get default language tag.
     */
    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }

    /**
     * Get language metadata (name, native label, dir).
     *
     * @return array{name: string, native: string, dir: string}
     */
    public function getLanguageInfo(string $lang): array
    {
        $primary = strtolower(explode('-', $lang)[0]);
        if (isset(self::KNOWN_LANGUAGES[$primary])) {
            return self::KNOWN_LANGUAGES[$primary];
        }

        $dir = $this->getDirection($primary);
        return [
            'name' => strtoupper($primary),
            'native' => strtoupper($primary),
            'dir' => $dir,
        ];
    }
}
