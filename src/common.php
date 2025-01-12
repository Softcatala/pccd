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

const PAGER_DEFAULT = 10;
const TITLE_MAX_LENGTH = 70;

// See https://wiki.php.net/rfc/mb_ucfirst.
if (!function_exists('mb_ucfirst')) {
    /**
     * ucfirst() function for multibyte character encodings.
     *
     * Borrowed from https://stackoverflow.com/a/58915632/1391963.
     */
    function mb_ucfirst(string $str, ?string $encoding = null): string
    {
        return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, null, $encoding);
    }
}

final readonly class Variant
{
    public string $PAREMIOTIPUS;
    public string $AUTOR;
    public string $AUTORIA;
    public string $DIARI;
    public string $ARTICLE;
    public string $EDITORIAL;
    public string $ANY;
    public string $PAGINA;
    public string $LLOC;
    public string $EXPLICACIO;
    public string $EXPLICACIO2;
    public string $EXEMPLES;
    public string $SINONIM;
    public string $EQUIVALENT;
    public string $IDIOMA;
    public string $FONT;
    public string $ACCEPCIO;
    public string $ID_FONT;
}

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

final readonly class Image
{
    public string $Identificador;
    public string $URL_ENLLAÇ;
    public string $AUTOR;
    public string $ANY;
    public string $DIARI;
    public string $ARTICLE;
    public string $WIDTH;
    public string $HEIGHT;
}

final readonly class Book
{
    private const URL_FIXES = [
        'https://lafinestralectora.cat/els-100-refranys-mes-populars-2/' => 'https://lafinestralectora.cat/els-100-refranys-mes-populars/',
    ];
    private string $Imatge;
    private string $Títol;
    private string $URL;
    private string $WIDTH;
    private string $HEIGHT;

    /**
     * @param array{
     *     alt_text?: string,
     *     file_name?: string,
     *     height?: int,
     *     lazy_loading?: bool,
     *     path?: string,
     *     width?: int,
     *     preload?: bool,
     *     preload_media?: string
     * } $imageOptions
     */
    public function render(array $imageOptions = []): string
    {
        $url = self::URL_FIXES[$this->URL] ?? $this->URL;
        $html = '';
        if ($url !== '') {
            $html .= '<a href="' . $url . '" title="' . htmlspecialchars($this->Títol) . '">';
        }

        // Default image options.
        $defaultOptions = [
            'alt_text' => $this->Títol,
            'file_name' => $this->Imatge,
            'height' => (int) $this->HEIGHT,
            'path' => '/img/obres/',
            'width' => (int) $this->WIDTH,
        ];

        // Generate image tags, merging provided options.
        $html .= get_image_tags(...$imageOptions + $defaultOptions);

        if ($url !== '') {
            $html .= '</a>';
        }

        return $html;
    }
}

/**
 * Render the page.
 */
function render_page(): void
{
    // Redirect to the homepage if 'index.php' is in the URL.
    if (str_contains(get_request_uri(), 'index.php')) {
        header('Location: /');

        exit;
    }

    // Cache pages for 15 minutes in the browser.
    header('Cache-Control: public, max-age=900');

    // Include the page template.
    require __DIR__ . '/template.php';
}

/**
 * Generic cache wrapper to get data from cache (APCu).
 *
 * @template T
 *
 * @param callable(): T $callback
 *
 * @return T
 */
function cache_get(string $key, callable $callback)
{
    if (!extension_loaded('apcu')) {
        return $callback();
    }

    $cached = apcu_fetch($key);
    if ($cached !== false) {
        return $cached;
    }

    $value = $callback();
    apcu_store($key, $value);

    return $value;
}

/**
 * Transforms plain text into valid HTML turning URLs into links.
 */
function html_escape_and_link_urls(string $text, string $property = '', bool $debug = false): string
{
    $escaped = htmlspecialchars($text, ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML5);
    $pattern = '/(https?:\/\/[^\s]+?)(?=[.,;:!?)"\']*(?:\s|&gt;|$))/';

    $output = preg_replace_callback($pattern, static function (array $matches) use ($debug, $property): string {
        $url = $matches[1];
        if ($debug) {
            file_put_contents(
                __DIR__ . '/../tmp/test_tmp_debug_html_escape_and_link_urls.txt',
                $url . "\n",
                FILE_APPEND
            );
        }

        $link = '<a class="external" target="_blank" rel="noopener" href="' . $url . '"';
        if ($property !== '') {
            $link .= ' property="' . $property . '"';
        }

        return $link . ">{$url}</a>";
    }, $escaped);

    assert(is_string($output));

    return $output;
}

/**
 * Gets a clean sinonim, removing unnecessary characters or notes.
 */
function get_sinonim_clean(string $sinonim): string
{
    // Try to remove annotations.
    $pos = mb_strpos($sinonim, '[');
    if ($pos !== false) {
        $sinonim = mb_substr($sinonim, 0, $pos);
    }

    // Remove unnecessary characters or words.
    $sinonim = trim($sinonim, ". \n\r\t\v\x00");
    $sinonim = str_replace(
        [
            '*',
            ' / ',
            'v.',
            'V.',
            'Veg.',
            'tb.',
            'Connex:',
            'Connexos:',
            'Similar, l\'expressió:',
            'Similars, les expressions:',
            'Similar:',
            'Similars:',
            'Contrari:',
            'Contraris:',
            '(incorrecte)',
            '(cast.)',
        ],
        ' ',
        $sinonim
    );
    $sinonim = preg_replace('/\s\s+/', ' ', $sinonim);
    assert(is_string($sinonim));

    // Remove last character if it is a number.
    if (preg_match('/\d$/', $sinonim) === 1) {
        $sinonim = substr($sinonim, 0, -1);
    }

    return trim($sinonim);
}

/**
 * Gets multiple sinonims from a SINONIM field.
 *
 * @return list<string>
 */
function get_sinonims(string $sinonim_field): array
{
    $sinonims = explode('|', $sinonim_field);

    $sinonims_array = [];
    foreach ($sinonims as $sinonim) {
        // Try to remove unnecessary characters or words.
        $sinonim = get_sinonim_clean($sinonim);

        // Discard empty or short records.
        if (mb_strlen($sinonim) < 3) {
            continue;
        }

        $sinonims_array[] = $sinonim;
    }

    return $sinonims_array;
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
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_TO_STRING,
            PDO::ATTR_STRINGIFY_FETCHES => true,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
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
 * Formats an HTML title, truncated to 70 characters.
 */
function format_html_title(string $title, string $suffix = ''): string
{
    if (mb_strlen($title) > TITLE_MAX_LENGTH) {
        $truncated_title = mb_substr($title, 0, TITLE_MAX_LENGTH - 2);
        $last_space_pos = mb_strrpos($truncated_title, ' ');
        if ($last_space_pos !== false) {
            $title = mb_substr($truncated_title, 0, $last_space_pos) . '…';
        }
    }

    if ($suffix !== '') {
        $full_title = $title . ' - ' . $suffix;
        if (mb_strlen($full_title) <= TITLE_MAX_LENGTH) {
            $title = $full_title;
        }
    }

    return $title;
}

/**
 * ISBN simple (but incorrect) validation.
 */
function isbn_is_valid(string $isbn): bool
{
    $isbn = str_replace('-', '', $isbn);
    $isbn_removed_chars = preg_replace('/[^a-zA-Z0-9]/', '', $isbn);

    return $isbn === $isbn_removed_chars && (strlen($isbn) === 10 || strlen($isbn) === 13);
}

/**
 * Returns the pagination limit from query string. Defaults to 10.
 */
function get_page_limit(): int
{
    if (isset($_GET['mostra'])) {
        $mostra = $_GET['mostra'];
        if ($mostra === '15' || $mostra === '25' || $mostra === '50') {
            return (int) $mostra;
        }
        if ($mostra === 'infinit') {
            return 999999;
        }
    }

    if (isset($_GET['font'])) {
        return 999999;
    }

    return PAGER_DEFAULT;
}

/**
 * Trims and removes newlines, extra spaces and unsafe characters from the provided string.
 *
 * Optionally and by default, escapes HTML and ensures the string ends with a dot or punctuation.
 */
function ct(string $text, bool $escape_html = true, bool $end_with_dot = true): string
{
    // Remove unsafe characters (https://htmlhint.com/docs/user-guide/rules/attr-unsafe-chars).
    $text = preg_replace("/\u{00AD}/", '', $text);
    assert(is_string($text));
    $text = preg_replace("/\u{200E}/", '', $text);
    assert(is_string($text));

    // Remove newlines and extra spaces.
    // https://html-validate.org/rules/attr-delimiter.html.
    // https://html-validate.org/rules/no-trailing-whitespace.html.
    // https://htmlhint.com/docs/user-guide/rules/attr-whitespace.
    $text = preg_replace('/\n/', ' ', $text);
    assert(is_string($text));
    $text = preg_replace('/\s+/', ' ', $text);
    assert(is_string($text));

    // Escape HTML.
    if ($escape_html) {
        $text = htmlspecialchars($text);
    }

    if ($end_with_dot) {
        // Remove trailing dot character.
        $text = trim($text, ". \n\r\t\v\x00");

        // Add trailing dot character.
        if (
            !str_ends_with($text, '?')
            && !str_ends_with($text, '!')
            && !str_ends_with($text, '…')
            && !str_ends_with($text, ';')
            && !str_ends_with($text, '*')
        ) {
            $text .= '.';
        }
    }

    return trim($text);
}

/**
 * Returns the current page name.
 */
function get_page_name(): string
{
    $allowed_pages = ['credits', 'instruccions', 'fonts', 'llibres', 'obra', 'paremiotipus', 'projecte', 'top100', 'top10000'];

    foreach ($allowed_pages as $allowed_page) {
        if (isset($_GET[$allowed_page])) {
            return $allowed_page;
        }
    }

    // Default to the search page, which is also the homepage.
    return 'search';
}

/**
 * Returns whether a checkbox should be checked in the search page.
 */
function checkbox_checked(string $checkbox): bool
{
    if (isset($_GET[$checkbox])) {
        return true;
    }

    // "variants" checkbox is enabled by default when the search is empty (e.g. in the homepage)
    return $checkbox === 'variant' && (!isset($_GET['cerca']) || $_GET['cerca'] === '');
}

/**
 * Returns the paremiotipus side blocks HTML.
 */
function get_paremiotipus_blocks(): string
{
    global $paremiotipus_blocks;

    return $paremiotipus_blocks ?? '';
}

/**
 * Sets the paremiotipus side blocks HTML.
 */
function set_paremiotipus_blocks(string $blocks): void
{
    global $paremiotipus_blocks;

    $paremiotipus_blocks = $blocks;
}

/**
 * Returns the side blocks HTML.
 */
function render_side_blocks(string $page_name): string
{
    $side_blocks = '';
    if ($page_name === 'search') {
        // Homepage and search pages show Top 100 and Books blocks.
        $random_paremiotipus = get_random_top_paremiotipus(100);
        $side_blocks = '<div class="bloc" data-nosnippet>';
        $side_blocks .= '<p class="text-balance">';
        $side_blocks .= '«<a href="' . get_paremiotipus_url($random_paremiotipus) . '">';
        $side_blocks .= get_paremiotipus_display($random_paremiotipus);
        $side_blocks .= '</a>»';
        $side_blocks .= '</p>';
        $side_blocks .= '<div class="footer"><a href="/top100">Les 100 parèmies més citades</a></div>';
        $side_blocks .= '</div>';

        $side_blocks .= '<div class="bloc bloc-books">';
        $side_blocks .= '<p><a href="/llibres">Llibres de l\'autor</a></p>';
        $side_blocks .= get_random_book()->render([
            'preload' => true,
            'preload_media' => '(min-width: 768px)',
        ]);
        $side_blocks .= '</div>';
    } elseif ($page_name === 'paremiotipus') {
        $side_blocks = get_paremiotipus_blocks();
    }

    // All pages show the credits block.
    $side_blocks .= '<div class="bloc bloc-credits bloc-white">';
    $side_blocks .= '<p>Un projecte de:</p>';
    $side_blocks .= '<p><a href="http://www.dites.cat">dites.cat</a></p>';
    $side_blocks .= '<p><a href="https://www.softcatala.org"><img alt="Softcatalà" width="120" height="80" src="/img/logo-softcatala.svg"></a></p>';
    $side_blocks .= '</div>';

    if ($page_name !== 'search') {
        // All non-search pages show the Top 10000 block, after credits.
        $random_paremiotipus = get_random_top_paremiotipus(10000);
        $side_blocks .= '<div class="bloc" data-nosnippet>';
        $side_blocks .= '<p class="text-balance">';
        $side_blocks .= '«<a href="' . get_paremiotipus_url($random_paremiotipus) . '">';
        $side_blocks .= get_paremiotipus_display($random_paremiotipus);
        $side_blocks .= '</a>»';
        $side_blocks .= '</p>';
        $side_blocks .= '<div class="footer">Les 10.000 parèmies més citades</div>';
        $side_blocks .= '</div>';
    }

    return $side_blocks;
}

/**
 * Returns the page title.
 */
function get_page_title(): string
{
    global $page_title;

    return $page_title ?? '';
}

/**
 * Sets the page title.
 */
function set_page_title(string $title): void
{
    global $page_title;

    // Remove some unsafe and unwanted characters.
    $page_title = ct(text: $title, escape_html: false, end_with_dot: false);
}

/**
 * Returns the canonical URL.
 */
function get_canonical_url(): string
{
    global $canonical_url;

    return $canonical_url ?? '';
}

/**
 * Sets the canonical URL.
 */
function set_canonical_url(string $url): void
{
    global $canonical_url;

    $canonical_url = $url;
}

/**
 * Returns the meta description.
 */
function get_meta_description(): string
{
    global $meta_description;

    return $meta_description ?? '';
}

/**
 * Sets the meta description, but only once.
 */
function set_meta_description(string $description): void
{
    global $meta_description;

    $meta_description = $description;
}

/**
 * Sets the meta description, but only once.
 */
function set_meta_description_once(string $description): void
{
    global $meta_description;

    if ($meta_description === null || $meta_description === '') {
        $meta_description = $description;
    }
}

/**
 * Returns the meta image URL.
 */
function get_meta_image(): string
{
    global $meta_image;

    return $meta_image ?? '';
}

/**
 * Sets the meta image URL.
 */
function set_meta_image(string $image_url): void
{
    global $meta_image;

    $meta_image = $image_url;
}

/**
 * Returns the og:audio URL.
 */
function get_og_audio_url(): string
{
    global $og_audio_url;

    return $og_audio_url ?? '';
}

/**
 * Sets the og:audio URL.
 */
function set_og_audio_url(string $audio_url): void
{
    global $og_audio_url;

    $og_audio_url = $audio_url;
}

/**
 * Returns the og:type.
 */
function get_og_type(): string
{
    global $og_type;

    return $og_type ?? '';
}

/**
 * Sets the og:type.
 */
function set_og_type(string $type): void
{
    global $og_type;

    $og_type = $type;
}

/**
 * Returns page-specific meta tags.
 */
function render_page_meta_tags(string $page_name): string
{
    $meta_tags = '';
    if ($page_name === 'search') {
        $is_homepage = (!isset($_GET['cerca']) || $_GET['cerca'] === '') && get_page_number() === 1;
        if ($is_homepage) {
            // Set canonical URL.
            set_canonical_url('https://pccd.dites.cat');
        } else {
            // Do not index the rest of result pages.
            $meta_tags .= '<meta name="robots" content="noindex">';
        }

        // Provide nice-to-have social metadata for the homepage and search pages.
        $meta_tags .= '<meta name="twitter:card" content="summary_large_image">';
        $meta_tags .= '<meta property="og:type" content="website">';
        // See https://stackoverflow.com/q/71087872/1391963.
        $meta_tags .= '<meta name="twitter:image" property="og:image" content="https://pccd.dites.cat/img/screenshot.png">';
    } elseif (get_og_type() !== '') {
        // Set specific type if set. This is only used for books in obra pages for now.
        $meta_tags .= '<meta property="og:type" content="' . get_og_type() . '">';
    } else {
        // Set og:type article for all other pages.
        $meta_tags .= '<meta property="og:type" content="article">';
    }

    // Meta description may be set when building main content.
    if (get_meta_description() !== '') {
        $meta_tags .= '<meta name="description" property="og:description" content="' . get_meta_description() . '">';
    }

    // Meta image may be set in paremiotipus and obra pages.
    $meta_image = get_meta_image();
    if ($meta_image !== '') {
        if (str_contains($meta_image, '/og/')) {
            // Generated images are larger and suitable to be used with Summary Card with Large Image.
            $meta_tags .= '<meta name="twitter:card" content="summary_large_image">';
        } else {
            $meta_tags .= '<meta name="twitter:card" content="summary">';
        }

        // See https://stackoverflow.com/q/71087872/1391963.
        $meta_tags .= '<meta name="twitter:image" property="og:image" content="' . $meta_image . '">';
    }

    // og:audio URL may be set in paremiotipus pages.
    if (get_og_audio_url() !== '') {
        $meta_tags .= '<meta property="og:audio" content="' . get_og_audio_url() . '">';
    }

    // Canonical may be set above or in paremiotipus and obra pages.
    if (get_canonical_url() !== '') {
        $meta_tags .= '<link rel="canonical" href="' . get_canonical_url() . '">';
    }

    return $meta_tags;
}

/**
 * Returns the paremiotipus name for display.
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
            error_log("Error: '{$paremiotipus}' not found in paremiotipus_display table");

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
 */
function name_to_path(string $name, bool $encode = true): string
{
    $path = str_replace([' ', '/'], ['_', '\\'], $name);

    if ($encode) {
        return rawurlencode($path);
    }

    return $path;
}

/**
 * Returns the name for a paremiotipus/obra querystring.
 */
function path_to_name(string $path): string
{
    return str_replace(['_', '\\'], [' ', '/'], $path);
}

/**
 * Tries to get a paremiotipus from a modisme.
 */
function get_paremiotipus_by_modisme(string $modisme): string
{
    $stmt = get_db()->prepare('SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `MODISME` = :modisme LIMIT 1');
    $stmt->execute([':modisme' => $modisme]);

    $paremiotipus = $stmt->fetchColumn();
    $paremiotipus = $paremiotipus !== false ? $paremiotipus : '';
    assert(is_string($paremiotipus));

    return $paremiotipus;
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
 * Returns an HTTP 404 page and exits.
 *
 * @param string $paremiotipus if not empty, suggest to visit that paremiotipus page.
 */
function return_404_and_exit(string $paremiotipus = ''): never
{
    header('HTTP/1.1 404 Not Found', response_code: 404);

    require __DIR__ . '/../docroot/404.html';
    if ($paremiotipus !== '') {
        $url = get_paremiotipus_url($paremiotipus);
        $paremiotipus = get_paremiotipus_display($paremiotipus);
        echo "<p>També us pot ser útil la pàgina del paremiotipus <a href='{$url}'>{$paremiotipus}</a>.";
    }

    exit;
}

/**
 * Try to redirect to a valid paremiotipus page and exit.
 */
function try_to_redirect_to_valid_paremiotipus_and_exit(string $paremiotipus): void
{
    $paremiotipus = trim($paremiotipus);

    // Do nothing if the provided paremiotipus was empty.
    if ($paremiotipus === '') {
        return;
    }

    // Try to get the paremiotipus from the modisme.
    $paremiotipus_match = get_paremiotipus_by_modisme($paremiotipus);
    if ($paremiotipus_match !== '') {
        // Redirect to an existing page.
        header('Location: ' . get_paremiotipus_url($paremiotipus_match), response_code: 301);

        exit;
    }
}

/**
 * Tries to get the best paremiotipus by searching.
 */
function get_paremiotipus_best_match(string $modisme): string
{
    // We do not want to avoid words here.
    $modisme = trim($modisme, '-');
    $modisme = str_replace(' -', ' ', $modisme);
    $modisme = trim($modisme);

    $paremiotipus = false;
    $modisme = normalize_search($modisme, 'conté');
    if ($modisme !== '') {
        $stmt = get_db()->prepare('SELECT
            `PAREMIOTIPUS`
        FROM
            `00_PAREMIOTIPUS`
        WHERE
            MATCH(`PAREMIOTIPUS`, `MODISME`) AGAINST (? IN BOOLEAN MODE)
        ORDER BY
            LENGTH(`PAREMIOTIPUS`)
        LIMIT
            1');

        try {
            $stmt->execute([$modisme]);
        } catch (Exception $e) {
            error_log("Error: {$modisme} not found: " . $e->getMessage());

            return '';
        }

        $paremiotipus = $stmt->fetchColumn();
    }

    return is_string($paremiotipus) ? $paremiotipus : '';
}

/**
 * Gets an array of unique variants, keyed by MODISME.
 *
 * @return array<string, non-empty-list<Variant>>
 */
function get_modismes_by_variant(string $paremiotipus): array
{
    $stmt = get_db()->prepare('SELECT DISTINCT
        `MODISME`,
        `PAREMIOTIPUS`,
        `AUTOR`,
        `AUTORIA`,
        `DIARI`,
        `ARTICLE`,
        `EDITORIAL`,
        `ANY`,
        `PAGINA`,
        `LLOC`,
        `EXPLICACIO`,
        `EXPLICACIO2`,
        `EXEMPLES`,
        `SINONIM`,
        `EQUIVALENT`,
        `IDIOMA`,
        `FONT`,
        `ACCEPCIO`,
        `ID_FONT`
    FROM
        `00_PAREMIOTIPUS`
    WHERE
        `PAREMIOTIPUS` = :paremiotipus
    ORDER BY
        `MODISME`,
        ISNULL(`AUTOR`),
        `AUTOR`,
        `DIARI`,
        `ARTICLE`,
        `ANY`,
        `ID_FONT`,
        `PAGINA`,
        `SINONIM`,
        `IDIOMA`,
        `EQUIVALENT`,
        `LLOC`');
    $stmt->execute([':paremiotipus' => $paremiotipus]);

    return $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_CLASS, Variant::class);
}

/**
 * Gets a list of Image objects for a specific paremiotipus.
 *
 * @return list<Image>
 */
function get_images(string $paremiotipus): array
{
    $stmt = get_db()->prepare('SELECT
        `Identificador`,
        `URL_ENLLAÇ`,
        `AUTOR`,
        `ANY`,
        `DIARI`,
        `ARTICLE`,
        `WIDTH`,
        `HEIGHT`
    FROM
        `00_IMATGES`
    WHERE
        `PAREMIOTIPUS` = :paremiotipus
    ORDER BY
        `Comptador` DESC');
    $stmt->execute([':paremiotipus' => $paremiotipus]);

    /** @var list<Image> */
    return $stmt->fetchAll(PDO::FETCH_CLASS, Image::class);
}

/**
 * Gets a list of Common Voice mp3 files for a specific paremiotipus.
 *
 * @return list<string>
 */
function get_cv_files(string $paremiotipus): array
{
    $stmt = get_db()->prepare('SELECT `file` FROM `commonvoice` WHERE `paremiotipus` = :paremiotipus');
    $stmt->execute([':paremiotipus' => $paremiotipus]);

    /** @var list<string> */
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function get_obra(string $obra_title): false|Obra
{
    $stmt = get_db()->prepare('SELECT
        `Any_edició`,
        `Any`,
        `Autor`,
        `Collecció`,
        `Data_compra`,
        `Edició`,
        `Editorial`,
        `HEIGHT`,
        `ISBN`,
        `Identificador`,
        `Idioma`,
        `Imatge`,
        `Lloc_compra`,
        `Municipi`,
        `Núm_collecció`,
        `Observacions`,
        `Preu`,
        `Pàgines`,
        `Registres`,
        `Títol`,
        `URL`,
        `Varietat_dialectal`,
        `WIDTH`
    FROM
        `00_FONTS`
    WHERE
        `Identificador` = :id');
    $stmt->execute([':id' => $obra_title]);

    return $stmt->fetchObject(Obra::class);
}

/**
 * Returns the number of paremiotipus for a specific font.
 */
function get_paremiotipus_count_by_font(string $font_id): int
{
    $stmt = get_db()->prepare('SELECT COUNT(1) FROM `00_PAREMIOTIPUS` WHERE `ID_FONT` = :id');
    $stmt->execute([':id' => $font_id]);

    return (int) $stmt->fetchColumn();
}

/**
 * Returns a canonical URL for the paremiotipus.
 */
function get_paremiotipus_url(string $paremiotipus, bool $absolute = false, bool $encode_full_url = false): string
{
    $base_url = '';
    if ($absolute) {
        $base_url = 'https://pccd.dites.cat';
    }

    if ($encode_full_url) {
        return rawurlencode($base_url . '/p/' . name_to_path($paremiotipus, encode: false));
    }

    return $base_url . '/p/' . name_to_path($paremiotipus);
}

/**
 * Returns a canonical URL for the obra.
 */
function get_obra_url(string $obra, bool $absolute = false): string
{
    $url = '';
    if ($absolute) {
        $url = 'https://pccd.dites.cat';
    }

    return $url . '/obra/' . name_to_path($obra);
}

/**
 * Renders and returns the current page content.
 */
function render_main_content(string $page_name): string
{
    ob_start();

    require __DIR__ . "/pages/{$page_name}.php";
    $main_content = ob_get_clean();

    return $main_content !== false ? $main_content : '';
}

/**
 * Returns a URL with some escaped characters if $url is a valid HTTP/HTTPS url, or an empty string otherwise.
 */
function get_clean_url(string $url): string
{
    $url = trim($url);
    if (
        $url !== ''
        && (
            (
                str_starts_with($url, 'http://')
                || str_starts_with($url, 'https://')
            )
            && filter_var($url, \FILTER_SANITIZE_URL) === $url
        )
    ) {
        return str_replace(['&', '[', ']'], ['&amp;', '%5B', '%5D'], $url);
    }

    return '';
}

/**
 * Returns a search pager URL.
 */
function get_pager_url(int $page_number): string
{
    $mostra = get_page_limit();
    if (!isset($_GET['cerca']) || !is_string($_GET['cerca']) || $_GET['cerca'] === '') {
        // Simplify links to the homepage as much as possible.
        if ($page_number === 1) {
            if ($mostra === PAGER_DEFAULT) {
                return '/';
            }

            return '/?mostra=' . $mostra;
        }

        if ($mostra === PAGER_DEFAULT) {
            return '/?pagina=' . $page_number;
        }

        return '/?mostra=' . $mostra . '&amp;pagina=' . $page_number;
    }

    // Build the URL in the same format as it is when the search form is submitted, so the browser/CDN cache can be
    // reused.
    $url = '/?mode=';
    if (isset($_GET['mode']) && is_string($_GET['mode']) && $_GET['mode'] !== '') {
        $url .= htmlspecialchars(urlencode($_GET['mode']));
    }

    $url .= '&amp;cerca=' . htmlspecialchars(urlencode($_GET['cerca']));

    $url .= isset($_GET['variant']) ? '&amp;variant=' : '';
    $url .= isset($_GET['sinonim']) ? '&amp;sinonim=' : '';
    $url .= isset($_GET['equivalent']) ? '&amp;equivalent=' : '';

    $url .= '&amp;mostra=' . $mostra;

    if ($page_number > 1) {
        $url .= '&amp;pagina=' . $page_number;
    }

    return $url;
}

/**
 * Renders a search pagination element.
 */
function render_pager_element(int $page_number, int|string $name, int|string $title = '', bool $is_active = false): string
{
    $rel = '';
    if ($title === 'Pàgina següent') {
        $rel = 'next';
    } elseif ($title === 'Pàgina anterior') {
        $rel = 'prev';
    }

    $pager_item = '<li>';
    if ($is_active) {
        $pager_item .= '<strong title="' . $title . '">' . $name . '</strong>';
    } else {
        $pager_item .= '<a href="' . get_pager_url($page_number) . '" title="' . $title . '"';
        if ($rel !== '') {
            $pager_item .= ' rel="' . $rel . '"';
        }
        $pager_item .= '>' . $name . '</a>';
    }

    return $pager_item . '</li>';
}

/**
 * Returns the search pagination links.
 */
function render_pager(int $page_num, int $num_pages): string
{
    // Previous and first page links.
    $prev_links = '';
    if ($page_num > 1) {
        // Show previous link.
        $prev_links .= render_pager_element(
            $page_num - 1,
            '<svg aria-hidden="true" viewBox="0 0 24 24"><path fill="currentColor" d="M15.535 3.515 7.05 12l8.485 8.485 1.415-1.414L9.878 12l7.072-7.071z"/></svg> Anterior',
            'Pàgina anterior'
        );

        // Show first page link.
        $prev_links .= render_pager_element(1, '1', 'Primera pàgina');
    }

    // Current page item.
    $page_links = render_pager_element($page_num, $page_num, 'Sou a la pàgina ' . $page_num, true);

    // `…` previous link.
    if ($page_num > 2) {
        $prev_prev_page = max(2, $page_num - 5);
        $page_links = render_pager_element(
            $prev_prev_page,
            $prev_prev_page === 2 && $page_num === 3 ? '2' : '…',
            'Pàgina ' . $prev_prev_page
        ) . $page_links;
    }

    // `…` next link.
    if ($page_num < $num_pages - 1) {
        $next_next_page = min($page_num + 5, $num_pages - 1);
        $page_links .= render_pager_element(
            $next_next_page,
            $next_next_page === $num_pages - 1 && $page_num === $num_pages - 2 ? $next_next_page : '…',
            'Pàgina ' . $next_next_page
        );
    }

    // Next and last page links.
    $next_links = '';
    if ($page_num < $num_pages) {
        // Show the last page link.
        $next_links = render_pager_element($num_pages, $num_pages, 'Última pàgina');

        // Show the next link.
        $next_links .= render_pager_element(
            $page_num + 1,
            'Següent <svg aria-hidden="true" viewBox="0 0 24 24"><path fill="currentColor" d="M8.465 20.485 16.95 12 8.465 3.515 7.05 4.929 14.122 12 7.05 19.071z"/></svg>',
            'Pàgina següent'
        );
    }

    return '<nav aria-label="Paginació dels resultats"><ul>' . $prev_links . $page_links . $next_links . '</ul></nav>';
}

/**
 * Returns whether the provided number needs an apostrophe in Catalan.
 */
function number_needs_apostrophe(int $num): bool
{
    // We do not have records bigger or equal than 11M, so this should be fine.
    return $num === 1 || $num === 11 || ($num >= 11000 && $num < 12000);
}

/**
 * Returns the search summary.
 */
function render_search_summary(int $offset, int $results_per_page, int $total, string $search_string): string
{
    if ($total === 1) {
        return 'S\'ha trobat 1 paremiotipus per a la cerca <span class="text-monospace">' . $search_string . '</span>.';
    }

    $output = "S'han trobat " . format_nombre($total) . ' paremiotipus per a la cerca <span class="text-monospace">' . $search_string . '</span>.';

    if ($total > $results_per_page) {
        $first_record = $offset + 1;
        $output .= (number_needs_apostrophe($first_record) ? " Registres de l'" : ' Registres del ') . format_nombre($first_record);

        $last_record = min($offset + $results_per_page, $total);
        $output .= (number_needs_apostrophe($last_record) ? " a l'" : ' al ') . format_nombre($last_record) . '.';
    }

    return $output;
}

/**
 * Formats an integer in Catalan.
 */
function format_nombre(int|string $num): string
{
    return number_format(num: (float) $num, thousands_separator: '.');
}

/**
 * Formats a price in Catalan.
 */
function format_preu(float $num): string
{
    $decimals = $num === floor($num) ? 0 : 2;

    return number_format(
        $num,
        decimals: $decimals,
        decimal_separator: ',',
        thousands_separator: '.'
    );
}

/**
 * Returns an array of languages from the database.
 *
 * From the 00_EQUIVALENTS table, it returns `IDIOMA` values keyed by `CODI`.
 *
 * @return array<string, string>
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
 */
function get_idioma(string $code): string
{
    $code = strtoupper(trim($code));
    if ($code !== '') {
        $languages = get_idiomes();
        if (isset($languages[$code])) {
            return mb_strtolower($languages[$code]);
        }
    }

    return '';
}

/**
 * Tries to return a valid ISO 639-1/639-2 code when given a potentially wrong code coming from the database.
 *
 * Input parameter $code is returned when value is not found in mapping but the format is valid. An empty string is
 * returned if the format of $code is not valid. Value is trimmed and converted to lowercase before performing checks.
 */
function get_idioma_iso_code(string $code): string
{
    $code = strtolower(trim($code));
    if (preg_match('/^[a-z]{2,3}$/', $code) !== 1) {
        return '';
    }

    $wrong_code_map = [
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
        'pr' => 'prv',
        'sa' => 'sc',
        // `si` is the ISO code of Sinhalese, but in the database it is used for Sicilian.
        'si' => 'scn',
    ];

    return $wrong_code_map[$code] ?? $code;
}

/**
 * Gets the current search page number, defaulting to 1.
 */
function get_page_number(): int
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
 * Builds the search query, storing it in $where_clause variable, and returns the search arguments.
 *
 * @return list<string>
 */
function build_search_query(string $search, string $search_mode, string &$where_clause): array
{
    $checkboxes = [
        'equivalent' => '`EQUIVALENT`',
        'sinonim' => '`SINONIM`',
        'variant' => '`MODISME`',
    ];

    $arguments = [$search];
    if ($search_mode === 'whole_sentence' || $search_mode === 'wildcard') {
        $where_clause = " WHERE `PAREMIOTIPUS` REGEXP CONCAT('[[:<:]]', ?, '[[:>:]]')";
    } elseif ($search_mode === 'comença') {
        $where_clause = " WHERE `PAREMIOTIPUS` LIKE CONCAT(?, '%')";
    } elseif ($search_mode === 'acaba') {
        $where_clause = " WHERE `PAREMIOTIPUS` LIKE CONCAT('%', ?)";
    } elseif ($search_mode === 'coincident') {
        $where_clause = ' WHERE `PAREMIOTIPUS` = ?';
    } elseif (isset($_GET['font']) && is_string($_GET['font']) && $_GET['font'] !== '') {
        $arguments = [path_to_name($_GET['font'])];
        $where_clause = ' WHERE `ID_FONT` = ?';
    } else {
        // 'conté' (default) search mode uses full-text.
        $columns = '`PAREMIOTIPUS`';

        foreach ($checkboxes as $checkbox => $column) {
            if (isset($_GET[$checkbox])) {
                $columns .= ", {$column}";
            }
        }

        $where_clause = " WHERE MATCH({$columns}) AGAINST (? IN BOOLEAN MODE)";
    }

    foreach ($checkboxes as $checkbox => $column) {
        if (isset($_GET[$checkbox])) {
            if ($search_mode === 'whole_sentence' || $search_mode === 'wildcard') {
                $where_clause .= " OR {$column} REGEXP CONCAT('[[:<:]]', ?, '[[:>:]]')";
                $arguments[] = $search;
            } elseif ($search_mode === 'comença') {
                $where_clause .= " OR {$column} LIKE CONCAT(?, '%')";
                $arguments[] = $search;
            } elseif ($search_mode === 'acaba') {
                $where_clause .= " OR {$column} LIKE CONCAT('%', ?)";
                $arguments[] = $search;
            } elseif ($search_mode === 'coincident') {
                $where_clause .= " OR {$column} = ?";
                $arguments[] = $search;
            }
        }
    }

    return $arguments;
}

/**
 * Returns the number of search results.
 *
 * @param list<string> $arguments
 *
 * @throws Exception If the query fails
 */
function get_n_results(string $where_clause, array $arguments): int
{
    // Create a unique cache key based on the query and arguments
    $cache_key = $where_clause . ' ' . implode('|', $arguments);

    return cache_get($cache_key, static function () use ($where_clause, $arguments): int {
        try {
            $stmt = get_db()->prepare("SELECT COUNT(DISTINCT `PAREMIOTIPUS`) FROM `00_PAREMIOTIPUS` {$where_clause}");
            $stmt->execute($arguments);

            return (int) $stmt->fetchColumn();
        } catch (Exception $exception) {
            error_log('Error in get_n_results: ' . $exception->getMessage());

            return 0;
        }
    });
}

/**
 * Returns the paremiotipus search results.
 *
 * @param list<string> $arguments
 *
 * @return list<string>
 */
function get_paremiotipus_search_results(string $where_clause, array $arguments, int $limit, int $offset): array
{
    $stmt = get_db()->prepare("SELECT DISTINCT
            `PAREMIOTIPUS`
        FROM
            `00_PAREMIOTIPUS`
        {$where_clause}
        ORDER BY
            `PAREMIOTIPUS`
        LIMIT {$limit}
        OFFSET {$offset}");
    $stmt->execute($arguments);

    /** @var list<string> */
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Remove special characters from a string, especially for matching paremiotipus.
 *
 * @param string $search_mode The search mode to normalize for. If provided, the string is processed for search.
 */
function normalize_search(string $string, string $search_mode = ''): string
{
    if ($string !== '') {
        // Remove useless characters in search that may affect syntax, or that are not useful.
        $string = str_replace(
            ['"', '+', '.', '%', '--', '_', '(', ')', '[', ']', '{', '}', '^', '>', '<', '~', '@', '$', '|', '/', '\\'],
            '',
            $string
        );

        // Standardize simple quotes.
        $string = str_replace('’', "'", $string);

        // Remove double spaces.
        $string = preg_replace('/\s+/', ' ', $string);
        assert(is_string($string));
        if ($string !== '') {
            // Fix characters for search.
            if ($search_mode === 'whole_sentence') {
                // Remove wildcards and unnecessary characters.
                $string = str_replace(['*', '?'], '', $string);
            } elseif ($search_mode === 'wildcard') {
                // Replace wildcard characters.
                $string = str_replace(['*', '?'], ['.*', '.'], $string);
            } elseif ($search_mode === 'conté') {
                // Remove characters that may affect FULL-TEXT search syntax.
                $string = str_replace(['*', '?'], '', $string);

                // Remove loose `-` operator.
                /** @noinspection CascadeStringReplacementInspection */
                $string = str_replace(' - ', ' ', $string);

                // Nice to have: remove extra useless characters (not `-`).
                /** @noinspection CascadeStringReplacementInspection */
                $string = str_replace(
                    ['“', '”', '«', '»', '…', ',', ':', ';', '!', '¡', '¿', '–', '—', '―', '─'],
                    '',
                    $string
                );

                // Build the full-text query.
                $words = preg_split('/\s+/', $string);
                $string = '';

                /** @var non-empty-list<string> $words */
                foreach ($words as $word) {
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

            return trim($string);
        }
    }

    return '';
}

/**
 * Returns array of 00_EDITORIA `NOM` values keyed by `CODI`.
 *
 * @return array<string, string>
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
 * @return array<string, string>
 */
function get_fonts(): array
{
    return cache_get('fonts', static function (): array {
        $stmt = get_db()->query('SELECT `Identificador`, `Títol` FROM `00_FONTS`');

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    });
}

/**
 * Generates HTML markup for an image, optionally within a <picture> tag for optimized formats.
 *
 * @param string $file_name The file name of the image file.
 * @param string $path The path to the image file, starting with a slash.
 * @param string $alt_text (optional) The alternative text for the image. Defaults to an empty string.
 * @param bool $escape_html (optional) Whether to escape the alternative text. Defaults to true.
 * @param int $width (optional) The width attribute for the <img> tag. Defaults to 0 (not set).
 * @param int $height (optional) The height attribute for the <img> tag. Defaults to 0 (not set).
 * @param bool $lazy_loading (optional) If true, adds 'loading="lazy"' to the <img> tag. Defaults to true.
 * @param bool $preload (optional) If true, adds a preload HTTP header for the image. Defaults to false.
 * @param string $preload_media (optional) Adds the media rule to the preloaded image. Defaults to empty string (no media rule).
 *
 * @return string The generated HTML markup for the image.
 */
function get_image_tags(
    string $file_name,
    string $path,
    string $alt_text = '',
    bool $escape_html = true,
    int $width = 0,
    int $height = 0,
    bool $lazy_loading = true,
    bool $preload = false,
    string $preload_media = ''
): string {
    $optimized_file_url = '';
    $file_url = $path . rawurlencode($file_name);
    // Image files may have been provided in WEBP/AVIF format already.
    if (!str_ends_with($file_name, '.webp') && !str_ends_with($file_name, '.avif')) {
        // We currently provide AVIF as an alternative for JPEG/PNG images, and WEBP for GIF.
        $avif_file = str_ireplace(['.jpg', '.png'], '.avif', $file_name);
        $avif_exists = str_ends_with($avif_file, '.avif') && is_file(__DIR__ . "/../docroot{$path}{$avif_file}");
        if ($avif_exists) {
            $optimized_file_url = $path . rawurlencode($avif_file);
        } else {
            $webp_file = str_ireplace('.gif', '.webp', $file_name);
            $webp_exists = str_ends_with($webp_file, '.webp') && is_file(__DIR__ . "/../docroot{$path}{$webp_file}");
            if ($webp_exists) {
                $optimized_file_url = $path . rawurlencode($webp_file);
            }
        }
    }
    $preload_url = $optimized_file_url !== '' ? $optimized_file_url : $file_url;
    $extension = strtolower(pathinfo($preload_url, PATHINFO_EXTENSION));
    $mime_types = [
        'avif' => 'image/avif',
        'gif' => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];
    $mime_type = $mime_types[$extension] ?? '';

    // Generate the HTML markup for the image.
    $image_tags = '';
    if ($optimized_file_url !== '') {
        $image_tags .= '<picture>';
        $image_tags .= '<source srcset="' . $optimized_file_url . '"';
        if ($mime_type !== '') {
            $image_tags .= ' type="' . $mime_type . '"';
        }
        $image_tags .= '>';
    }
    $image_tags .= '<img alt="' . ($escape_html ? htmlspecialchars($alt_text) : $alt_text) . '"';
    if ($lazy_loading) {
        $image_tags .= ' loading="lazy"';
    }
    if ($width > 0 && $height > 0) {
        $image_tags .= ' width="' . $width . '" height="' . $height . '"';
    }
    $image_tags .= ' src="' . $file_url . '">';
    if ($optimized_file_url !== '') {
        $image_tags .= '</picture>';
    }

    if ($preload) {
        preload_image_header(url: $preload_url, media: $preload_media, type: $mime_type);
    }

    return $image_tags;
}

/**
 * Adds an HTTP header to preload an image.
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

    // The goal here is to flush the headers early, allowing the browser to start
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
 * Returns the total number of occurrences (modismes).
 */
function get_n_modismes(): int
{
    return cache_get('n_modismes', static function (): int {
        $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS`');

        return (int) $stmt->fetchColumn();
    });
}

/**
 * Returns the total number of distinct paremiotipus.
 */
function get_n_paremiotipus(): int
{
    return cache_get('n_paremiotipus', static function (): int {
        $stmt = get_db()->query('SELECT COUNT(1) FROM `paremiotipus_display`');

        return (int) $stmt->fetchColumn();
    });
}

/**
 * Returns the total number of individual authors (informants).
 */
function get_n_informants(): int
{
    return cache_get('n_informants', static function (): int {
        $stmt = get_db()->query('SELECT COUNT(DISTINCT `AUTOR`) FROM `00_PAREMIOTIPUS`');

        return (int) $stmt->fetchColumn();
    });
}

/**
 * Returns the total number of sources (fonts).
 */
function get_n_fonts(): int
{
    return cache_get('n_fonts', static function (): int {
        $stmt = get_db()->query('SELECT COUNT(1) FROM `00_FONTS`');

        return (int) $stmt->fetchColumn();
    });
}

/**
 * Returns a random paremiotipus from top 10000.
 */
function get_random_top_paremiotipus(int $max = 10000): string
{
    $random_index = mt_rand(0, $max - 1);

    return cache_get("paremiotipus_{$random_index}", static function () use ($random_index): string {
        $stmt = get_db()->query("SELECT `Paremiotipus` FROM `common_paremiotipus` LIMIT 1 OFFSET {$random_index}");

        $random = $stmt->fetchColumn();
        assert(is_string($random));

        return $random;
    });
}

/**
 * Returns a random book by Víctor Pàmies.
 */
function get_random_book(): Book
{
    $books = cache_get('obresvpr', static function (): array {
        $stmt = get_db()->query('SELECT `Imatge`, `Títol`, `URL`, `WIDTH`, `HEIGHT` FROM `00_OBRESVPR`');

        return $stmt->fetchAll(PDO::FETCH_CLASS, Book::class);
    });

    return $books[array_rand($books)];
}
