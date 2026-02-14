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

const MINIMUM_EXPLANATION_LENGTH = 5;

final readonly class ParemiotipusVariant
{
    private string $PAREMIOTIPUS;

    private string $AUTOR;

    private string $AUTORIA;

    private string $DIARI;

    private string $ARTICLE;

    private string $EDITORIAL;

    private string $ANY;

    private string $PAGINA;

    private string $LLOC;

    private string $EXPLICACIO;

    private string $EXPLICACIO2;

    private string $EXEMPLES;

    private string $SINONIM;

    private string $EQUIVALENT;

    private string $IDIOMA;

    private string $FONT;

    private string $ACCEPCIO;

    private string $ID_FONT;

    /**
     * Gets the paremiotipus ID.
     */
    public function getId(): string
    {
        return $this->PAREMIOTIPUS;
    }

    /**
     * Gets the year as an integer, or 0 if empty.
     */
    public function getYear(): int
    {
        if ($this->ANY !== '' && $this->ANY !== '0') {
            return (int) $this->ANY;
        }

        return 0;
    }

    /**
     * Gets the source id, for counting purposes.
     */
    public function getSourceId(): string
    {
        return $this->ID_FONT . $this->AUTOR . $this->DIARI;
    }

    /**
     * Renders a formatted citation.
     *
     * @param array<string, string> $editorials Array of editorial codes to names.
     * @param array<string, string> $fonts      Array of font IDs to names.
     */
    public function renderCitation(array $editorials, array $fonts): string
    {
        $citation = htmlspecialchars($this->AUTOR);

        $year = $this->getYear();
        if ($year !== 0) {
            $citation .= " ({$year})";
        }

        // Add colon separator after author/year, if we have additional content.
        if ($this->DIARI !== '' || $this->ARTICLE !== '') {
            $citation .= ':';
        }

        $editorial_name = $editorials[$this->EDITORIAL] ?? $this->EDITORIAL;

        // Add journal/diary name if different from editorial.
        if ($this->DIARI !== '' && $this->DIARI !== $editorial_name) {
            $diari = '<i>' . htmlspecialchars($this->DIARI) . '</i>';
            if ($this->ID_FONT !== '' && isset($fonts[$this->ID_FONT])) {
                $diari = '<a href="' . get_obra_url($this->ID_FONT) . '">' . $diari . '</a>';
            }
            $citation .= " {$diari}";
        }

        if ($this->ARTICLE !== '') {
            $citation .= ' «' . html_escape_and_link_urls($this->ARTICLE) . '»';
        }

        if ($this->PAGINA !== '') {
            $citation .= ', p. ' . htmlspecialchars($this->PAGINA);
        }

        if ($editorial_name !== '') {
            $citation .= '. ' . htmlspecialchars($editorial_name);
        }

        if ($this->ACCEPCIO !== '') {
            $citation .= ', accepció ' . htmlspecialchars($this->ACCEPCIO);
        }

        // Remove potentially introduced leading spaces and punctuation.
        $citation = ltrim($citation, ' :,.');

        if ($citation === '') {
            return '';
        }

        // End citation with a dot.
        $citation = rtrim($citation, '.') . '.';

        return '<div class="footer">' . $citation . '</div>';
    }

    /**
     * Returns the rendered body content of a paremiotipus entry, including explanation, examples, synonyms, etc.
     */
    public function renderBody(): string
    {
        $body_html = '';

        // Build explanation from combined explanation fields.
        $explanation = mb_ucfirst(prepare_field($this->EXPLICACIO . $this->EXPLICACIO2));

        // Do not print very short explanations as they are likely not meaningful.
        if (strlen($explanation) < MINIMUM_EXPLANATION_LENGTH) {
            $explanation = '';
        }

        // Add authorship information to explanation.
        if ($this->AUTORIA !== '') {
            $explanation .= ' De: ' . prepare_field($this->AUTORIA);
            $explanation = ltrim($explanation);
        }

        if ($explanation !== '') {
            $body_html .= "<div>{$explanation}</div>";
        }

        if ($this->EXEMPLES !== '') {
            $body_html .= '<div><i>' . mb_ucfirst(prepare_field($this->EXEMPLES)) . '</i></div>';
        }

        if ($this->SINONIM !== '') {
            $body_html .= '<div>Sinònim: ' . prepare_field($this->SINONIM) . '</div>';
        }

        if ($this->EQUIVALENT !== '') {
            $equivalent = prepare_field($this->EQUIVALENT);
            $idioma = get_idioma($this->IDIOMA);

            if ($idioma === '') {
                $body_html .= "<div>Equivalent: {$equivalent}</div>";
            } else {
                $iso_code = get_idioma_iso_code($this->IDIOMA);
                if ($iso_code !== '') {
                    $equivalent = "<span lang=\"{$iso_code}\">{$equivalent}</span>";
                }
                $body_html .= "<div>Equivalent en {$idioma}: {$equivalent}</div>";
            }
        }

        if ($this->LLOC !== '') {
            $body_html .= '<div>Lloc: ' . prepare_field($this->LLOC) . '</div>';
        }

        // Add source section (unless it is too short).
        if (strlen($this->FONT) > 1) {
            $body_html .= '<div>Font: ' . prepare_field($this->FONT) . '</div>';
        }

        return $body_html;
    }
}

final readonly class ParemiotipusImage
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

/**
 * Returns a URL with some encoded characters if $input_url is a valid HTTP/HTTPS url, or an empty string otherwise.
 *
 * The input_url parameter specifies the URL to clean and validate.
 */
function get_clean_url(string $input_url): string
{
    $url = mb_trim($input_url);

    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        return '';
    }

    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return '';
    }

    // Encode a few characters.
    return str_replace(['&', '[', ']'], ['&amp;', '%5B', '%5D'], $url);
}

/**
 * Gets an array of unique variants, keyed by MODISME.
 *
 * The paremiotipus_id parameter specifies the paremiotipus ID to search for variants.
 *
 * @return array<string, non-empty-list<ParemiotipusVariant>> Array of variants grouped by MODISME.
 */
function get_recurrences_by_variant(string $paremiotipus_id): array
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
    $stmt->execute([':paremiotipus' => $paremiotipus_id]);

    return $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_CLASS, ParemiotipusVariant::class);
}

/**
 * Tries to get a paremiotipus from a modisme.
 *
 * The modisme parameter specifies the modisme to search for.
 */
function get_paremiotipus_by_modisme(string $modisme): string
{
    $stmt = get_db()->prepare('SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `MODISME` = :modisme LIMIT 1');
    $stmt->execute([':modisme' => $modisme]);

    $paremiotipus = $stmt->fetchColumn();

    return is_string($paremiotipus) ? $paremiotipus : '';
}

/**
 * Gets a list of ParemiotipusImage objects for a specific paremiotipus.
 *
 * The paremiotipus_id parameter specifies the paremiotipus ID to get images for.
 *
 * @return list<ParemiotipusImage> List of ParemiotipusImage objects.
 */
function get_paremiotipus_images(string $paremiotipus_id): array
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
    $stmt->execute([':paremiotipus' => $paremiotipus_id]);

    /** @var list<ParemiotipusImage> */
    return $stmt->fetchAll(PDO::FETCH_CLASS, ParemiotipusImage::class);
}

/**
 * Gets a list of Common Voice mp3 files for a specific paremiotipus.
 *
 * The paremiotipus_id parameter specifies the paremiotipus ID to get mp3 files for.
 *
 * @return list<string> List of mp3 file names.
 */
function get_cv_files(string $paremiotipus_id): array
{
    $stmt = get_db()->prepare('SELECT `file` FROM `commonvoice` WHERE `paremiotipus` = :paremiotipus');
    $stmt->execute([':paremiotipus' => $paremiotipus_id]);

    /** @var list<string> */
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Tries to get the best match of paremiotipus by searching a modisme.
 *
 * The input_modisme parameter specifies the paremiotipus/modisme to search.
 */
function get_paremiotipus_best_match(string $input_modisme): string
{
    // We do not want to avoid words here.
    $modisme = trim($input_modisme, '-');
    $modisme = str_replace(' -', ' ', $modisme);
    $modisme = trim($modisme);

    $paremiotipus = false;
    $modisme = normalize_search($modisme, SearchMode::CONTAINS);
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
            error_log("Error: modisme '{$modisme}' not found: " . $e->getMessage());

            return '';
        }

        $paremiotipus = $stmt->fetchColumn();
    }

    return is_string($paremiotipus) ? $paremiotipus : '';
}

/**
 * Try to redirect to a valid paremiotipus page and exit via HTTP 301 header.
 *
 * The input_paremiotipus parameter specifies the paremiotipus to validate and redirect if possible.
 */
function try_to_redirect_to_valid_paremiotipus_and_exit(string $input_paremiotipus): void
{
    $paremiotipus = trim($input_paremiotipus);

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
 * Generates the HTML block for Common Voice and sets og:audio meta tag if at least one file exists.
 *
 * The paremiotipus_id parameter specifies the paremiotipus ID to get Common Voice files for.
 */
function render_common_voice_and_set_og_tags(string $paremiotipus_id): string
{
    $mp3_files = get_cv_files($paremiotipus_id);

    $commonvoice_content_html = '';
    foreach ($mp3_files as $mp3_file) {
        // Set OG audio file. Only the last one set will be used if it overwrites.
        PageRenderer::setOgAudioUrl("https://pccd.dites.cat/mp3/{$mp3_file}");

        $commonvoice_content_html .= '<a class="audio" href="/mp3/' . $mp3_file . '" role="button">';
        $commonvoice_content_html .= '<audio preload="none" src="/mp3/' . $mp3_file . '"></audio>';
        // SVG for play button (same as original)
        $commonvoice_content_html .= '<svg width="32" height="27" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 18" aria-label="Reprodueix" role="img"><g fill="#3c9ae3"><path d="M3.153 6.5H0v4.999h3.153l4.108 3.38s.863.757.863.051V3.001c0-.559-.759.044-.759.044zm10.897 9.841c2.195-1.833 3.453-4.508 3.453-7.34S16.245 3.494 14.05 1.66a.69.69 0 0 0-.971.085.68.68 0 0 0 .084.963A8.18 8.18 0 0 1 16.126 9a8.2 8.2 0 0 1-2.961 6.291.676.676 0 0 0-.086.962c.244.293.68.329.971.088"/><path d="M14.74 9c0-2.02-.896-3.935-2.463-5.243a.69.69 0 0 0-.971.083.68.68 0 0 0 .084.965 5.46 5.46 0 0 1 1.975 4.194 5.46 5.46 0 0 1-1.975 4.194.68.68 0 0 0-.084.963.69.69 0 0 0 .971.082A6.8 6.8 0 0 0 14.74 9"/><path d="M11.984 9a4.09 4.09 0 0 0-1.479-3.147.69.69 0 0 0-1.13.524c0 .195.085.39.246.525a2.72 2.72 0 0 1 0 4.195.68.68 0 0 0-.084.962.69.69 0 0 0 .969.084A4.08 4.08 0 0 0 11.984 9"/></g></svg>';
        $commonvoice_content_html .= '</a>';
    }

    if ($commonvoice_content_html === '') {
        // No Common Voice content.
        return '';
    }

    $block_html = '<div id="commonvoice" class="bloc text-balance text-break" title="Reprodueix un enregistrament">';
    $block_html .= $commonvoice_content_html;
    $block_html .= '<p><a href="https://commonvoice.mozilla.org/ca">';
    $block_html .= '<img title="Projecte Common Voice" alt="Logo Common Voice" width="100" height="25" src="/img/logo-commonvoice.svg"></a></p>';
    $block_html .= '</div>';

    return $block_html;
}

/**
 * Generates the HTML block for images and sets og:image meta tag if at least one image exists.
 *
 * The paremiotipus_id parameter specifies the paremiotipus ID to get images for.
 * The alt_text parameter specifies the alt text for the images.
 */
function render_paremiotipus_images_and_set_og_tags(string $paremiotipus_id, string $alt_text): string
{
    $images = get_paremiotipus_images($paremiotipus_id);

    $images_content_html = '';
    foreach ($images as $image) {
        $is_first_processed_image = $images_content_html === '';
        if ($is_first_processed_image) {
            // Use the newest image as meta image.
            PageRenderer::setMetaImage('https://pccd.dites.cat/img/imatges/' . rawurlencode($image->Identificador));
        }

        $image_tag = render_image_tags(
            file_name: $image->Identificador,
            path: '/img/imatges/',
            alt_text: $alt_text,
            escape_html: false,
            width: $image->WIDTH,
            height: $image->HEIGHT,
            preload: $is_first_processed_image,
            preload_media: '(min-width: ' . BREAKPOINT_LG . 'px)'
        );

        $image_link_url = get_clean_url($image->URL_ENLLAÇ);
        if ($image_link_url !== '') {
            $image_tag = '<a href="' . $image_link_url . '">' . $image_tag . '</a>';
        }

        $image_caption = render_image_caption(
            autor: $image->AUTOR,
            any: $image->ANY,
            diari: $image->DIARI,
            article: $image->ARTICLE,
            link: $image_link_url
        );

        $images_content_html .= '<div class="bloc bloc-image text-break">';
        $images_content_html .= '<figure>';
        $images_content_html .= $image_tag;
        $images_content_html .= $image_caption;
        $images_content_html .= '</figure>';
        $images_content_html .= '</div>';
    }

    if ($images_content_html === '') {
        // If there are no images, generate an OG image with text.
        PageRenderer::setMetaImage('https://pccd.dites.cat/og/' . name_to_slug($paremiotipus_id) . '.png');

        return '';
    }

    return '<div id="imatges">' . $images_content_html . '</div>';
}

/**
 * Generates the HTML for an image caption, if there is input.
 *
 * The autor parameter specifies the author of the image.
 * The any parameter specifies the year of the image.
 * The diari parameter specifies the diary or publication.
 * The article parameter specifies the article title.
 * The link parameter specifies the link to the article or image.
 */
function render_image_caption(string $autor, string $any, string $diari, string $article, string $link): string
{
    $image_caption = htmlspecialchars($autor);

    if ($any !== '' && $any !== '0') {
        $image_caption .= ' (' . $any . ')';
    }

    if ($diari !== '' && $diari !== $autor) {
        $diari_html = htmlspecialchars($diari);
        if ($link !== '' && $article === '') {
            $diari_html = '<a href="' . $link . '" class="external" target="_blank" rel="noopener">' . $diari_html . '</a>';
        }
        $image_caption .= ": <em>{$diari_html}</em>";
    }

    if ($article !== '') {
        if (str_contains($article, 'http')) {
            $article_html = html_escape_and_link_urls($article);
        } else {
            $article_html = htmlspecialchars($article);
            if ($link !== '') {
                $article_html = '<a href="' . $link . '" class="external" target="_blank" rel="noopener">' . $article_html . '</a>';
            }
        }
        $image_caption .= " «{$article_html}»";
    }

    // Remove potentially introduced leading spaces and punctuation.
    $image_caption = ltrim($image_caption, ' :');

    if ($image_caption === '') {
        return '';
    }

    return '<figcaption class="small">' . $image_caption . '</figcaption>';
}

/**
 * Gets the language name of the translation from the database table.
 *
 * The key parameter specifies the column key to extract the language name from.
 */
function get_language_name_from_column(string $key): string
{
    return mb_strtolower(str_replace('EQ_', '', $key));
}

/**
 * Gets the multilingüe translations, if they exist.
 *
 * The paremiotipus_id parameter specifies the paremiotipus ID to get translations for.
 *
 * @return array<string, non-empty-string> Array of translations keyed by language.
 */
function get_paremiotipus_translations(string $paremiotipus_id): array
{
    $stmt = get_db()->prepare('SELECT * FROM `RML` WHERE `PAREMIOTIPUS` = :paremiotipus');
    $stmt->execute([':paremiotipus' => $paremiotipus_id]);

    $translations = [];
    $records = $stmt->fetchAll();
    foreach ($records as $translation) {
        foreach ($translation as $key => $value) {
            assert(is_string($key));
            assert(is_string($value));
            if (str_starts_with($key, 'EQ_') && $value !== '') {
                $translations[get_language_name_from_column($key)] = $value;
            }
        }
    }

    return $translations;
}

/**
 * Returns rendered multilingüe translations, or an empty string if they do not exist.
 *
 * The paremiotipus_id parameter specifies the paremiotipus ID to render translations for.
 */
function render_paremiotipus_translations(string $paremiotipus_id): string
{
    $translations = get_paremiotipus_translations($paremiotipus_id);
    // Sort translations by language name.
    // FIXME: ideally, use Collator for handling UTF-8.
    ksort($translations);

    $translations_array = [];
    foreach ($translations as $language => $translation) {
        $iso_code = get_idioma_iso_code_from_name($language);
        if ($iso_code !== '') {
            $translation = '<span lang="' . $iso_code . '">' . $translation . '</span>';
        }
        $translations_array[] = mb_ucfirst($language) . ': ' . $translation;
    }

    return implode('<br>', $translations_array);
}

/**
 * Returns the rendered multilingüe toggle button HTML code.
 */
function render_paremiotipus_translations_button(): string
{
    return '<button hidden type="button" id="toggle-translations" title="Mostra el refranyer multilingüe"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" viewBox="0 0 24 24"><path fill="currentColor" d="m12.9 15-2.6-2.4A17.5 17.5 0 0 0 14.2 6H17V4h-7V2H8v2H1v2h11.2A15 15 0 0 1 9 11.3c-1-1-1.7-2.1-2.3-3.3h-2c.7 1.6 1.7 3.2 3 4.6l-5.1 5L4 19l5-5 3.1 3.1zm5.6-5h-2L12 22h2l1.1-3H20l1.1 3h2zm-2.6 7 1.6-4.3 1.6 4.3z"/></svg></button>';
}

/**
 * Returns the rendered share icons HTML code.
 *
 * The share_url parameter specifies the URL to share.
 * The title parameter specifies the title to use in sharing.
 */
function render_share_icons(string $share_url, string $title): string
{
    $output = '<div class="share-wrapper">';
    $output .= '<button type="button" id="share">Comparteix <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17 22q-1.3 0-2.1-.9T14 19v-.7l-7-4.1q-.4.4-.9.6T5 15q-1.3 0-2.1-.9T2 12t.9-2.1T5 9q.6 0 1.1.2t1 .6l7-4.1v-.3L14 5q0-1.3.9-2.1T17 2t2.1.9T20 5t-.9 2.1T17 8q-.6 0-1.1-.2t-1-.6l-7 4.1v.3l.1.4q.1.3 0 .4t0 .3l7 4.1q.4-.4.9-.6T17 16q1.3 0 2.1.9T20 19t-.9 2.1-2.1.9"/></svg></button>';
    $output .= '<div class="share-icons" hidden>';
    $output .= '<span class="close" role="button" tabindex="0" aria-label="Tanca"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6.4 19 5 17.6l5.6-5.6L5 6.4 6.4 5l5.6 5.6L17.6 5 19 6.4 13.4 12l5.6 5.6-1.4 1.4-5.6-5.6z"/></svg></span>';
    $output .= '<a class="share-icon facebook" href="https://www.facebook.com/sharer/sharer.php?u=' . $share_url . '" target="_blank" rel="noopener"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95"/></svg></span><span class="share-title">Facebook</span></a>';
    $output .= '<a class="share-icon twitter" href="https://x.com/intent/post?url=' . $share_url . '" target="_blank" rel="noopener"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.2 4.2 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.52 8.52 0 0 1-5.33 1.84q-.51 0-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23"/></svg></span><span class="share-title">Twitter</span></a>';
    $output .= '<a class="share-icon whatsapp" href="https://api.whatsapp.com/send?text=' . $share_url . '" target="_blank" rel="noopener"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M19.05 4.91A9.82 9.82 0 0 0 12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01m-7.01 15.24c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.26 8.26 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24 2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 0 1 2.41 5.83c.02 4.54-3.68 8.23-8.22 8.23m4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.17.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43s.17-.25.25-.41c.08-.17.04-.31-.02-.43s-.56-1.34-.76-1.84c-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07s.89 2.4 1.01 2.56c.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18s-.22-.16-.47-.28"/></svg></span><span class="share-title">WhatsApp</span></a>';
    $output .= '<a class="share-icon telegram" href="https://t.me/share/?url=' . $share_url . '" target="_blank" rel="noopener"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2m4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38"/></svg></span><span class="share-title">Telegram</span></a>';
    $output .= '<a class="share-icon email" href="mailto:?subject=' . rawurlencode($title) . '&amp;body=' . $share_url . '"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M29 9v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9m26 0a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2m26 0-11.862 8.212a2 2 0 0 1-2.276 0L3 9"/></svg></span><span class="share-title">Correu</span></a>';
    $output .= '<a class="share-icon copy" href="#"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M18.327 7.286h-8.044a1.93 1.93 0 0 0-1.925 1.938v10.088c0 1.07.862 1.938 1.925 1.938h8.044a1.93 1.93 0 0 0 1.925-1.938V9.224c0-1.07-.862-1.938-1.925-1.938"/><path d="M15.642 7.286V4.688c0-.514-.203-1.007-.564-1.37a1.92 1.92 0 0 0-1.361-.568H5.673c-.51 0-1 .204-1.36.568a1.95 1.95 0 0 0-.565 1.37v10.088c0 .514.203 1.007.564 1.37s.85.568 1.361.568h2.685"/></g></svg></span><span class="share-title">Enllaç</span></a>';
    $output .= '</div></div>';

    return $output;
}
