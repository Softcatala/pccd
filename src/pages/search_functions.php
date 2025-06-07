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
const PAGER_ALL = 999999;

/**
 * Returns the search mode.
 */
function get_search_mode(): SearchMode
{
    $input_search_mode = isset($_GET['mode']) && is_string($_GET['mode']) ? $_GET['mode'] : '';
    $search_mode = SearchMode::tryFrom($input_search_mode) ?? SearchMode::CONTAINS;

    // Switch to internal search modes based on conditions.
    if (
        $search_mode === SearchMode::CONTAINS
        && isset($_GET['cerca'])
        && $_GET['cerca'] !== ''
        && is_string($_GET['cerca'])
    ) {
        $trimmed_search = mb_trim($_GET['cerca']);
        if (str_starts_with($trimmed_search, '"') && str_ends_with($trimmed_search, '"')) {
            // Simple custom search mode for whole sentence search.
            $search_mode = SearchMode::WHOLE_SENTENCE;
        } elseif (
            !str_contains($trimmed_search, ' ')
            && (
                str_contains($trimmed_search, '*')
                || str_contains($trimmed_search, '?')
            )
        ) {
            // Simple custom search mode for using wildcards in single-word searches.
            $search_mode = SearchMode::WILDCARD;
        }
    }

    return $search_mode;
}

/**
 * Gets the search input normalized, for the SQL query.
 */
function get_search_normalized(SearchMode $search_mode): string
{
    if (isset($_GET['cerca']) && $_GET['cerca'] !== '' && is_string($_GET['cerca'])) {
        $trimmed_search = mb_trim($_GET['cerca']);
        $search_length = strlen($trimmed_search);
        if ($search_length > 0 && $search_length < 255) {
            return normalize_search($trimmed_search, $search_mode);
        }
    }

    return '';
}

/**
 * Gets the search input sanitized for rendering.
 */
function get_search_clean(): string
{
    if (isset($_GET['cerca']) && $_GET['cerca'] !== '' && is_string($_GET['cerca'])) {
        return htmlspecialchars(mb_trim($_GET['cerca']));
    }

    return '';
}

/**
 * Returns the pagination limit from query string.
 */
function get_search_pagination_limit(): int
{
    if (isset($_GET['font'])) {
        // If a specific font is requested, return all entries.
        return PAGER_ALL;
    }

    if (isset($_GET['mostra'])) {
        $mostra = $_GET['mostra'];
        if ($mostra === '15' || $mostra === '25' || $mostra === '50') {
            return (int) $mostra;
        }
        if ($mostra === 'infinit') {
            return PAGER_ALL;
        }
    }

    return PAGER_DEFAULT;
}

/**
 * Returns the number of search pages.
 */
function get_search_page_count(int $result_count, int $results_per_page): int
{
    return (int) ceil($result_count / $results_per_page);
}

/**
 * Returns the page number offset.
 */
function get_search_page_offset(int $current_page, int $results_per_page): int
{
    return ($current_page - 1) * $results_per_page;
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
 * Returns a search pager URL.
 */
function get_search_pager_url(int $page_number): string
{
    $mostra = get_search_pagination_limit();
    if (!isset($_GET['cerca']) || $_GET['cerca'] === '' || !is_string($_GET['cerca'])) {
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
    if (isset($_GET['mode']) && is_string($_GET['mode'])) {
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
function render_search_pager_element(int $page_number, int|string $name, int|string $title = '', bool $is_active = false): string
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
        $pager_item .= '<a href="' . get_search_pager_url($page_number) . '" title="' . $title . '"';
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
function render_search_pager(int $page_number, int $page_count): string
{
    // Previous and first page links.
    $prev_links = '';
    if ($page_number > 1) {
        // Show previous link.
        $prev_links .= render_search_pager_element(
            $page_number - 1,
            '<svg aria-hidden="true" viewBox="0 0 24 24"><path fill="currentColor" d="M15.535 3.515 7.05 12l8.485 8.485 1.415-1.414L9.878 12l7.072-7.071z"/></svg> Anterior',
            'Pàgina anterior'
        );

        // Show first page link.
        $prev_links .= render_search_pager_element(1, '1', 'Primera pàgina');
    }

    // Current page item.
    $page_links = render_search_pager_element($page_number, $page_number, 'Sou a la pàgina ' . $page_number, true);

    // `…` previous link.
    if ($page_number > 2) {
        $prev_prev_page = max(2, $page_number - 5);
        $page_links = render_search_pager_element(
            $prev_prev_page,
            $prev_prev_page === 2 && $page_number === 3 ? '2' : '…',
            'Pàgina ' . $prev_prev_page
        ) . $page_links;
    }

    // `…` next link.
    if ($page_number < $page_count - 1) {
        $next_next_page = min($page_number + 5, $page_count - 1);
        $page_links .= render_search_pager_element(
            $next_next_page,
            $next_next_page === $page_count - 1 && $page_number === $page_count - 2 ? $next_next_page : '…',
            'Pàgina ' . $next_next_page
        );
    }

    // Next and last page links.
    $next_links = '';
    if ($page_number < $page_count) {
        // Show the last page link.
        $next_links = render_search_pager_element($page_count, $page_count, 'Última pàgina');

        // Show the next link.
        $next_links .= render_search_pager_element(
            $page_number + 1,
            'Següent <svg aria-hidden="true" viewBox="0 0 24 24"><path fill="currentColor" d="M8.465 20.485 16.95 12 8.465 3.515 7.05 4.929 14.122 12 7.05 19.071z"/></svg>',
            'Pàgina següent'
        );
    }

    return '<nav aria-label="Paginació dels resultats"><ul>' . $prev_links . $page_links . $next_links . '</ul></nav>';
}

/**
 * Determines if the given number requires an apostrophe in Catalan.
 */
function number_needs_apostrophe(int $num): bool
{
    // A result count of 11M or biggger is not expected, so this logic is sufficient.
    return $num === 1 || $num === 11 || ($num >= 11000 && $num < 12000);
}

/**
 * Returns the search summary.
 */
function render_search_summary(int $offset, int $results_per_page, int $result_count, string $search_string): string
{
    if ($result_count === 1) {
        return 'S\'ha trobat 1 paremiotipus per a la cerca <span class="text-monospace">' . $search_string . '</span>.';
    }

    $output = "S'han trobat " . format_nombre($result_count) . ' paremiotipus per a la cerca <span class="text-monospace">' . $search_string . '</span>.';

    if ($result_count > $results_per_page) {
        $first_record = $offset + 1;
        $output .= (number_needs_apostrophe($first_record) ? " Registres de l'" : ' Registres del ') . format_nombre($first_record);

        $last_record = min($offset + $results_per_page, $result_count);
        $output .= (number_needs_apostrophe($last_record) ? " a l'" : ' al ') . format_nombre($last_record) . '.';
    }

    return $output;
}

/**
 * Builds the search query, storing it in $where_clause variable, and returns the search arguments.
 *
 * @return array{0: string, 1: list<string>} Returns a tuple where the first element is the SQL where clause and the second element is the list of query arguments
 */
function build_search_sql_query(string $search_query, SearchMode $search_mode): array
{
    $checkboxes = [
        'equivalent' => '`EQUIVALENT`',
        'sinonim' => '`SINONIM`',
        'variant' => '`MODISME`',
    ];

    $arguments = [$search_query];
    if ($search_mode === SearchMode::WHOLE_SENTENCE || $search_mode === SearchMode::WILDCARD) {
        $where_clause = " WHERE `PAREMIOTIPUS` REGEXP CONCAT('[[:<:]]', ?, '[[:>:]]')";
    } elseif ($search_mode === SearchMode::STARTS_WITH) {
        $where_clause = " WHERE `PAREMIOTIPUS` LIKE CONCAT(?, '%')";
    } elseif ($search_mode === SearchMode::ENDS_WITH) {
        $where_clause = " WHERE `PAREMIOTIPUS` LIKE CONCAT('%', ?)";
    } elseif ($search_mode === SearchMode::EXACT) {
        $where_clause = ' WHERE `PAREMIOTIPUS` = ?';
    } elseif (isset($_GET['font']) && $_GET['font'] !== '' && is_string($_GET['font'])) {
        $arguments = [slug_to_name($_GET['font'])];
        $where_clause = ' WHERE `ID_FONT` = ?';
    } else {
        // SearchMode::CONTAINS (default) search mode uses full-text.
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
            if ($search_mode === SearchMode::WHOLE_SENTENCE || $search_mode === SearchMode::WILDCARD) {
                $where_clause .= " OR {$column} REGEXP CONCAT('[[:<:]]', ?, '[[:>:]]')";
                $arguments[] = $search_query;
            } elseif ($search_mode === SearchMode::STARTS_WITH) {
                $where_clause .= " OR {$column} LIKE CONCAT(?, '%')";
                $arguments[] = $search_query;
            } elseif ($search_mode === SearchMode::ENDS_WITH) {
                $where_clause .= " OR {$column} LIKE CONCAT('%', ?)";
                $arguments[] = $search_query;
            } elseif ($search_mode === SearchMode::EXACT) {
                $where_clause .= " OR {$column} = ?";
                $arguments[] = $search_query;
            }
        }
    }

    return [$where_clause, $arguments];
}

/**
 * Returns the number of search results.
 *
 * @param list<string> $arguments
 *
 * @throws Exception If the query fails
 */
function get_result_count(string $where_clause, array $arguments): int
{
    // Create a unique cache key based on the query and arguments.
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
