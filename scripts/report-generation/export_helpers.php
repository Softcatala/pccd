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
 * Writes a database result set to a JSON file using bounded memory.
 */
function write_report_export_json(string $filename, PDOStatement $statement, string $output_directory, int $fetch_mode = PDO::FETCH_ASSOC): void
{
    $path = "{$output_directory}/{$filename}";
    $file = fopen($path, 'w');
    if ($file === false) {
        throw new RuntimeException("Failed to open {$path}");
    }

    try {
        if (fwrite($file, '[') === false) {
            throw new RuntimeException("Failed to write {$filename}");
        }

        $first = true;
        $buffer = '';
        $buffer_length = 0;

        while (($row = $statement->fetch($fetch_mode)) !== false) {
            $json = json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $separator = $first ? '' : ',';
            $buffer .= $separator . $json;
            $buffer_length += strlen($separator) + strlen($json);

            if ($buffer_length >= 1048576) {
                if (fwrite($file, $buffer) === false) {
                    throw new RuntimeException("Failed to write {$filename}");
                }

                $buffer = '';
                $buffer_length = 0;
            }

            $first = false;
        }

        if ($buffer !== '' && fwrite($file, $buffer) === false) {
            throw new RuntimeException("Failed to write {$filename}");
        }

        if (fwrite($file, "]\n") === false) {
            throw new RuntimeException("Failed to write {$filename}");
        }
    } finally {
        $statement->closeCursor();
        fclose($file);
    }
}
