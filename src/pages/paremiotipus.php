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

require __DIR__ . '/paremiotipus_functions.php';

const YEAR_MAX = PHP_INT_MAX;

$paremiotipus_input = is_string($_GET['paremiotipus']) ? slug_to_name($_GET['paremiotipus']) : '';
$modismes = get_modismes_by_variant($paremiotipus_input);
$modisme_count = count($modismes);

if ($modisme_count === 0) {
    // Try to redirect (HTTP 301) to a valid paremiotipus page.
    try_to_redirect_to_valid_paremiotipus_and_exit($paremiotipus_input);

    // If no match could be found, return an HTTP 404 page.
    return_404_and_exit(input_paremiotipus: get_paremiotipus_best_match($paremiotipus_input));
}

$paremiotipus_id = current($modismes)[0]->PAREMIOTIPUS;
$canonical_url = get_paremiotipus_url($paremiotipus_id, absolute: true);

if (!str_starts_with(get_request_uri(), '/p/')) {
    // Redirect old URLs to the new ones.
    header("Location: {$canonical_url}", response_code: 301);

    exit;
}

$paremiotipus_display = get_paremiotipus_display($paremiotipus_id);
PageRenderer::setTitle($paremiotipus_display);
PageRenderer::setCanonicalUrl($canonical_url);

$blocks_html = render_common_voice_and_set_og_tags($paremiotipus_id);
$blocks_html .= render_paremiotipus_images_and_set_og_tags($paremiotipus_id, alt_text: $paremiotipus_display);
PageRenderer::setParemiotipusBlocks($blocks_html);

$editorials = get_editorials();
$fonts = get_fonts_paremiotipus();
$total_min_year = YEAR_MAX;
$recurrences_count = 0;
$rendered_variants = [];

// Process each variant of the paremiotipus.
foreach ($modismes as $modisme => $recurrences) {
    $min_year_per_variant = YEAR_MAX;
    $prev_source_id = '';
    $variant_sources_count = 0;
    $current_variant_html = '';
    $recurrences_count += count($recurrences);

    // Process each recurrence within this variant.
    foreach ($recurrences as $recurrence) {
        $citation = '';
        if ($recurrence->AUTOR !== '') {
            $citation = htmlspecialchars($recurrence->AUTOR);
        }

        // Add year to citation and track minimum year.
        if ($recurrence->ANY !== '' && $recurrence->ANY !== '0') {
            $year = (int) $recurrence->ANY;
            if ($citation !== '') {
                $citation .= ' ';
            }
            $citation .= "({$year})";
            if ($year < $min_year_per_variant) {
                $min_year_per_variant = $year;
            }
        }

        if ($citation !== '' && ($recurrence->DIARI !== '' || $recurrence->ARTICLE !== '')) {
            $citation .= ':';
        }

        // Get editorial name for later use.
        $editorial_name = '';
        if ($recurrence->EDITORIAL !== '') {
            $editorial_name = $editorials[$recurrence->EDITORIAL] ?? $recurrence->EDITORIAL;
        }

        // Add journal/diary name if different from editorial.
        if ($recurrence->DIARI !== '' && $recurrence->DIARI !== $editorial_name) {
            if ($citation !== '') {
                $citation .= ' ';
            }
            $diari = '<i>' . htmlspecialchars($recurrence->DIARI) . '</i>';
            if ($recurrence->ID_FONT !== '' && isset($fonts[$recurrence->ID_FONT])) {
                $diari = '<a href="' . get_obra_url($recurrence->ID_FONT) . '">' . $diari . '</a>';
            }
            $citation .= $diari;
        }

        if ($recurrence->ARTICLE !== '') {
            if ($citation !== '') {
                $citation .= ' ';
            }
            $citation .= '«' . html_escape_and_link_urls($recurrence->ARTICLE) . '»';
        }

        if ($recurrence->PAGINA !== '') {
            $citation .= ', p. ' . htmlspecialchars($recurrence->PAGINA);
        }

        if ($editorial_name !== '') {
            if ($citation !== '') {
                $citation .= '. ';
            }
            $citation .= htmlspecialchars($editorial_name);
        }

        if ($citation !== '') {
            if ($recurrence->ACCEPCIO !== '') {
                $citation .= ', accepció ' . htmlspecialchars($recurrence->ACCEPCIO);
            }
            if (!str_ends_with($citation, '.')) {
                $citation .= '.';
            }

            $explanation = '';
            if ($recurrence->EXPLICACIO !== '' && $recurrence->EXPLICACIO2 !== '') {
                $explanation = mb_ucfirst(prepare_text_for_html($recurrence->EXPLICACIO . $recurrence->EXPLICACIO2));
            } elseif (strlen($recurrence->EXPLICACIO) > 3) {
                $explanation = mb_ucfirst(prepare_text_for_html($recurrence->EXPLICACIO));
            }
            if ($recurrence->AUTORIA !== '') {
                if ($explanation !== '') {
                    $explanation .= ' ';
                }
                $explanation .= 'De: ' . prepare_text_for_html($recurrence->AUTORIA);
            }

            $body_html = '';
            if ($explanation !== '') {
                $body_html .= "<div>{$explanation}</div>";
            }
            if ($recurrence->EXEMPLES !== '') {
                $body_html .= '<div><i>' . mb_ucfirst(prepare_text_for_html($recurrence->EXEMPLES)) . '</i></div>';
            }
            if ($recurrence->SINONIM !== '') {
                $body_html .= '<div>Sinònim: ' . prepare_text_for_html($recurrence->SINONIM) . '</div>';
            }
            if ($recurrence->EQUIVALENT !== '') {
                $equivalent = prepare_text_for_html($recurrence->EQUIVALENT);
                $idioma = get_idioma($recurrence->IDIOMA);
                if ($idioma !== '') {
                    $iso_code = get_idioma_iso_code($recurrence->IDIOMA);
                    if ($iso_code !== '') {
                        $equivalent = "<span lang=\"{$iso_code}\">{$equivalent}</span>";
                    }
                    $body_html .= "<div>Equivalent en {$idioma}: {$equivalent}</div>";
                } else {
                    $body_html .= "<div>Equivalent: {$equivalent}</div>";
                }
            }
            if ($recurrence->LLOC !== '') {
                $body_html .= '<div>Lloc: ' . prepare_text_for_html($recurrence->LLOC) . '</div>';
            }
            if (strlen($recurrence->FONT) > 1) {
                $body_html .= '<div>Font: ' . prepare_text_for_html($recurrence->FONT) . '</div>';
            }

            // Skip entries that only contain a year (no substantial content).
            if ($body_html === '' && preg_match('/\(\d{4}\).$/', $citation) === 1) {
                $citation = '';
            }

            // Add entry to variant HTML if we have content.
            if ($body_html !== '' || $citation !== '') {
                $current_variant_html .= '<div class="entry">';
                if ($body_html !== '') {
                    $current_variant_html .= $body_html;
                }
                if ($citation !== '') {
                    $current_variant_html .= '<div class="footer">' . $citation . '</div>';
                }
                $current_variant_html .= '</div>';
            }

            // Count unique sources, for sorting.
            $current_source_id = $recurrence->ID_FONT !== '' ? $recurrence->ID_FONT : $recurrence->AUTOR . $recurrence->DIARI . $recurrence->ARTICLE;
            if ($prev_source_id === '' || $current_source_id !== $prev_source_id) {
                $variant_sources_count++;
            }
            $prev_source_id = $current_source_id;
        } elseif ($recurrence->LLOC !== '') {
            // Handle location-only entries.
            $current_variant_html .= '<div class="entry">';
            $current_variant_html .= '<div>Lloc: ' . prepare_text_for_html($recurrence->LLOC) . '</div>';
            $current_variant_html .= '</div>';

            // Count places as different sources.
            $variant_sources_count++;
        }
    }

    // Build the display HTML for this variant.
    $modisme_safe = htmlspecialchars($modisme);
    if ($modisme_count === 1 && $modisme_safe === $paremiotipus_display) {
        // Single variant: no header needed.
        $variant_html = $current_variant_html;
    } else {
        // Multiple variants: show header and summary.
        $variant_html = "<h2>{$modisme_safe}</h2>";
        if ($current_variant_html === '' || $variant_sources_count === 0) {
            // Sources with only a year are displayed without details.
            if ($min_year_per_variant < YEAR_MAX) {
                $variant_html .= "<div class='summary'>1 font, {$min_year_per_variant}.</div>";
            }
        } else {
            $variant_html .= '<details open>';
            $variant_html .= '<summary>';
            $variant_html .= $variant_sources_count === 1 ? '1 font' : "{$variant_sources_count} fonts";
            if ($min_year_per_variant < YEAR_MAX) {
                $variant_html .= ", {$min_year_per_variant}";
            }
            $variant_html .= '.';
            $variant_html .= '</summary>';
            $variant_html .= $current_variant_html;
            $variant_html .= '</details>';
        }
    }

    $rendered_variants[] = [
        'count' => $variant_sources_count,
        'html' => $variant_html,
    ];

    // Track the earliest year across all variants.
    if ($min_year_per_variant < $total_min_year) {
        $total_min_year = $min_year_per_variant;
    }
}

// Build description section with statistics.
$output = '<div class="description">';
if ($recurrences_count === 1) {
    $output .= '1&nbsp;recurrència.';
} elseif ($modisme_count === 1) {
    $output .= "{$recurrences_count}&nbsp;recurrències.";
} else {
    $output .= "{$recurrences_count}&nbsp;recurrències en {$modisme_count}&nbsp;variants.";
}
if ($total_min_year < YEAR_MAX) {
    $output .= " Primera&nbsp;citació:&nbsp;{$total_min_year}.";
}
$output .= '</div>';

// Build shortcuts section with toggle button and sharing.
$output .= '<div class="shortcuts">';
if ($modisme_count > 1) {
    $output .= '<button type="button" id="toggle-all" title="Amaga els detalls de cada font">Contrau-ho tot</button>';
}
$share_url = get_paremiotipus_url($paremiotipus_id, absolute: true, encode_full_url: true);
$output .= render_share_icons($share_url, $paremiotipus_display);
$output .= '</div>';

// Display variants sorted by number of sources (most sources first).
usort($rendered_variants, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
foreach ($rendered_variants as $variant) {
    $output .= $variant['html'];
}

echo $output;
