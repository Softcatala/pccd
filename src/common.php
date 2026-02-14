<?php

/**
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 * (c) Víctor Pàmies i Riudor <vpamies@gmail.com>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

use Pdo\Mysql;

/**
 * CSS breakpoints for responsive design.
 * These values should match the breakpoints defined in src/css/variables.css.
 */
const BREAKPOINT_MD = 576;
const BREAKPOINT_LG = 768;

/**
 * Primary color used throughout the site.
 * This value should match --color-primary defined in src/css/variables.css.
 */
const COLOR_PRIMARY = '#2b5797';

/**
 * Open Graph image rendering settings.
 */
const OG_TEXT_MAX_LENGTH = 100;
const OG_IMAGE_WIDTH = 1200;
const OG_IMAGE_HEIGHT = 630;
const OG_TEXT_MAX_LINES = 4;
const OG_FONT_PATH = __DIR__ . '/fonts/Roboto-Regular.ttf';
const OG_TRIM_CHARS = "[](),?!:;«»º-–—―─'\"“”‘’….¡¿* \n\r\t\v\0\x95";

/**
 * Handles rendering of pages, with their meta tags and side blocks.
 */
final class PageRenderer
{
    /**
     * Static pages with simple URL routing (e.g. /credits → credits page).
     *
     * Each page must have a corresponding PHP file in src/pages/ directory.
     * Used by route_request() for URL routing.
     */
    public const array STATIC_PAGE_NAMES = [
        'credits',
        'fonts',
        'instruccions',
        'llibres',
        'projecte',
        'top100',
        'top10000',
    ];

    /**
     * The maximum age for dynamic pages in seconds.
     *
     * This is used to set the Cache-Control header for dynamic pages, and is
     * specially useful for our JS-based prefetch mechanism.
     */
    private const int CACHE_MAX_AGE_DYNAMIC_PAGES = 900;

    /**
     * All page names that can be rendered, including dynamic routes.
     */
    private const array PAGE_NAMES = [
        ...self::STATIC_PAGE_NAMES,
        'obra',
        'paremiotipus',
        'search',
    ];

    public readonly string $name;

    public readonly string $mainContent;

    public readonly string $sideBlocks;

    private static string $title;

    private static string $paremiotipusBlocks;

    private static string $canonicalUrl = '';

    private static string $metaDescription = '';

    private static string $metaImage = '';

    private static string $ogAudioUrl = '';

    private static OgType $ogType = OgType::ARTICLE;

    /**
     * Initializes the page renderer, setting up the page name, main content, and side blocks.
     *
     * This information is needed to render the template.
     */
    public function __construct()
    {
        $this->name = $this->getPageName();
        $this->mainContent = $this->renderMainContent();
        $this->sideBlocks = $this->renderSideBlocks();
    }

    /**
     * Gets the current page title.
     */
    public function getTitle(): string
    {
        return self::$title;
    }

    /**
     * Gets the rendered meta tags for the current page, including Open Graph and Twitter tags.
     */
    public function renderPageMetaTags(): string
    {
        $meta_tags = [];

        if ($this->name === 'search' && !$this->isHomepage()) {
            // Do not index search pages that are not the homepage.
            $meta_tags[] = '<meta name="robots" content="noindex">';
        }

        $meta_tags[] = '<meta property="og:type" content="' . self::$ogType->value . '">';

        if (self::$metaDescription !== '') {
            $meta_tags[] = '<meta name="description" property="og:description" content="' . self::$metaDescription . '">';
        }

        if (self::$metaImage !== '') {
            $card_type = 'summary';
            // The only pages we know the image is large is enough are the
            // homepage and the ones that have generated OG images.
            if ($this->isHomepage() || str_contains(self::$metaImage, '/og/')) {
                $card_type = 'summary_large_image';
            }
            $meta_tags[] = '<meta name="twitter:image" property="og:image" content="' . self::$metaImage . '">';
            $meta_tags[] = '<meta name="twitter:card" content="' . $card_type . '">';
        }

        if (self::$ogAudioUrl !== '') {
            $meta_tags[] = '<meta property="og:audio" content="' . self::$ogAudioUrl . '">';
        }

        if (self::$canonicalUrl !== '') {
            $meta_tags[] = '<link rel="canonical" href="' . self::$canonicalUrl . '">';
        }

        return implode("\n", $meta_tags);
    }

    /**
     * Sets the page title.
     */
    public static function setTitle(string $title): void
    {
        self::$title = prepare_field($title, escape_html: false, end_with_dot: false);
    }

    /**
     * Sets the HTML blocks for the paremiotipus side section.
     */
    public static function setParemiotipusBlocks(string $blocks): void
    {
        self::$paremiotipusBlocks = $blocks;
    }

    /**
     * Sets the canonical URL for the page.
     */
    public static function setCanonicalUrl(string $url): void
    {
        self::$canonicalUrl = $url;
    }

    /**
     * Sets the meta description for the page.
     */
    public static function setMetaDescription(string $description): void
    {
        self::$metaDescription = $description;
    }

    /**
     * Sets the meta image URL for the page.
     */
    public static function setMetaImage(string $image_url): void
    {
        self::$metaImage = $image_url;
    }

    /**
     * Sets the Open Graph audio URL for the page.
     */
    public static function setOgAudioUrl(string $audio_url): void
    {
        self::$ogAudioUrl = $audio_url;
    }

    /**
     * Sets the Open Graph type for the page.
     */
    public static function setOgType(OgType $og_type): void
    {
        self::$ogType = $og_type;
    }

    /**
     * Outputs the CSS content for the current page.
     *
     * Returns page-specific CSS if it exists, otherwise falls back to base.css.
     */
    public function printCss(): void
    {
        $page_css_file = __DIR__ . "/../docroot/css/pages/{$this->name}.min.css";
        if (file_exists($page_css_file)) {
            // Using require instead of file_get_contents() or readfile()
            // allows serving the file from memory (OPcache).
            require $page_css_file;
        } else {
            require __DIR__ . '/../docroot/css/base.min.css';
        }
    }

    /**
     * Outputs the JavaScript content for the current page.
     *
     * Returns page-specific JS for certain pages followed by the main app.min.js.
     */
    public function printJs(): void
    {
        // Add page-specific JavaScript.
        $page_js_file = __DIR__ . "/../docroot/js/pages/{$this->name}.min.js";
        if (file_exists($page_js_file)) {
            // Using require instead of file_get_contents() or readfile()
            // allows serving the file from memory (OPcache).
            require $page_js_file;
        }

        // Add main JS file.
        require __DIR__ . '/../docroot/js/app.min.js';
    }

    /**
     * Renders the page by including the main template and setting cache headers.
     */
    public static function render(): void
    {
        header('Cache-Control: public, max-age=' . self::CACHE_MAX_AGE_DYNAMIC_PAGES);

        // Include the page template.
        require __DIR__ . '/templates/main.php';
    }

    /**
     * Returns the rendered footer for the current page.
     */
    public function renderFooter(): string
    {
        $footer = '<p>' . format_nombre(get_modisme_count()) . '&nbsp;fitxes, corresponents a ' . format_nombre(get_paremiotipus_count()) . '&nbsp;paremiotipus, recollides de ' . format_nombre(get_font_count()) . '&nbsp;fonts i ' . format_nombre(get_informant_count()) . '&nbsp;informants. Última actualització: ' . get_latest_db_date() . '</p>';
        $footer .= '<p>' . get_copyright_notice() . '</p>';

        return $footer;
    }

    /**
     * Checks if the current page is the homepage.
     *
     * The homepage is defined as the first search results page with no
     * search query.
     */
    private function isHomepage(): bool
    {
        return $this->name === 'search'
            && (!isset($_GET['cerca']) || $_GET['cerca'] === '')
            && get_search_page_number() === 1;
    }

    /**
     * Returns the current page name based on the query parameters.
     */
    private function getPageName(): string
    {
        foreach (self::PAGE_NAMES as $page) {
            if (isset($_GET[$page])) {
                return $page;
            }
        }

        // Default to the search page, which is also the homepage.
        return 'search';
    }

    /**
     * Returns the rendered main content for the current page.
     */
    private function renderMainContent(): string
    {
        ob_start();

        require __DIR__ . "/pages/{$this->name}.php";
        $main_content = ob_get_clean();
        assert($main_content !== false);

        return $main_content;
    }

    /**
     * Returns the rendered books block for the sidebar.
     */
    private function renderBooksBlock(): string
    {
        $block = '<div class="bloc bloc-books">';
        $block .= '<p><a href="/llibres">Llibres de l\'autor</a></p>';
        $block .= get_random_book()->render(preload: true, preload_media: '(min-width: ' . BREAKPOINT_LG . 'px)');
        $block .= '</div>';

        return $block;
    }

    /**
     * Returns the rendered credits block for the sidebar.
     */
    private function renderCreditsBlock(): string
    {
        $block = '<div class="bloc bloc-credits bloc-white">';
        $block .= '<p>Un projecte de:</p>';
        $block .= '<p><a href="http://www.dites.cat">dites.cat</a></p>';
        $block .= '<p><a href="https://www.softcatala.org"><img alt="Softcatalà" width="120" height="80" src="/img/logo-softcatala.svg"></a></p>';
        $block .= '</div>';

        return $block;
    }

    /**
     * Returns the rendered top 100 paremiotipus block for the sidebar.
     */
    private function renderTop100Block(): string
    {
        $random_paremiotipus = get_random_top_paremiotipus(100);
        $block = '<div class="bloc" data-nosnippet>';
        $block .= '<p class="text-balance">«<a href="' . get_paremiotipus_url($random_paremiotipus) . '">';
        $block .= get_paremiotipus_display($random_paremiotipus);
        $block .= '</a>»</p>';
        $block .= '<div class="footer"><a href="/top100">Les 100 parèmies més citades</a></div>';
        $block .= '</div>';

        return $block;
    }

    /**
     * Returns the rendered top 10,000 paremiotipus block for the sidebar.
     */
    private function renderTop10000Block(): string
    {
        $random_paremiotipus = get_random_top_paremiotipus(10000);
        $block = '<div class="bloc" data-nosnippet>';
        $block .= '<p class="text-balance">«<a href="' . get_paremiotipus_url($random_paremiotipus) . '">';
        $block .= get_paremiotipus_display($random_paremiotipus);
        $block .= '</a>»</p>';
        $block .= '<div class="footer">Les 10.000 parèmies més citades</div>';
        $block .= '</div>';

        return $block;
    }

    /**
     * Returns the rendered sidebar blocks for the current page.
     */
    private function renderSideBlocks(): string
    {
        $blocks = '';
        if ($this->name === 'search') {
            $blocks .= $this->renderTop100Block();
            $blocks .= $this->renderBooksBlock();
        } elseif ($this->name === 'paremiotipus') {
            $blocks .= self::$paremiotipusBlocks;
        }
        $blocks .= $this->renderCreditsBlock();
        if ($this->name !== 'search') {
            $blocks .= $this->renderTop10000Block();
        }

        return $blocks;
    }
}

/**
 * Data class representing a bibliographic work (Obra).
 */
final readonly class Obra
{
    public string $Identificador;

    public string $Títol;

    public string $Imatge;

    public string $Preu;

    public string $Any_edició;

    public string $Pàgines;

    public string $Registres;

    public string $Any;

    public string $Autor;

    public string $Collecció;

    public string $Data_compra;

    public string $Edició;

    public string $Editorial;

    public string $Idioma;

    public string $ISBN;

    public string $Lloc_compra;

    public string $Municipi;

    public string $Núm_collecció;

    public string $Observacions;

    public string $URL;

    public string $Varietat_dialectal;

    public string $WIDTH;

    public string $HEIGHT;
}

/**
 * Data class representing a book by Víctor Pàmies, with rendering logic for HTML output.
 */
final readonly class Book
{
    private string $Imatge;

    private string $Títol;

    private string $URL;

    private string $WIDTH;

    private string $HEIGHT;

    /**
     * Renders the book as HTML, optionally with lazy loading and preload options.
     *
     * Set lazy_loading to false to disable lazy loading.
     * Set preload to true to preload the image.
     * The preload_media parameter specifies the media query for preloading.
     */
    public function render(bool $lazy_loading = true, bool $preload = false, string $preload_media = ''): string
    {
        $html = '';
        if ($this->URL !== '') {
            $html .= '<a href="' . $this->URL . '" title="' . htmlspecialchars($this->Títol) . '">';
        }
        $html .= render_image_tags(
            file_name: $this->Imatge,
            path: '/img/obres/',
            alt_text: 'Coberta ' . $this->Títol,
            width: $this->WIDTH,
            height: $this->HEIGHT,
            lazy_loading: $lazy_loading,
            preload: $preload,
            preload_media: $preload_media
        );
        if ($this->URL !== '') {
            $html .= '</a>';
        }

        return $html;
    }
}

/**
 * Enum for Open Graph types.
 */
enum OgType: string
{
    case ARTICLE = 'article';

    case WEBSITE = 'website';

    case BOOK = 'book';
}

/**
 * Enum for search modes.
 *
 * This enum defines the different search modes available in the application.
 * It includes both public-facing modes, which are selectable by the user,
 * and internal modes used for specific query types.
 */
enum SearchMode: string
{
    // The string values are used directly in public-facing URLs as query
    // parameters for search pages. Changing them may break existing links in
    // external sites.
    case CONTAINS = 'conté';

    case STARTS_WITH = 'comença';

    case ENDS_WITH = 'acaba';

    case EXACT = 'coincident';

    // Internal search modes.
    case WHOLE_SENTENCE = 'whole_sentence';

    case WILDCARD = 'wildcard';

    /**
     * Returns the user-facing label for a search mode.
     *
     * This method serves a dual purpose:
     * 1. It provides the human-readable label for modes that are public.
     * 2. It implicitly defines which modes are public by returning a string
     *    label. For internal modes, it returns null.
     */
    public function getPublicLabel(): ?string
    {
        return match ($this) {
            self::CONTAINS => 'conté',
            self::STARTS_WITH => 'comença per',
            self::ENDS_WITH => 'acaba en',
            self::EXACT => 'coincident',
            default => null,
        };
    }
}

/**
 * Generic cache wrapper to get data from cache (APCu).
 *
 * The key parameter specifies the cache key to use for storing/retrieving the value.
 *
 * @template T
 *
 * @param callable(): T $callback The callback to generate the value if not cached.
 *
 * @return T The cached value, or the result of the callback if not cached.
 */
function cache_get(string $key, callable $callback): mixed
{
    if (!extension_loaded('apcu')) {
        return $callback();
    }

    /** @var T */
    $cached = apcu_fetch($key, $success);
    if ($success) {
        return $cached;
    }

    $value = $callback();
    apcu_store($key, $value);

    return $value;
}

/**
 * Transforms plain text into valid HTML turning URLs into links.
 *
 * The text parameter contains the input text to escape and linkify.
 * The property parameter specifies the property attribute to add to links.
 * Set debug to true to write debug information to a file.
 */
function html_escape_and_link_urls(string $text, string $property = '', bool $debug = false): string
{
    $escaped = htmlspecialchars($text, ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML5);
    $pattern = '/(https?:\/\/[^\s]+?)(?=[.,;:!?)"\']*(?:\s|&gt;|$))/';

    $output = preg_replace_callback($pattern, static function (array $matches) use ($debug, $property): string {
        $url = $matches[1];

        if ($debug) {
            file_put_contents(
                __DIR__ . '/../data/reports/test_tmp_debug_html_escape_and_link_urls.txt',
                $url . "\n",
                FILE_APPEND
            );
        }

        $attributes = 'class="external" target="_blank" rel="noopener" href="' . $url . '"';
        if ($property !== '') {
            $attributes .= ' property="' . $property . '"';
        }

        return "<a {$attributes}>{$url}</a>";
    }, $escaped);

    assert(is_string($output));

    return $output;
}

/**
 * Returns the database connection.
 */
function get_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Potentially, set environment variables in a local file.
    // if (file_exists(__DIR__ . '/db_settings.local.php')) {
    //    require __DIR__ . '/db_settings.local.php';
    // }

    $host = getenv('MYSQL_HOSTNAME');
    $db_name = getenv('MYSQL_DATABASE');
    $user = getenv('MYSQL_USER');
    $password = getenv('MYSQL_PASSWORD');

    assert(is_string($host));
    assert(is_string($db_name));
    assert(is_string($user));
    assert(is_string($password));

    try {
        $pdo = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $user, $password, [
            Mysql::ATTR_MULTI_STATEMENTS => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_TO_STRING,
            PDO::ATTR_STRINGIFY_FETCHES => true,
        ]);

        return $pdo;
    } catch (Exception) {
        ob_end_clean();

        header('HTTP/1.1 500 Internal Server Error', response_code: 500);
        header('Cache-Control: no-cache, no-store, must-revalidate');

        require __DIR__ . '/../docroot/500.html';

        exit;
    }
}

/**
 * Trims and removes newlines, extra spaces and unsafe characters from the provided string.
 *
 * The input parameter contains the string to prepare.
 * Set escape_html to false to disable HTML escaping.
 * Set end_with_dot to false to prevent ensuring the string ends with a dot or punctuation.
 */
function prepare_field(string $input, bool $escape_html = true, bool $end_with_dot = true): string
{
    // Remove unsafe characters in attributes (https://htmlhint.com/docs/user-guide/rules/attr-unsafe-chars).
    $text = str_replace(["\u{00AD}", "\u{200E}"], '', $input);

    // Remove newlines and extra spaces.
    // https://html-validate.org/rules/attr-delimiter.html.
    // https://html-validate.org/rules/no-trailing-whitespace.html.
    // https://htmlhint.com/docs/user-guide/rules/attr-whitespace.
    $text = preg_replace('/\s+/', ' ', $text);
    assert(is_string($text));

    if ($escape_html) {
        $text = htmlspecialchars($text);
    }

    if ($end_with_dot) {
        // Remove any existing trailing dot character.
        $text = rtrim($text, ". \n\r\t\v\x00");

        $ending_punctuation = ['?', '!', '…', ';', '*'];
        $needs_dot = true;
        foreach ($ending_punctuation as $punct) {
            if (str_ends_with($text, $punct)) {
                $needs_dot = false;

                break;
            }
        }

        if ($needs_dot) {
            $text .= '.';
        }
    }

    return mb_trim($text);
}

/**
 * Gets the current search page number, defaulting to 1.
 */
function get_search_page_number(): int
{
    if (isset($_GET['pagina']) && is_string($_GET['pagina'])) {
        $pagina = (int) $_GET['pagina'];
        if ($pagina > 0) {
            return $pagina;
        }
    }

    return 1;
}

/**
 * Returns the paremiotipus name for display.
 *
 * The paremiotipus parameter specifies the identifier.
 * Set escape_html to false to disable HTML escaping.
 * Set use_fallback_string to false to prevent using the input as fallback if not found.
 */
function get_paremiotipus_display(string $paremiotipus, bool $escape_html = true, bool $use_fallback_string = true): string
{
    static $stmt = null;
    if ($stmt === null) {
        $stmt = get_db()->prepare('SELECT `Display` FROM `paremiotipus_display` WHERE `Paremiotipus` = :paremiotipus');
    }

    $display = cache_get($paremiotipus, static function () use ($paremiotipus, $stmt): string {
        $stmt->execute([':paremiotipus' => $paremiotipus]);
        $value = $stmt->fetchColumn();
        if ($value === false) {
            error_log("Error: paremiotipus '{$paremiotipus}' not found in paremiotipus_display table");

            return '';
        }

        assert(is_string($value));

        return $value;
    });

    if ($display === '' && $use_fallback_string) {
        $display = $paremiotipus;
    }

    return $escape_html ? htmlspecialchars($display) : $display;
}

/**
 * Returns the path for a paremiotipus/obra title.
 *
 * The name parameter specifies the name to convert to a slug.
 * Set encode to false to disable URL-encoding the slug.
 */
function name_to_slug(string $name, bool $encode = true): string
{
    $path = str_replace([' ', '/'], ['_', '\\'], $name);

    return $encode ? rawurlencode($path) : $path;
}

/**
 * Returns the name for a paremiotipus/obra querystring.
 *
 * The path parameter specifies the slug/path to convert back to a name.
 */
function slug_to_name(string $path): string
{
    return str_replace(['_', '\\'], [' ', '/'], $path);
}

/**
 * Returns the REQUEST_URI.
 *
 * @psalm-suppress PossiblyUndefinedArrayOffset, RedundantCondition
 */
function get_request_uri(): string
{
    $request_uri = $_SERVER['REQUEST_URI'];
    assert(is_string($request_uri));

    return $request_uri;
}

/**
 * Routes the request based on REQUEST_URI and populates $_GET accordingly.
 *
 * This centralizes routing logic in PHP, making it portable across web servers
 * (Apache, Nginx, etc.) without needing to maintain separate rewrite rules.
 */
function route_request(): void
{
    $request_uri = get_request_uri();

    // Remove query string for path matching.
    $path = parse_url($request_uri, PHP_URL_PATH);
    if (!is_string($path)) {
        return;
    }

    // Default: homepage/search (no routing needed, PageRenderer handles it).
    if ($path === '/' || $path === '') {
        return;
    }

    // Route: static pages.
    $path_without_slash = ltrim($path, '/');
    if (in_array($path_without_slash, PageRenderer::STATIC_PAGE_NAMES, true)) {
        $_GET[$path_without_slash] = '';

        return;
    }

    // Decode URL-encoded characters for matching.
    $path = rawurldecode($path);

    // Route: /p/{slug} → paremiotipus page.
    if (preg_match('#^/p/(.+)$#', $path, $matches) === 1) {
        $_GET['paremiotipus'] = $matches[1];

        return;
    }

    // Route: /obra/{slug} → obra page.
    if (preg_match('#^/obra/(.+)$#', $path, $matches) === 1) {
        $_GET['obra'] = $matches[1];

        return;
    }

    // Route: /og/{slug}.png → OG image.
    if (preg_match('#^/og/(.+)\.png$#', $path, $matches) === 1) {
        render_og_image_and_exit($matches[1]);
    }

    // Anything else is a 404.
    return_404_and_exit();
}

/**
 * Renders an OG image for the provided paremiotipus slug and exits.
 */
function render_og_image_and_exit(string $paremiotipus_slug): void
{
    $paremiotipus = slug_to_name($paremiotipus_slug);
    if ($paremiotipus === '') {
        return_404_and_exit();
    }

    $text = get_paremiotipus_display(paremiotipus: $paremiotipus, escape_html: false, use_fallback_string: false);
    if ($text === '') {
        return_404_and_exit();
    }

    $text_length = mb_strlen($text);

    $image = imagecreatetruecolor(OG_IMAGE_WIDTH, OG_IMAGE_HEIGHT);
    assert($image !== false, 'Failed to create image');
    imagetruecolortopalette($image, true, 2);

    $bg_color = imagecolorallocate(image: $image, red: 43, green: 87, blue: 151);
    assert($bg_color !== false, 'Failed to allocate background color');
    imagefilledrectangle($image, 0, 0, OG_IMAGE_WIDTH, OG_IMAGE_HEIGHT, $bg_color);

    $text_color = imagecolorallocate(image: $image, red: 255, green: 255, blue: 255);
    assert($text_color !== false, 'Failed to allocate text color');

    $wrapped_text = wordwrap(string: $text, width: OG_TEXT_MAX_LENGTH / OG_TEXT_MAX_LINES, cut_long_words: true);
    $n_lines = substr_count($wrapped_text, "\n") + 1;

    if ($n_lines > OG_TEXT_MAX_LINES) {
        $lines = explode("\n", $wrapped_text);
        $wrapped_text = implode("\n", array_slice($lines, 0, OG_TEXT_MAX_LINES));
        $wrapped_text = rtrim($wrapped_text, OG_TRIM_CHARS) . '…';
        $n_lines = substr_count($wrapped_text, "\n") + 1;
    }

    // Adjust font size based on text length.
    $font_size = 400;
    if ($n_lines === 4) {
        $font_size = 70;
    } elseif ($n_lines === 3) {
        $font_size = 80;
    } elseif ($text_length > 16) {
        $font_size = 90;
    } elseif ($text_length > 8) {
        $font_size = 100;
    } elseif ($text_length > 6) {
        $font_size = 150;
    } elseif ($text_length > 4) {
        $font_size = 175;
    } elseif ($text_length > 2) {
        $font_size = 200;
    }

    // Calculate text box size and position to center it.
    do {
        $bbox = imagettfbbox($font_size, 0, OG_FONT_PATH, $wrapped_text);
        assert($bbox !== false, 'Failed to calculate text bounding box');

        $text_width = $bbox[4] - $bbox[0];
        $text_height = $bbox[5] - $bbox[1];

        $x = (int) ((OG_IMAGE_WIDTH - $text_width) / 2);
        $y = (int) ((OG_IMAGE_HEIGHT - $text_height) / 2);

        if ($text_width + 100 > OG_IMAGE_WIDTH) {
            $font_size -= 10;
        }
    } while ($text_width + 100 > OG_IMAGE_WIDTH);

    // Adapt top margin.
    if ($n_lines === 2) {
        $y = 290;
    } elseif ($n_lines === 3) {
        $y = 220;
    } elseif ($n_lines === 4) {
        $y = 170;
    } elseif ($text_length > 8) {
        $y -= 10;
    } elseif ($text_length > 4) {
        $y -= 20;
    }

    // Add text to image.
    imagettftext(
        image: $image,
        size: $font_size,
        angle: 0,
        x: $x,
        y: $y,
        color: $text_color,
        font_filename: OG_FONT_PATH,
        text: $wrapped_text
    );

    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=31536000, immutable');
    imagepng($image);

    exit;
}

/**
 * Returns a canonical URL for the paremiotipus.
 *
 * The paremiotipus parameter specifies the identifier.
 * Set absolute to true for an absolute URL.
 * Set encode_full_url to true to encode the full URL.
 */
function get_paremiotipus_url(string $paremiotipus, bool $absolute = false, bool $encode_full_url = false): string
{
    $base_url = '';
    if ($absolute) {
        $base_url = 'https://pccd.dites.cat';
    }

    if ($encode_full_url) {
        return rawurlencode($base_url . '/p/' . name_to_slug($paremiotipus, encode: false));
    }

    return $base_url . '/p/' . name_to_slug($paremiotipus);
}

/**
 * Returns a canonical URL for the obra.
 *
 * The obra parameter specifies the identifier.
 * Set absolute to true for an absolute URL.
 */
function get_obra_url(string $obra, bool $absolute = false): string
{
    $base_url = '';
    if ($absolute) {
        $base_url = 'https://pccd.dites.cat';
    }

    return $base_url . '/obra/' . name_to_slug($obra);
}

/**
 * Formats an integer in Catalan.
 *
 * The num parameter specifies the number to format.
 * The decimals parameter specifies the number of decimals.
 */
function format_nombre(float|int|string $num, int $decimals = 0): string
{
    return number_format((float) $num, decimals: $decimals, decimal_separator: ',', thousands_separator: '.');
}

/**
 * Returns an array of languages from the database.
 *
 * From the 00_EQUIVALENTS table, it returns `IDIOMA` values keyed by `CODI`.
 *
 * @return array<string, string> Array of language codes to language names.
 */
function get_idiomes(): array
{
    return cache_get('equivalents', static function (): array {
        $stmt = get_db()->query('SELECT `CODI`, `IDIOMA` FROM `00_EQUIVALENTS`');

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    });
}

/**
 * Gets a language name in lowercase from its language code, or an empty string.
 *
 * The input_code parameter specifies the language code to look up.
 */
function get_idioma(string $input_code): string
{
    $code = strtoupper(trim($input_code));
    $languages = get_idiomes();

    return mb_strtolower($languages[$code] ?? '');
}

/**
 * Tries to return a valid ISO 639-1/639-2 code when given a potentially wrong code coming from the database.
 *
 * The input_code parameter specifies the language code to correct.
 */
function get_idioma_iso_code(string $input_code): string
{
    $code = strtolower(trim($input_code));
    if (preg_match('/^[a-z]{2,3}$/', $code) !== 1) {
        return '';
    }

    $corrections = [
        // `ar` is the ISO code for Arabic, but in the database it is used for Aranès and Argentinian (Spanish).
        'ar' => 'oc',
        'as' => 'ast',
        // `bs` is the ISO code for Bosnian, but in the database it is used for Serbocroata.
        'bs' => 'sh',
        'll' => 'la',
        // `ne` is the ISO code Official Nepali Native, but in the database may be used for Dutch.
        // 'ne' => 'nl',
        'po' => 'pl',
        // ISO code for Provençal is missing. "pro" is for Old Provençal, and "prv" is no longer recognised. In the
        // database we have "pr", which is not assigned by ISO.
        'pr' => 'oc',
        'sa' => 'sc',
        // `si` is the ISO code of Sinhalese, but in the database it is used for Sicilian.
        'si' => 'scn',
    ];

    return $corrections[$code] ?? $code;
}

/**
 * Returns the ISO 639-1/639-2 code for a language name in Catalan.
 *
 * The input_name parameter specifies the language name in Catalan.
 */
function get_idioma_iso_code_from_name(string $input_name): string
{
    $name = mb_strtolower(trim($input_name));
    $languages = get_idiomes();

    foreach ($languages as $code => $language) {
        if (mb_strtolower($language) === $name) {
            return get_idioma_iso_code($code);
        }
    }

    return '';
}

/**
 * Normalize and remove special characters from a string, especially for matching a paremiotipus.
 *
 * The input_string parameter specifies the input string to normalize.
 * The search_mode parameter specifies the search mode to normalize for. If provided, the string is processed for search.
 */
function normalize_search(string $input_string, ?SearchMode $search_mode = null): string
{
    // Remove useless characters in search that may affect syntax, or that are not useful.
    $string = str_replace(
        ['"', '+', '.', '%', '--', '_', '(', ')', '[', ']', '{', '}', '^', '>', '<', '~', '@', '$', '|', '/', '\\'],
        '',
        $input_string
    );

    $string = str_replace('’', "'", $string);

    $string = preg_replace('/\s+/', ' ', $string);
    assert(is_string($string));

    // Fix characters for search.
    if ($search_mode === SearchMode::WHOLE_SENTENCE) {
        // Remove wildcards and unnecessary characters.
        $string = str_replace(['*', '?'], '', $string);
    } elseif ($search_mode === SearchMode::WILDCARD) {
        // Replace wildcard characters.
        $string = str_replace(['*', '?'], ['.*', '.'], $string);
    } elseif ($search_mode === SearchMode::CONTAINS) {
        // Remove characters that may affect FULL-TEXT search syntax.
        $string = str_replace(['*', '?'], '', $string);

        // Remove loose `-` operator.
        $string = str_replace(' - ', ' ', $string);

        // Nice to have: remove extra useless characters (not `-`).
        $string = str_replace(
            ['“', '”', '«', '»', '…', ',', ':', ';', '!', '¡', '¿', '–', '—', '―', '─'],
            '',
            $string
        );

        // Build the full-text query.
        $words = preg_split('/\s+/', $string);
        assert(is_array($words));
        $string = '';
        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            if (str_starts_with($word, '-')) {
                // Respect `-` operator.
                $string .= '-';
                $word = ltrim($word, '-');
            } else {
                // Manually put the `+` operator to ensure the word is searched.
                $string .= '+';
            }

            if (str_contains($word, '-')) {
                // See https://stackoverflow.com/a/5192800/1391963.
                $string .= '"' . $word . '" ';
            } else {
                $string .= "{$word} ";
            }
        }
    }

    return mb_trim($string);
}

/**
 * Returns array of 00_EDITORIA `NOM` values keyed by `CODI`.
 *
 * @return array<string, string> Array of editorial codes to names.
 */
function get_editorials(): array
{
    return cache_get('editorials', static function (): array {
        $stmt = get_db()->query('SELECT `CODI`, `NOM` FROM `00_EDITORIA`');

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    });
}

/**
 * Returns array of 00_FONTS `Títol` values keyed by `Identificador`.
 *
 * Used in the paremiotipus page and in some reports.
 *
 * @return array<string, string> Array of font identifiers to titles.
 */
function get_fonts_paremiotipus(): array
{
    return cache_get('fonts', static function (): array {
        $stmt = get_db()->query('SELECT `Identificador`, `Títol` FROM `00_FONTS`');

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    });
}

/**
 * Generates HTML markup for a .jpg, .png or .gif image, within a <picture> with avif/webp alternatives.
 *
 * The file_name parameter specifies the file name of the image file.
 * The path parameter specifies the path to the image file, starting with a slash.
 * The alt_text parameter specifies the alternative text for the image.
 * Set escape_html to false to disable escaping the alternative text.
 * The width parameter specifies the width attribute for the <img> tag ('0' means not set).
 * The height parameter specifies the height attribute for the <img> tag ('0' means not set).
 * Set lazy_loading to false to disable lazy loading.
 * Set preload to true to add a preload HTTP header for the image.
 * The preload_media parameter adds the media rule to the preloaded image.
 */
function render_image_tags(
    string $file_name,
    string $path,
    string $alt_text = '',
    bool $escape_html = true,
    string $width = '0',
    string $height = '0',
    bool $lazy_loading = true,
    bool $preload = false,
    string $preload_media = ''
): string {
    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // We provide AVIF for JPEG/PNG images, and WEBP for GIF.
    [$optimized_extension, $mime_type] = match ($extension) {
        'jpg', 'png' => ['avif', 'image/avif'],
        'gif' => ['webp', 'image/webp'],
        default => ['', ''],
    };

    if ($optimized_extension === '') {
        return '';
    }

    $file_url = $path . rawurlencode($file_name);
    $optimized_file = preg_replace('/\.' . $extension . '$/i', '.' . $optimized_extension, $file_name);
    assert(is_string($optimized_file));
    $optimized_file_url = $path . rawurlencode($optimized_file);

    $image_tags = '<picture>';
    $image_tags .= '<source srcset="' . $optimized_file_url . '" type="' . $mime_type . '">';
    $image_tags .= '<img alt="' . ($escape_html ? htmlspecialchars($alt_text) : $alt_text) . '"';
    if ($lazy_loading) {
        $image_tags .= ' loading="lazy"';
    }
    if ($width !== '0' && $height !== '0') {
        $image_tags .= ' width="' . $width . '" height="' . $height . '"';
    }
    $image_tags .= ' src="' . $file_url . '">';
    $image_tags .= '</picture>';

    if ($preload) {
        // For using preload/lazyload at the same time, see https://news.ycombinator.com/item?id=46574640.
        preload_image_header(url: $optimized_file_url, media: $preload_media, type: $mime_type);
    }

    return $image_tags;
}

/**
 * Adds an HTTP header to preload an image.
 *
 * The url parameter specifies the URL of the image to preload.
 * The media parameter specifies the media attribute for the preload header.
 * The type parameter specifies the MIME type of the image.
 */
function preload_image_header(string $url, string $media = '', string $type = ''): void
{
    $header = "Link: <{$url}>; rel=preload; as=image";
    if ($type !== '') {
        $header .= "; type={$type}";
    }
    if ($media !== '') {
        $header .= "; media=\"{$media}\"";
    }
    header($header);

    // The idea here was to flush the headers early, allowing the browser to start
    // preloading the specified image before the full HTML content is generated.
    // However, the practical impact of this optimization has proven to be minimal
    // for 2 reasons:
    // 1. The page is already generated very quickly.
    // 2. The header is added late in the script execution.
    //
    // Benchmarking with Chrome DevTools and curl showed marginal improvements of
    // ~2-5ms in the time for headers to be received. Moving the preload logic
    // earlier in the script (e.g., at the top of a long "paremiotipus" page)
    // reduced this time further to ~12-17ms, still well below the threshold of
    // human perception.
    //
    // ob_flush();
    // flush();
}

/**
 * Returns an HTTP 404 page and exits.
 *
 * If input_paremiotipus is not empty, suggests to visit that paremiotipus page (which should exist).
 */
function return_404_and_exit(string $input_paremiotipus = ''): never
{
    header('HTTP/1.1 404 Not Found', response_code: 404);

    require __DIR__ . '/../docroot/404.html';
    if ($input_paremiotipus !== '') {
        $url = get_paremiotipus_url($input_paremiotipus);
        $paremiotipus = get_paremiotipus_display($input_paremiotipus);
        echo "<p>També us pot ser útil la pàgina del paremiotipus <a href='{$url}'>{$paremiotipus}</a>.";
    }

    exit;
}

/**
 * Returns the total number of occurrences (modismes).
 */
function get_modisme_count(): int
{
    return cache_get('modisme_count', static function (): int {
        $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS`');

        return (int) $stmt->fetchColumn();
    });
}

/**
 * Returns the total number of distinct paremiotipus.
 */
function get_paremiotipus_count(): int
{
    return cache_get('paremiotipus_count', static function (): int {
        $stmt = get_db()->query('SELECT COUNT(1) FROM `paremiotipus_display`');

        return (int) $stmt->fetchColumn();
    });
}

/**
 * Returns the total number of individual authors (informants).
 */
function get_informant_count(): int
{
    return cache_get('informant_count', static function (): int {
        $stmt = get_db()->query('SELECT COUNT(DISTINCT `AUTOR`) FROM `00_PAREMIOTIPUS`');

        return (int) $stmt->fetchColumn();
    });
}

/**
 * Returns the total number of sources (fonts).
 */
function get_font_count(): int
{
    return cache_get('font_count', static function (): int {
        $stmt = get_db()->query('SELECT COUNT(1) FROM `00_FONTS`');

        return (int) $stmt->fetchColumn();
    });
}

/**
 * Returns a random top $max paremiotipus from top 10000.
 *
 * The max parameter specifies the maximum number of top paremiotipus to consider.
 */
function get_random_top_paremiotipus(int $max = 10000): string
{
    // mt_rand() is faster than random_int(), and does not throw exceptions if the necessary
    // entropy cannot be gathered. And it is random enough for this use case.
    $random_index = mt_rand(0, $max - 1);

    return cache_get("top_paremiotipus_{$random_index}", static function () use ($random_index): string {
        $stmt = get_db()->query("SELECT `Paremiotipus` FROM `common_paremiotipus` ORDER BY `Compt` DESC LIMIT 1 OFFSET {$random_index}");

        $random = $stmt->fetchColumn();
        if (!is_string($random)) {
            // We may be using a sample DB, try falling back to the first record.
            $random_index = 0;
            $stmt = get_db()->query("SELECT `Paremiotipus` FROM `common_paremiotipus` ORDER BY `Compt` DESC LIMIT 1 OFFSET {$random_index}");
            $random = $stmt->fetchColumn();
            if (!is_string($random)) {
                exit('PCCD may have not been installed after importing a new database. Consider running `npm run install:db` or reading the docs.');
            }
        }

        return $random;
    });
}

/**
 * Returns all books by Víctor Pàmies.
 *
 * @return non-empty-list<Book> Array of Book objects.
 */
function get_books(): array
{
    return cache_get('llibres', static function (): array {
        $stmt = get_db()->query('SELECT `Imatge`, `Títol`, `URL`, `WIDTH`, `HEIGHT` FROM `00_OBRESVPR`');

        /** @var non-empty-list<Book> */
        return $stmt->fetchAll(PDO::FETCH_CLASS, Book::class);
    });
}

/**
 * Returns a random book by Víctor Pàmies.
 */
function get_random_book(): Book
{
    $books = get_books();

    return $books[array_rand($books)];
}

/**
 * Returns the latest database date from the data/db_date.txt file.
 */
function get_latest_db_date(): string
{
    ob_start();

    // Using require to allow serving the file from memory (OPcache).
    require __DIR__ . '/../data/db_date.txt';
    $content = ob_get_clean();

    assert($content !== false);

    return trim($content);
}

/**
 * Returns the copyright notice.
 */
function get_copyright_notice(): string
{
    $current_year = date('Y');

    return "© Víctor Pàmies i Riudor, 2020-{$current_year}.";
}
