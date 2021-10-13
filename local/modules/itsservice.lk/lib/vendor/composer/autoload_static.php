<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit26f506ce42234dbe19af9b471bde868b
{
    public static $prefixLengthsPsr4 = array (
        's' => 
        array (
            'setasign\\Fpdi\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'setasign\\Fpdi\\' => 
        array (
            0 => __DIR__ . '/..' . '/setasign/fpdi/src',
        ),
    );

    public static $classMap = array (
        'Clegginabox\\PDFMerger\\PDFMerger' => __DIR__ . '/..' . '/clegginabox/pdf-merger/src/PDFMerger/PDFMerger.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'FPDF' => __DIR__ . '/..' . '/setasign/fpdf/fpdf.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit26f506ce42234dbe19af9b471bde868b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit26f506ce42234dbe19af9b471bde868b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit26f506ce42234dbe19af9b471bde868b::$classMap;

        }, null, ClassLoader::class);
    }
}
