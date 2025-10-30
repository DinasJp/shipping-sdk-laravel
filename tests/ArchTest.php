<?php

use Symfony\Component\Finder\Finder;

it('does not use dd, dump, or ray in package files', function () {
    $finder = new Finder();
    $finder->files()
        ->in([__DIR__ . '/../src', __DIR__])
        ->name('*.php');

    $pattern = '/\b(?:dd|dump|ray)\s*\(/i';

    foreach ($finder as $file) {
        $contents = $file->getContents();
        if (preg_match($pattern, $contents, $m)) {
            $path = $file->getRealPath();
            $this->fail("Found debugging function \"{$m[0]}\" in {$path}");
        }
    }

    $this->assertTrue(true);
});
