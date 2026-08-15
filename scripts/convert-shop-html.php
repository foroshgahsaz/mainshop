<?php

$base = dirname(__DIR__);
$index = file($base.'/shophtml/index.html', FILE_IGNORE_NEW_LINES);

function convertPaths(string $text): string
{
    $text = preg_replace('#href="css/#', 'href="{{ asset(\'shop/css/', $text);
    $text = preg_replace('#href="vendor/#', 'href="{{ asset(\'shop/vendor/', $text);
    $text = preg_replace('#src="vendor/#', 'src="{{ asset(\'shop/vendor/', $text);
    $text = preg_replace('#src="js/#', 'src="{{ asset(\'shop/js/', $text);
    $text = preg_replace('#src="images/#', 'src="{{ asset(\'shop/images/', $text);
    $text = preg_replace('#\.(css|js|webp|jpg|jpeg|svg|png)"#', '.$1\') }}"', $text);
    $text = str_replace('href="index.html"', 'href="{{ route(\'home\') }}"', $text);

    return $text;
}

$out = $base.'/resources/views/shop/partials';
if (! is_dir($out)) {
    mkdir($out, 0777, true);
}

file_put_contents("$out/footer.blade.php", convertPaths(implode("\n", array_slice($index, 983, 257))));
file_put_contents("$out/overlays.blade.php", convertPaths(implode("\n", array_slice($index, 1241, 268))));

echo "Converted footer and overlays.\n";
