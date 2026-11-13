<?php
declare(strict_types=1);

namespace App;

/** Renders PHP templates inside a layout. All output goes through e() for HTML escaping. */
final class View
{
    public function __construct(private readonly string $dir, private readonly array $shared = [])
    {
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], ?string $layout = 'layout'): string
    {
        $data += $this->shared;
        $content = $this->capture("$this->dir/$template.php", $data);
        return $layout === null ? $content : $this->capture("$this->dir/$layout.php", $data + ['content' => $content]);
    }

    /** @param array<string, mixed> $data */
    private function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
