<?php

$root = dirname(__DIR__);
$files = [
    'help-members.png' => [0.292, 0.775, 0.355, 0.905],
    'help-member-transfer.png' => [0.255, 0.70, 0.62, 0.93],
    'help-coupons.png' => [0.748, 0.168, 0.905, 0.218],
    'help-coupon-delivery.png' => [0.25, 0.36, 0.70, 0.90],
    'help-analytics.png' => [0.26, 0.14, 0.95, 0.55],
    'help-analysis-delivery.png' => [0.26, 0.48, 0.95, 0.90],
    'help-settings.png' => [0.255, 0.47, 0.60, 0.57],
    'help-home-settings.png' => [0.26, 0.16, 0.95, 0.78],
    'help-app-more.png' => [0.64, 0.90, 0.76, 0.98],
];

foreach ($files as $name => $box) {
    $path = $root.'/public/images/help/'.$name;
    $image = imagecreatefrompng($path);
    if ($image === false) {
        fwrite(STDERR, "skip {$name}\n");
        continue;
    }
    $width = imagesx($image);
    $height = imagesy($image);
    $color = imagecolorallocate($image, 225, 29, 72);
    $x1 = (int) round($width * $box[0]);
    $y1 = (int) round($height * $box[1]);
    $x2 = (int) round($width * $box[2]);
    $y2 = (int) round($height * $box[3]);
    imagesetthickness($image, 8);
    imagerectangle($image, $x1, $y1, $x2, $y2, $color);
    imagepng($image, $path);
    imagedestroy($image);
    echo "{$name} {$width}x{$height}\n";
}
