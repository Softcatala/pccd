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

$request_uri = get_request_uri();
$paremiotipus = is_string($_GET['paremiotipus']) ? path_to_name($_GET['paremiotipus']) : '';
$variants = get_modismes_by_variant($paremiotipus);

$total_variants = count($variants);
if ($total_variants === 0) {
    // Try to redirect (HTTP 301) to a valid paremiotipus page.
    try_to_redirect_to_valid_paremiotipus_and_exit($paremiotipus);

    // If no match could be found, return an HTTP 404 page.
    error_log("Error: entry not found for URL: {$request_uri}");
    return_404_and_exit(paremiotipus: get_paremiotipus_best_match($paremiotipus));
}

$editorials = get_editorials();
$fonts = get_fonts_paremiotipus();

// Loop through the variants.
$paremiotipus_db = '';
$paremiotipus_display = '';
$canonical_url = '';
$share_url = '';
$total_min_year = YEAR_MAX;
$total_recurrences = 0;
$rendered_variants = [];
foreach ($variants as $modisme => $variant) {
    if ($canonical_url === '') {
        // Set the canonical URL and page title.
        $paremiotipus_db = $variant[0]->PAREMIOTIPUS;
        $canonical_url = get_paremiotipus_url(paremiotipus: $paremiotipus_db, absolute: true);
        $share_url = get_paremiotipus_url(paremiotipus: $paremiotipus_db, absolute: true, encode_full_url: true);

        // Redirect old URLs to the new ones.
        if (!str_starts_with($request_uri, '/p/')) {
            header("Location: {$canonical_url}", response_code: 301);

            exit;
        }

        PageRenderer::setCanonicalUrl($canonical_url);
        $paremiotipus_display = get_paremiotipus_display($paremiotipus_db);
        PageRenderer::setTitle($paremiotipus_display);
    }

    // Loop through the variant's recurrences.
    $min_year = YEAR_MAX;
    $prev_source = '';
    $variant_sources = 0;
    $paremia = '';
    $total_recurrences += count($variant);
    foreach ($variant as $v) {
        // TODO: rector is less clever.
        // @phpstan-ignore identical.alwaysTrue, function.alreadyNarrowedType
        assert($v::class === ParemiotipusVariant::class);
        $work = '';
        if ($v->AUTOR !== '') {
            $work = htmlspecialchars($v->AUTOR);
        }
        if ($v->ANY !== '' && $v->ANY !== '0') {
            if ($work !== '') {
                $work .= ' ';
            }
            $work .= '(' . $v->ANY . ')';
            if ($v->ANY < $min_year) {
                $min_year = (int) $v->ANY;
            }
        }
        if ($work !== '' && ($v->DIARI !== '' || $v->ARTICLE !== '')) {
            $work .= ':';
        }
        $editorial = '';
        if ($v->EDITORIAL !== '') {
            $editorial = $editorials[$v->EDITORIAL] ?? $v->EDITORIAL;
        }
        // Print DIARI if it is different from EDITORIAL.
        if ($v->DIARI !== '' && $v->DIARI !== $editorial) {
            if ($work !== '') {
                $work .= ' ';
            }
            $diari = '<i>' . htmlspecialchars($v->DIARI) . '</i>';
            if ($v->ID_FONT !== '' && isset($fonts[$v->ID_FONT])) {
                $diari = '<a href="' . get_obra_url($v->ID_FONT) . '">' . $diari . '</a>';
            }
            $work .= $diari;
        }
        if ($v->ARTICLE !== '') {
            if ($work !== '') {
                $work .= ' ';
            }
            $work .= '«' . html_escape_and_link_urls($v->ARTICLE) . '»';
        }
        if ($v->PAGINA !== '') {
            $work .= ', p. ' . htmlspecialchars($v->PAGINA);
        }
        if ($editorial !== '') {
            if ($work !== '') {
                $work .= '. ';
            }
            // TODO: rector is less clever.
            // @phpstan-ignore function.alreadyNarrowedType, function.alreadyNarrowedType
            assert(is_string($editorial));
            $work .= htmlspecialchars($editorial);
        }
        if ($work !== '') {
            if ($v->ACCEPCIO !== '') {
                $work .= ', accepció ' . htmlspecialchars($v->ACCEPCIO);
            }
            if (!str_ends_with($work, '.')) {
                $work .= '.';
            }

            $explanation = '';
            if ($v->EXPLICACIO !== '' && $v->EXPLICACIO2 !== '') {
                $explanation = mb_ucfirst(ct($v->EXPLICACIO . $v->EXPLICACIO2));
            } elseif (strlen($v->EXPLICACIO) > 3) {
                $explanation = mb_ucfirst(ct($v->EXPLICACIO));
            }
            if ($v->AUTORIA !== '') {
                if ($explanation !== '') {
                    $explanation .= ' ';
                }
                $explanation .= 'De: ' . ct($v->AUTORIA);
            }

            $body = '';
            if ($explanation !== '') {
                $body .= "<div>{$explanation}</div>";
            }
            if ($v->EXEMPLES !== '') {
                $body .= '<div><i>' . mb_ucfirst(ct($v->EXEMPLES)) . '</i></div>';
            }
            if ($v->SINONIM !== '') {
                $body .= '<div>Sinònim: ' . ct($v->SINONIM) . '</div>';
            }
            if ($v->EQUIVALENT !== '') {
                $equivalent = ct($v->EQUIVALENT);
                $idioma = get_idioma($v->IDIOMA);
                if ($idioma !== '') {
                    $iso_code = get_idioma_iso_code($v->IDIOMA);
                    if ($iso_code !== '') {
                        $equivalent = "<span lang=\"{$iso_code}\">{$equivalent}</span>";
                    }
                    $body .= "<div>Equivalent en {$idioma}: {$equivalent}</div>";
                } else {
                    $body .= "<div>Equivalent: {$equivalent}</div>";
                }
            }
            if ($v->LLOC !== '') {
                $body .= '<div>Lloc: ' . ct($v->LLOC) . '</div>';
            }
            if (strlen($v->FONT) > 1) {
                $body .= '<div>Font: ' . ct($v->FONT) . '</div>';
            }

            // Do not print the footer if the entry only contains the year.
            if ($body === '' && preg_match('/\(\d{4}\).$/', $work) > 0) {
                $work = '';
            }

            if ($body !== '' || $work !== '') {
                $paremia .= '<div class="entry">';
                if ($body !== '') {
                    $paremia .= $body;
                }
                if ($work !== '') {
                    $paremia .= '<div class="footer">' . $work . '</div>';
                }
                $paremia .= '</div>';
            }

            // As results are sorted by these fields, use them for counting the number of sources.
            $current_source = $v->ID_FONT !== '' ? $v->ID_FONT : $v->AUTOR . $v->DIARI . $v->ARTICLE;
            if ($prev_source === '' || $current_source !== $prev_source) {
                $variant_sources++;
            }
            $prev_source = $current_source;
        } elseif ($v->LLOC !== '') {
            $paremia .= '<div class="entry">';
            $paremia .= '<div>Lloc: ' . ct($v->LLOC) . '</div>';
            $paremia .= '</div>';

            // In this case, count places as different sources.
            $variant_sources++;
        }
    }

    $modisme_safe = htmlspecialchars($modisme);
    if ($total_variants === 1 && $modisme_safe === $paremiotipus_display) {
        $rendered_variant = $paremia;
    } else {
        // Show a header and a summary only when there is more than 1 variant.
        $rendered_variant = "<h2>{$modisme_safe}</h2>";
        if ($paremia === '' || $variant_sources === 0) {
            // Sources with only a year are displayed without details.
            if ($min_year < YEAR_MAX) {
                $rendered_variant .= "<div class='summary'>1 font, {$min_year}.</div>";
            }
        } else {
            $rendered_variant .= '<details open>';
            $rendered_variant .= '<summary>';
            $rendered_variant .= $variant_sources === 1 ? '1 font' : "{$variant_sources} fonts";
            if ($min_year < YEAR_MAX) {
                $rendered_variant .= ", {$min_year}";
            }
            $rendered_variant .= '.';
            $rendered_variant .= '</summary>';
            $rendered_variant .= $paremia;
            $rendered_variant .= '</details>';
        }
    }

    $rendered_variants[] = [
        'count' => $variant_sources,
        'html' => $rendered_variant,
    ];

    if ($min_year < $total_min_year) {
        $total_min_year = $min_year;
    }
}

// Build the right column.
// Common Voice.
$mp3_files = get_cv_files($paremiotipus_db);
$cv_output = '';
foreach ($mp3_files as $mp3_file) {
    if (!is_file(__DIR__ . "/../../docroot/mp3/{$mp3_file}")) {
        error_log("Error: asset file is missing: {$mp3_file}");

        continue;
    }

    $is_first_audio = $cv_output === '';
    if ($is_first_audio) {
        PageRenderer::setOgAudioUrl("https://pccd.dites.cat/mp3/{$mp3_file}");
    }

    $cv_output .= '<a class="audio" href="/mp3/' . $mp3_file . '" role="button">';
    $cv_output .= '<audio preload="none" src="/mp3/' . $mp3_file . '"></audio>';
    $cv_output .= '<svg width="32" height="27" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 18" aria-label="Reprodueix" role="img"><g fill="#3c9ae3"><path d="M3.153 6.5H0v4.999h3.153l4.108 3.38s.863.757.863.051V3.001c0-.559-.759.044-.759.044zm10.897 9.841c2.195-1.833 3.453-4.508 3.453-7.34S16.245 3.494 14.05 1.66a.69.69 0 0 0-.971.085.68.68 0 0 0 .084.963A8.18 8.18 0 0 1 16.126 9a8.2 8.2 0 0 1-2.961 6.291.676.676 0 0 0-.086.962c.244.293.68.329.971.088"/><path d="M14.74 9c0-2.02-.896-3.935-2.463-5.243a.69.69 0 0 0-.971.083.68.68 0 0 0 .084.965 5.46 5.46 0 0 1 1.975 4.194 5.46 5.46 0 0 1-1.975 4.194.68.68 0 0 0-.084.963.69.69 0 0 0 .971.082A6.8 6.8 0 0 0 14.74 9"/><path d="M11.984 9a4.09 4.09 0 0 0-1.479-3.147.69.69 0 0 0-1.13.524c0 .195.085.39.246.525a2.72 2.72 0 0 1 0 4.195.68.68 0 0 0-.084.962.69.69 0 0 0 .969.084A4.08 4.08 0 0 0 11.984 9"/></g></svg>';
    $cv_output .= '</a>';
}

// Images.
$images = get_images($paremiotipus_db);
$images_output = '';
foreach ($images as $image) {
    if (!is_file(__DIR__ . "/../../docroot/img/imatges/{$image->Identificador}")) {
        error_log("Error: asset file is missing: {$image->Identificador}");

        continue;
    }

    $is_first_image = $images_output === '';
    if ($is_first_image) {
        // Use it for the meta image.
        PageRenderer::setMetaImage('https://pccd.dites.cat/img/imatges/' . rawurlencode($image->Identificador));
    }

    $image_tag = get_image_tags(
        file_name: $image->Identificador,
        path: '/img/imatges/',
        alt_text: $paremiotipus_display,
        escape_html: false,
        width: $image->WIDTH,
        height: $image->HEIGHT,
        preload: $is_first_image,
        preload_media: '(min-width: 768px)'
    );

    $image_link = get_clean_url($image->URL_ENLLAÇ);
    if ($image_link !== '') {
        $image_tag = '<a href="' . $image_link . '">' . $image_tag . '</a>';
    }

    $images_output .= '<div class="bloc bloc-image text-break"><figure>';
    $images_output .= $image_tag;

    $image_caption = '';
    if ($image->AUTOR !== '') {
        $image_caption = htmlspecialchars($image->AUTOR);
    }
    if ($image->ANY !== '' && $image->ANY !== '0') {
        if ($image_caption !== '') {
            $image_caption .= ' ';
        }
        $image_caption .= '(' . $image->ANY . ')';
    }
    if ($image->DIARI !== '' && $image->DIARI !== $image->AUTOR) {
        if ($image_caption !== '') {
            $image_caption .= ': ';
        }

        // If there is no ARTICLE, link DIARI to the content.
        $diari = htmlspecialchars($image->DIARI);
        if ($image_link !== '' && $image->ARTICLE === '') {
            $diari = '<a href="' . $image_link . '" class="external" target="_blank" rel="noopener">' . $diari . '</a>';
        }
        $image_caption .= "<em>{$diari}</em>";
    }
    if ($image->ARTICLE !== '') {
        if ($image_caption !== '') {
            $image_caption .= ' ';
        }

        // Link to the content, unless the text has a link already.
        if (str_contains($image->ARTICLE, 'http')) {
            // In that case, link to the included URL.
            $article = html_escape_and_link_urls($image->ARTICLE);
        } else {
            $article = htmlspecialchars($image->ARTICLE);
            // Reuse the link of the image, if there is one.
            if ($image_link !== '') {
                $article = '<a href="' . $image_link . '" class="external" target="_blank" rel="noopener">' . $article . '</a>';
            }
        }
        $image_caption .= "«{$article}»";
    }

    if ($image_caption !== '') {
        $images_output .= '<figcaption class="small">' . $image_caption . '</figcaption>';
    }

    $images_output .= '</figure></div>';
}

if ($images_output === '') {
    // If there are no images, generate an OG image with text.
    PageRenderer::setMetaImage('https://pccd.dites.cat/og/' . name_to_path($paremiotipus_db) . '.png');
}

$blocks = '';
if ($cv_output !== '') {
    $blocks = '<div id="commonvoice" class="bloc text-balance text-break" title="Reprodueix un enregistrament">';
    $blocks .= $cv_output;
    $blocks .= '<p><a href="https://commonvoice.mozilla.org/ca">';
    $blocks .= '<img title="Projecte Common Voice" alt="Logo Common Voice" width="100" height="25" src="/img/logo-commonvoice.svg"></a></p>';
    $blocks .= '</div>';
}
if ($images_output !== '') {
    $blocks .= '<div id="imatges">';
    $blocks .= $images_output;
    $blocks .= '</div>';
}
PageRenderer::setParemiotipusBlocks($blocks);

$output = '<div class="description">';
if ($total_recurrences === 1) {
    $output .= '1&nbsp;recurrència.';
} elseif ($total_variants === 1) {
    $output .= "{$total_recurrences}&nbsp;recurrències.";
} else {
    $output .= "{$total_recurrences}&nbsp;recurrències en {$total_variants}&nbsp;variants.";
}
if ($total_min_year < YEAR_MAX) {
    $output .= " Primera&nbsp;citació:&nbsp;{$total_min_year}.";
}
$output .= '</div>';

$output .= '<div class="shortcuts">';
if ($total_variants > 1) {
    $output .= '<button type="button" id="toggle-all" title="Amaga els detalls de cada font">Contrau-ho tot</button>';
}
$output .= '<div class="share-wrapper">';
$output .= '<button type="button" id="share">Comparteix <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17 22q-1.3 0-2.1-.9T14 19v-.7l-7-4.1q-.4.4-.9.6T5 15q-1.3 0-2.1-.9T2 12t.9-2.1T5 9q.6 0 1.1.2t1 .6l7-4.1v-.3L14 5q0-1.3.9-2.1T17 2t2.1.9T20 5t-.9 2.1T17 8q-.6 0-1.1-.2t-1-.6l-7 4.1v.3l.1.4q.1.3 0 .4t0 .3l7 4.1q.4-.4.9-.6T17 16q1.3 0 2.1.9T20 19t-.9 2.1-2.1.9"/></svg></button>';
$output .= '<div class="share-icons" hidden>';
$output .= '<span class="close" role="button" tabindex="0" aria-label="Tanca"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6.4 19 5 17.6l5.6-5.6L5 6.4 6.4 5l5.6 5.6L17.6 5 19 6.4 13.4 12l5.6 5.6-1.4 1.4-5.6-5.6z"/></svg></span>';
$output .= '<a class="share-icon facebook" href="https://www.facebook.com/sharer/sharer.php?u=' . $share_url . '" target="_blank" rel="noopener"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95"/></svg></span><span class="share-title">Facebook</span></a>';
$output .= '<a class="share-icon twitter" href="https://x.com/intent/post?url=' . $share_url . '" target="_blank" rel="noopener"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.2 4.2 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.52 8.52 0 0 1-5.33 1.84q-.51 0-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23"/></svg></span><span class="share-title">Twitter</span></a>';
$output .= '<a class="share-icon whatsapp" href="https://api.whatsapp.com/send?text=' . $share_url . '" target="_blank" rel="noopener"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M19.05 4.91A9.82 9.82 0 0 0 12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01m-7.01 15.24c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.26 8.26 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24 2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 0 1 2.41 5.83c.02 4.54-3.68 8.23-8.22 8.23m4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.17.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43s.17-.25.25-.41c.08-.17.04-.31-.02-.43s-.56-1.34-.76-1.84c-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07s.89 2.4 1.01 2.56c.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18s-.22-.16-.47-.28"/></svg></span><span class="share-title">WhatsApp</span></a>';
$output .= '<a class="share-icon telegram" href="https://t.me/share/?url=' . $share_url . '" target="_blank" rel="noopener"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2m4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38"/></svg></span><span class="share-title">Telegram</span></a>';
$output .= '<a class="share-icon email" href="mailto:?subject=' . rawurlencode($paremiotipus_display) . '&amp;body=' . $share_url . '"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M29 9v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9m26 0a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2m26 0-11.862 8.212a2 2 0 0 1-2.276 0L3 9"/></svg></span><span class="share-title">Correu</span></a>';
$output .= '<a class="share-icon copy" href="#"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M18.327 7.286h-8.044a1.93 1.93 0 0 0-1.925 1.938v10.088c0 1.07.862 1.938 1.925 1.938h8.044a1.93 1.93 0 0 0 1.925-1.938V9.224c0-1.07-.862-1.938-1.925-1.938"/><path d="M15.642 7.286V4.688c0-.514-.203-1.007-.564-1.37a1.92 1.92 0 0 0-1.361-.568H5.673c-.51 0-1 .204-1.36.568a1.95 1.95 0 0 0-.565 1.37v10.088c0 .514.203 1.007.564 1.37s.85.568 1.361.568h2.685"/></g></svg></span><span class="share-title">Enllaç</span></a>';
$output .= '</div></div></div>';

// Print variants sorted by the number of sources.
usort($rendered_variants, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
foreach ($rendered_variants as $rendered_variant) {
    $output .= $rendered_variant['html'];
}

echo $output;
