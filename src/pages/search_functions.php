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

const MAX_SEARCH_QUERY_LENGTH = 255;

const PAGINATION_ALL_RESULTS = 999999;
const PAGINATION_RESULTS_PER_PAGE_DEFAULT = 10;
const PAGINATION_RESULTS_PER_PAGE_OPTIONS = [10, 15, 25, 50];
const PAGINATION_ELLIPSIS_JUMP_SIZE = 5;

/**
 * Returns the search mode from the query string, or the default if not present or invalid.
 */
function get_search_mode_from_query(): SearchMode
{
    $input_search_mode = isset($_GET['mode']) && is_string($_GET['mode']) ? $_GET['mode'] : '';

    return SearchMode::tryFrom($input_search_mode) ?? SearchMode::CONTAINS;
}

/**
 * Returns the computed search mode, based on the searched string.
 */
function get_internal_search_mode(): SearchMode
{
    $search_mode = get_search_mode_from_query();

    // Return user-provided search mode if it is not the default.
    if ($search_mode !== SearchMode::CONTAINS) {
        return $search_mode;
    }

    // For the default search mode, switch to internal search modes based on conditions.
    $query = isset($_GET['cerca']) && is_string($_GET['cerca']) ? mb_trim($_GET['cerca']) : '';

    if (str_starts_with($query, '"') && str_ends_with($query, '"')) {
        // Whole-sentence search.
        return SearchMode::WHOLE_SENTENCE;
    }

    if (!str_contains($query, ' ') && (str_contains($query, '*') || str_contains($query, '?'))) {
        // Wildcard-enabled search for single-word searches.
        return SearchMode::WILDCARD;
    }

    return SearchMode::CONTAINS;
}

/**
 * Gets the search input normalized, for the SQL query.
 */
function get_search_query_normalized(): string
{
    if (!isset($_GET['cerca']) || !is_string($_GET['cerca'])) {
        return '';
    }

    $query = mb_trim($_GET['cerca']);
    if ($query === '' || strlen($query) > MAX_SEARCH_QUERY_LENGTH) {
        return '';
    }

    // TODO: ideally, we could normalize Unicode to NFKC, as it is done with the DB. But because characters are usually
    // typed in NFC, and NFC and NFKC tend to handle most common characters (e.g. accented letters in Catalan) the same
    // way, this has not been necessary.
    return normalize_search($query, get_internal_search_mode());
}

/**
 * Gets the search input sanitized for rendering.
 */
function get_search_query_input_clean(): string
{
    if (isset($_GET['cerca']) && is_string($_GET['cerca'])) {
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
        return PAGINATION_ALL_RESULTS;
    }

    if (isset($_GET['mostra']) && is_string($_GET['mostra'])) {
        $results_per_page = (int) $_GET['mostra'];
        if (in_array($results_per_page, PAGINATION_RESULTS_PER_PAGE_OPTIONS, true)) {
            return $results_per_page;
        }
        if ($results_per_page === -1) {
            return PAGINATION_ALL_RESULTS;
        }
    }

    return PAGINATION_RESULTS_PER_PAGE_DEFAULT;
}

/**
 * Returns whether a checkbox should be checked in the search page.
 *
 * The checkbox parameter specifies the checkbox name to check.
 */
function checkbox_checked(string $checkbox): bool
{
    if (isset($_GET[$checkbox])) {
        return true;
    }

    // "variants" checkbox is enabled by default in the homepage.
    return $checkbox === 'variant' && (!isset($_GET['cerca']) || $_GET['cerca'] === '');
}

/**
 * Returns a search pager URL.
 *
 * The page_number parameter specifies the page number for the URL.
 */
function get_search_pager_url(int $page_number): string
{
    $results_per_page = get_search_pagination_limit();
    if (!isset($_GET['cerca']) || $_GET['cerca'] === '' || !is_string($_GET['cerca'])) {
        // Simplify links to the homepage as much as possible.
        if ($page_number === 1) {
            if ($results_per_page === PAGINATION_RESULTS_PER_PAGE_DEFAULT) {
                return '/';
            }

            return '/?mostra=' . $results_per_page;
        }

        if ($results_per_page === PAGINATION_RESULTS_PER_PAGE_DEFAULT) {
            return '/?pagina=' . $page_number;
        }

        return '/?mostra=' . $results_per_page . '&amp;pagina=' . $page_number;
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

    $url .= '&amp;mostra=' . $results_per_page;

    if ($page_number > 1) {
        $url .= '&amp;pagina=' . $page_number;
    }

    return $url;
}

/**
 * Renders a <select> element to choose the number of results per page.
 *
 * The pagination_limit parameter specifies the current pagination limit.
 */
function render_results_per_page_selector(int $pagination_limit): string
{
    $html = '<select name="mostra" aria-label="Nombre de resultats per pàgina" data-default="' . PAGINATION_RESULTS_PER_PAGE_DEFAULT . '">';
    foreach (PAGINATION_RESULTS_PER_PAGE_OPTIONS as $option) {
        $selected = $pagination_limit === $option ? ' selected' : '';
        $html .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    $html .= '</select>';

    return $html;
}

/**
 * Renders a <select> element to choose the search mode.
 */
function render_search_mode_selector(): string
{
    $search_mode = get_search_mode_from_query();
    $html = '<select name="mode" aria-label="Mode de cerca">';
    foreach (SearchMode::cases() as $mode) {
        $label = $mode->getPublicLabel();
        if ($label !== null) {
            $selected = $search_mode === $mode ? ' selected' : '';
            $html .= '<option value="' . $mode->value . '"' . $selected . '>' . $label . '</option>';
        }
    }
    $html .= '</select>';

    return $html;
}

/**
 * Renders a search pagination element.
 *
 * The page_number parameter specifies the page number for this item.
 * The text parameter specifies the text to display in the item.
 * The title parameter specifies the title attribute for the item.
 * Set is_active to true if this item is the active page.
 */
function render_search_pager_item(int $page_number, int|string $text, string $title = '', bool $is_active = false): string
{
    $rel = '';
    if ($title === 'Pàgina següent') {
        $rel = 'next';
    } elseif ($title === 'Pàgina anterior') {
        $rel = 'prev';
    }

    $pager_item = '<li>';
    if ($is_active) {
        $pager_item .= '<strong title="' . $title . '">' . $text . '</strong>';
    } else {
        $pager_item .= '<a href="' . get_search_pager_url($page_number) . '" title="' . $title . '"';
        if ($rel !== '') {
            $pager_item .= ' rel="' . $rel . '"';
        }
        $pager_item .= '>' . $text . '</a>';
    }
    $pager_item .= '</li>';

    return $pager_item;
}

/**
 * Returns the search pagination links.
 *
 * The current_page_number parameter specifies the current page number.
 * The page_count parameter specifies the total number of pages.
 */
function render_search_pager(int $current_page_number, int $page_count): string
{
    // Only render pagination if there is more than one page.
    if ($page_count < 2) {
        return '';
    }

    $pager_items = [];

    // Render previous page links.
    if ($current_page_number > 1) {
        // Previous page button.
        $pager_items[] = render_search_pager_item(
            page_number: $current_page_number - 1,
            text: '<svg aria-hidden="true" viewBox="0 0 24 24"><path fill="currentColor" d="M15.535 3.515 7.05 12l8.485 8.485 1.415-1.414L9.878 12l7.072-7.071z"/></svg> Anterior',
            title: 'Pàgina anterior'
        );

        // First page link.
        $pager_items[] = render_search_pager_item(
            page_number: 1,
            text: '1',
            title: 'Primera pàgina'
        );

        // `…` previous link.
        if ($current_page_number > 2) {
            $ellipsis_prev_page = max(2, $current_page_number - PAGINATION_ELLIPSIS_JUMP_SIZE);
            $pager_items[] = render_search_pager_item(
                page_number: $ellipsis_prev_page,
                text: $ellipsis_prev_page === 2 && $current_page_number === 3 ? $ellipsis_prev_page : '…',
                title: "Pàgina {$ellipsis_prev_page}"
            );
        }
    }

    // Current page item.
    $pager_items[] = render_search_pager_item(
        page_number: $current_page_number,
        text: $current_page_number,
        title: "Sou a la pàgina {$current_page_number}",
        is_active: true
    );

    // Next page links.
    if ($current_page_number < $page_count) {
        // `…` next link.
        if ($current_page_number < $page_count - 1) {
            $ellipsis_next_page = min($current_page_number + PAGINATION_ELLIPSIS_JUMP_SIZE, $page_count - 1);
            $pager_items[] = render_search_pager_item(
                page_number: $ellipsis_next_page,
                text: $ellipsis_next_page === $page_count - 1 && $current_page_number === $page_count - 2 ? $ellipsis_next_page : '…',
                title: "Pàgina {$ellipsis_next_page}"
            );
        }

        // Last page link.
        $pager_items[] = render_search_pager_item(
            page_number: $page_count,
            text: $page_count,
            title: 'Última pàgina'
        );

        // Next page button.
        $pager_items[] = render_search_pager_item(
            page_number: $current_page_number + 1,
            text: 'Següent <svg aria-hidden="true" viewBox="0 0 24 24"><path fill="currentColor" d="M8.465 20.485 16.95 12 8.465 3.515 7.05 4.929 14.122 12 7.05 19.071z"/></svg>',
            title: 'Pàgina següent'
        );
    }

    return '<nav aria-label="Paginació dels resultats"><ul>' . implode('', $pager_items) . '</ul></nav>';
}

/**
 * Determines if the given number requires an apostrophe in Catalan.
 *
 * The num parameter specifies the number to check.
 */
function number_needs_apostrophe(int $num): bool
{
    // A result count of 11M or bigger is not expected, so this logic is sufficient.
    return $num === 1 || $num === 11 || ($num >= 11000 && $num < 12000);
}

/**
 * Returns the search summary.
 *
 * The offset parameter specifies the current offset for pagination.
 * The results_per_page parameter specifies the number of results per page.
 * The result_count parameter specifies the total number of results.
 * The search_query parameter specifies the search query string.
 */
function render_search_summary(int $offset, int $results_per_page, int $result_count, string $search_query): string
{
    if ($result_count === 1) {
        return 'S\'ha trobat 1 paremiotipus per a la cerca <span class="text-monospace text-break">' . $search_query . '</span>.';
    }

    $output = "S'han trobat " . format_nombre($result_count) . ' paremiotipus per a la cerca <span class="text-monospace text-break">' . $search_query . '</span>.';

    if ($result_count <= $results_per_page) {
        return $output;
    }

    $first_result_number = $offset + 1;
    $output .= (number_needs_apostrophe($first_result_number) ? " Registres de l'" : ' Registres del ') . format_nombre($first_result_number);

    $last_result_number = min($offset + $results_per_page, $result_count);
    $output .= (number_needs_apostrophe($last_result_number) ? " a l'" : ' al ') . format_nombre($last_result_number) . '.';

    return $output;
}

/**
 * Builds the search query, storing it in $where_clause variable, and returns the search arguments.
 *
 * @return array{0: string, 1: list<string>} Returns a tuple where the first element is the SQL where clause and the second element is the list of query arguments
 */
function build_search_sql_query(): array
{
    $search_mode = get_internal_search_mode();
    $search_query = get_search_query_normalized();
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
        $columns_to_search = '`PAREMIOTIPUS`';

        foreach ($checkboxes as $checkbox_name => $column_name) {
            if (isset($_GET[$checkbox_name])) {
                $columns_to_search .= ", {$column_name}";
            }
        }

        $where_clause = " WHERE MATCH({$columns_to_search}) AGAINST (? IN BOOLEAN MODE)";
    }

    foreach ($checkboxes as $checkbox_name => $column_name) {
        if (isset($_GET[$checkbox_name])) {
            if ($search_mode === SearchMode::WHOLE_SENTENCE || $search_mode === SearchMode::WILDCARD) {
                $where_clause .= " OR {$column_name} REGEXP CONCAT('[[:<:]]', ?, '[[:>:]]')";
                $arguments[] = $search_query;
            } elseif ($search_mode === SearchMode::STARTS_WITH) {
                $where_clause .= " OR {$column_name} LIKE CONCAT(?, '%')";
                $arguments[] = $search_query;
            } elseif ($search_mode === SearchMode::ENDS_WITH) {
                $where_clause .= " OR {$column_name} LIKE CONCAT('%', ?)";
                $arguments[] = $search_query;
            } elseif ($search_mode === SearchMode::EXACT) {
                $where_clause .= " OR {$column_name} = ?";
                $arguments[] = $search_query;
            }
        }
    }

    return [$where_clause, $arguments];
}

/**
 * Returns the number of search results.
 *
 * The where_clause parameter specifies the SQL WHERE clause to use for counting results.
 *
 * @param list<string> $arguments The arguments to bind to the SQL query.
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
 * The where_clause parameter specifies the SQL WHERE clause to use for fetching results.
 * The limit parameter specifies the maximum number of results to return.
 * The offset parameter specifies the offset from which to start returning results.
 *
 * @param list<string> $arguments The arguments to bind to the SQL query.
 *
 * @return list<string> A list of PAREMIOTIPUS strings matching the search criteria.
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
