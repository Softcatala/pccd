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

require __DIR__ . '/../src/common.php';

const TEXT_MAX_LENGTH = 100;
const IMAGE_WIDTH = 1200;
const IMAGE_HEIGHT = 630;
const TEXT_MAX_LINES = 4;
const FONT_PATH = __DIR__ . '/../src/fonts/Roboto-Regular.ttf';
const TRIM_CHARS = "[](),?!:;«»º-–—―─'\"“”‘’….¡¿* \n\r\t\v\0\x95";

$paremiotipus = slug_to_name(isset($_GET['paremiotipus']) && is_string($_GET['paremiotipus']) ? $_GET['paremiotipus'] : '');
if ($paremiotipus === '') {
    return_404_and_exit();
}

$text = get_paremiotipus_display(paremiotipus: $paremiotipus, escape_html: false, use_fallback_string: false);
if ($text === '') {
    return_404_and_exit();
}

$text_length = mb_strlen($text);

$image = imagecreatetruecolor(IMAGE_WIDTH, IMAGE_HEIGHT);
assert($image !== false, 'Failed to create image');
imagetruecolortopalette($image, true, 2);

$bg_color = imagecolorallocate(image: $image, red: 43, green: 87, blue: 151);
assert($bg_color !== false, 'Failed to allocate background color');
imagefilledrectangle($image, 0, 0, IMAGE_WIDTH, IMAGE_HEIGHT, $bg_color);

$text_color = imagecolorallocate(image: $image, red: 255, green: 255, blue: 255);
assert($text_color !== false, 'Failed to allocate text color');

$wrapped_text = wordwrap(string: $text, width: TEXT_MAX_LENGTH / TEXT_MAX_LINES, cut_long_words: true);
$n_lines = substr_count($wrapped_text, "\n") + 1;

if ($n_lines > TEXT_MAX_LINES) {
    $lines = explode("\n", $wrapped_text);
    $wrapped_text = implode("\n", array_slice($lines, 0, TEXT_MAX_LINES));
    $wrapped_text = rtrim($wrapped_text, TRIM_CHARS) . '…';
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
    $bbox = imagettfbbox($font_size, 0, FONT_PATH, $wrapped_text);
    assert($bbox !== false, 'Failed to calculate text bounding box');

    $text_width = $bbox[4] - $bbox[0];
    $text_height = $bbox[5] - $bbox[1];

    $x = (int) ((IMAGE_WIDTH - $text_width) / 2);
    $y = (int) ((IMAGE_HEIGHT - $text_height) / 2);

    if ($text_width + 100 > IMAGE_WIDTH) {
        $font_size -= 10;
    }
} while ($text_width + 100 > IMAGE_WIDTH);

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
    font_filename: FONT_PATH,
    text: $wrapped_text
);

header('Content-Type: image/png');
header('Cache-Control: public, max-age=31536000, immutable');
imagepng($image);
