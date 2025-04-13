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

/**
 * Gets an array of unique variants, keyed by MODISME.
 *
 * @return array<string, non-empty-list<ParemiotipusVariant>>
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

    return $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_CLASS, ParemiotipusVariant::class);
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

/**
 * Tries to get the best paremiotipus by searching.
 */
function get_paremiotipus_best_match(string $input_modisme): string
{
    // We do not want to avoid words here.
    $modisme = trim($input_modisme, '-');
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
 * Try to redirect to a valid paremiotipus page and exit.
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
