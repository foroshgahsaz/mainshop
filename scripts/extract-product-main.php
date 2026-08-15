<?php

$base = dirname(__DIR__);
$product = file($base . '/shophtml/product.html', FILE_IGNORE_NEW_LINES);

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

$main = convertPaths(implode("\n", array_slice($product, 260, 550)));
file_put_contents($base . '/resources/views/shop/products/_show_static.blade.php', $main);
echo "Product main extracted.\n";
