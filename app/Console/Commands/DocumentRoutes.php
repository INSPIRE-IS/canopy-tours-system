<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class DocumentRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routes:doc {--markdown : Output as markdown file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate documentation of routes grouped by prefix and middleware';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'uri'        => $route->uri(),
                'methods'    => implode('|', $route->methods()),
                'name'       => $route->getName(),
                'action'     => $route->getActionName(),
                'middleware' => implode(', ', $route->gatherMiddleware()),
            ];
        });

        $grouped = $routes->groupBy(function ($route) {
            if (Str::startsWith($route['uri'], 'admin')) {
                return 'Admin (Backoffice)';
            } elseif (Str::startsWith($route['uri'], 'front')) {
                return 'Frontend (Customer)';
            }
            return 'General / Auth';
        });

        if ($this->option('markdown')) {
            $output = "# Route Documentation\n\n";
            foreach ($grouped as $section => $sectionRoutes) {
                $output .= "## {$section}\n\n";
                $output .= "| Method | URI | Name | Action | Middleware |\n";
                $output .= "|--------|-----|------|--------|-------------|\n";
                foreach ($sectionRoutes as $r) {
                    $output .= "| {$r['methods']} | `{$r['uri']}` | {$r['name']} | {$r['action']} | {$r['middleware']} |\n";
                }
                $output .= "\n";
            }

            $path = base_path('routes_doc.md');
            file_put_contents($path, $output);
            $this->info("✅ Routes documented in routes_doc.md");
        } else {
            foreach ($grouped as $section => $sectionRoutes) {
                $this->line("\n<fg=yellow>{$section}</>");
                foreach ($sectionRoutes as $r) {
                    $this->line(" - [{$r['methods']}] {$r['uri']} → {$r['action']} (name: {$r['name']})");
                }
            }
        }

        return self::SUCCESS;
    }
}
