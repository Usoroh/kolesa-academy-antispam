<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf8ca02e7482fc07f17979c7ef16d9a53
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Predis\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Predis\\' => 
        array (
            0 => __DIR__ . '/..' . '/predis/predis/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitf8ca02e7482fc07f17979c7ef16d9a53::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitf8ca02e7482fc07f17979c7ef16d9a53::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitf8ca02e7482fc07f17979c7ef16d9a53::$classMap;

        }, null, ClassLoader::class);
    }
}
