<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LayerDependencyTest extends TestCase
{
    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function forbiddenDependencies(): iterable
    {
        yield 'domain remains independent from outer layers and frameworks' => [
            dirname(__DIR__, 3).'/app/Domain',
            [
                'App\\Http\\',
                'App\\Infrastructure\\',
                'App\\Models\\',
                'App\\Services\\',
                'App\\UseCases\\',
                'Illuminate\\',
            ],
        ];

        yield 'services do not depend on use cases or controllers' => [
            dirname(__DIR__, 3).'/app/Services',
            [
                'App\\Http\\Controllers\\',
                'App\\UseCases\\',
            ],
        ];

        yield 'use cases do not depend on controllers or HTTP requests' => [
            dirname(__DIR__, 3).'/app/UseCases',
            [
                'App\\Http\\Controllers\\',
                'Illuminate\\Http\\Request',
            ],
        ];
    }

    /**
     * @param  list<string>  $forbiddenNamespaces
     */
    #[DataProvider('forbiddenDependencies')]
    public function test_layer_has_no_forbidden_dependencies(string $directory, array $forbiddenNamespaces): void
    {
        $violations = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            foreach ($forbiddenNamespaces as $namespace) {
                if (str_contains($source, 'use '.$namespace)) {
                    $violations[] = $file->getPathname().' imports '.$namespace;
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }
}
