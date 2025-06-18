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
$recurrences_by_variant = get_recurrences_by_variant($paremiotipus_input);
$modisme_count = count($recurrences_by_variant);

if ($modisme_count === 0) {
    // Try to redirect (HTTP 301) to a valid paremiotipus page.
    try_to_redirect_to_valid_paremiotipus_and_exit($paremiotipus_input);

    // If no match could be found, return an HTTP 404 page.
    return_404_and_exit(input_paremiotipus: get_paremiotipus_best_match($paremiotipus_input));
}

$paremiotipus_id = current($recurrences_by_variant)[0]->PAREMIOTIPUS;
$canonical_url = get_paremiotipus_url($paremiotipus_id, absolute: true);

// Redirect old URLs to the new ones.
if (!str_starts_with(get_request_uri(), '/p/')) {
    header("Location: {$canonical_url}", response_code: 301);

    exit;
}

// Set page title and canonical URL.
$paremiotipus_display = get_paremiotipus_display($paremiotipus_id);
PageRenderer::setTitle($paremiotipus_display);
PageRenderer::setCanonicalUrl($canonical_url);

// Render side blocks and set additional OG tags.
$blocks_html = render_common_voice_and_set_og_tags($paremiotipus_id);
$blocks_html .= render_paremiotipus_images_and_set_og_tags($paremiotipus_id, alt_text: $paremiotipus_display);
PageRenderer::setParemiotipusBlocks($blocks_html);

// Init necessary variables for the main loop.
$editorials = get_editorials();
$fonts = get_fonts_paremiotipus();
$total_min_year = YEAR_MAX;
$recurrences_count = 0;
$rendered_variants = [];

// Process each variant of the paremiotipus.
foreach ($recurrences_by_variant as $modisme => $recurrences) {
    $min_year_per_variant = YEAR_MAX;
    $prev_source_id = '';
    $variant_sources_count = 0;
    $current_variant_html = '';
    $recurrences_count += count($recurrences);

    // Process each recurrence within this variant.
    foreach ($recurrences as $recurrence) {
        $year = (int) $recurrence->ANY;
        if ($year > 0 && $year < $min_year_per_variant) {
            $min_year_per_variant = $year;
        }

        $body = render_entry_body($recurrence);
        $citation = render_citation($recurrence, $editorials, $fonts);
        if ($body !== '' || $citation !== '') {
            $current_variant_html .= '<div class="entry">';
            $current_variant_html .= $body;
            $current_variant_html .= $citation;
            $current_variant_html .= '</div>';

            // Do not count repeated entries as different sources (e.g., different translations or places).
            $current_source_id = $recurrence->ID_FONT . $recurrence->AUTOR . $recurrence->DIARI;
            if ($current_source_id !== $prev_source_id) {
                $variant_sources_count++;
            }
            $prev_source_id = $current_source_id;
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

    $rendered_variants[] = [
        'count' => $variant_sources_count,
        'html' => $variant_html,
    ];

    // Track the earliest year across all variants.
    if ($min_year_per_variant < $total_min_year) {
        $total_min_year = $min_year_per_variant;
    }
}

// Start printing page output.
// Print description section with statistics.
echo '<div class="description">';
if ($recurrences_count === 1) {
    echo '1&nbsp;recurrència.';
} elseif ($modisme_count === 1) {
    echo "{$recurrences_count}&nbsp;recurrències.";
} else {
    echo "{$recurrences_count}&nbsp;recurrències en {$modisme_count}&nbsp;variants.";
}
if ($total_min_year < YEAR_MAX) {
    echo " Primera&nbsp;citació:&nbsp;{$total_min_year}.";
}
echo '</div>';

// Print shortcuts section.
echo '<div class="shortcuts">';
if ($modisme_count > 1) {
    // Print the toggle button.
    echo '<button type="button" id="toggle-all" title="Amaga els detalls de cada font">Contrau-ho tot</button>';
}
// Print share icons.
$share_url = get_paremiotipus_url($paremiotipus_id, absolute: true, encode_full_url: true);
echo render_share_icons($share_url, $paremiotipus_display);
echo '</div>';

// Print variants sorted by number of sources (most sources first).
usort($rendered_variants, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
foreach ($rendered_variants as $variant) {
    echo $variant['html'];
}
