<?php

namespace App\Support;

class ProjectTemplates
{
    /**
     * Template metadata for the "start from template" picker. Keep in the same order
     * they should appear in the UI, blank first.
     */
    public static function definitions(): array
    {
        return [
            'blank' => [
                'name' => 'Blank',
                'description' => 'An empty page you build from scratch.',
            ],
            'seo-report' => [
                'name' => 'SEO Report',
                'description' => 'KPI tiles, a ranking table, and a summary section.',
            ],
            'pricing-page' => [
                'name' => 'Pricing Page',
                'description' => 'A three-tier pricing table.',
            ],
            'proposal' => [
                'name' => 'Proposal',
                'description' => 'Cover page, scope, and timeline sections.',
            ],
        ];
    }

    public static function files(?string $key, string $projectName): array
    {
        $method = match ($key) {
            'seo-report' => 'seoReport',
            'pricing-page' => 'pricingPage',
            'proposal' => 'proposal',
            default => 'blank',
        };

        return static::$method($projectName);
    }

    private static function blank(string $name): array
    {
        return [
            ['filename' => 'index.html', 'type' => 'html', 'sort_order' => 0, 'content' => <<<HTML
                <!DOCTYPE html>
                <html lang="en">
                <head>
                  <meta charset="UTF-8">
                  <title>{$name}</title>
                  <link rel="stylesheet" href="style.css">
                </head>
                <body>
                  <h1>{$name}</h1>
                  <script src="script.js"></script>
                </body>
                </html>

                HTML],
            ['filename' => 'style.css', 'type' => 'css', 'sort_order' => 1, 'content' => "body {\n  font-family: sans-serif;\n}\n"],
            ['filename' => 'script.js', 'type' => 'js', 'sort_order' => 2, 'content' => "console.log('ready');\n"],
        ];
    }

    private static function seoReport(string $name): array
    {
        return [
            ['filename' => 'index.html', 'type' => 'html', 'sort_order' => 0, 'content' => <<<HTML
                <!DOCTYPE html>
                <html lang="en">
                <head>
                  <meta charset="UTF-8">
                  <title>{$name}</title>
                  <link rel="stylesheet" href="style.css">
                </head>
                <body>
                  <header>
                    <h1>{$name}</h1>
                    <p class="period">Reporting period: Month Year</p>
                  </header>

                  <section class="kpis">
                    <div class="kpi"><span class="value">12,480</span><span class="label">Organic sessions</span></div>
                    <div class="kpi"><span class="value">+18%</span><span class="label">MoM growth</span></div>
                    <div class="kpi"><span class="value">42</span><span class="label">Keywords in top 10</span></div>
                    <div class="kpi"><span class="value">3.2%</span><span class="label">Conversion rate</span></div>
                  </section>

                  <section>
                    <h2>Keyword rankings</h2>
                    <table>
                      <thead><tr><th>Keyword</th><th>Position</th><th>Change</th></tr></thead>
                      <tbody>
                        <tr><td>example keyword one</td><td>4</td><td class="up">+3</td></tr>
                        <tr><td>example keyword two</td><td>9</td><td class="up">+1</td></tr>
                        <tr><td>example keyword three</td><td>15</td><td class="down">-2</td></tr>
                      </tbody>
                    </table>
                  </section>

                  <section>
                    <h2>Summary</h2>
                    <p>Replace this with a short narrative of what happened this month and what's planned next.</p>
                  </section>

                  <script src="script.js"></script>
                </body>
                </html>

                HTML],
            ['filename' => 'style.css', 'type' => 'css', 'sort_order' => 1, 'content' => <<<'CSS'
                body { font-family: system-ui, sans-serif; max-width: 860px; margin: 0 auto; padding: 40px 20px; color: #1f2937; }
                header { margin-bottom: 32px; }
                .period { color: #6b7280; margin-top: 4px; }
                .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 40px; }
                .kpi { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 4px; }
                .kpi .value { font-size: 1.75rem; font-weight: 700; }
                .kpi .label { font-size: 0.85rem; color: #6b7280; }
                section { margin-bottom: 40px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
                td.up { color: #16a34a; }
                td.down { color: #dc2626; }
                CSS],
            ['filename' => 'script.js', 'type' => 'js', 'sort_order' => 2, 'content' => "console.log('ready');\n"],
        ];
    }

    private static function pricingPage(string $name): array
    {
        return [
            ['filename' => 'index.html', 'type' => 'html', 'sort_order' => 0, 'content' => <<<HTML
                <!DOCTYPE html>
                <html lang="en">
                <head>
                  <meta charset="UTF-8">
                  <title>{$name}</title>
                  <link rel="stylesheet" href="style.css">
                </head>
                <body>
                  <h1>{$name}</h1>
                  <div class="plans">
                    <div class="plan">
                      <h3>Starter</h3>
                      <p class="price">\$499<span>/mo</span></p>
                      <ul><li>Feature one</li><li>Feature two</li><li>Feature three</li></ul>
                    </div>
                    <div class="plan featured">
                      <h3>Growth</h3>
                      <p class="price">\$999<span>/mo</span></p>
                      <ul><li>Everything in Starter</li><li>Feature four</li><li>Feature five</li></ul>
                    </div>
                    <div class="plan">
                      <h3>Scale</h3>
                      <p class="price">\$1,999<span>/mo</span></p>
                      <ul><li>Everything in Growth</li><li>Feature six</li><li>Priority support</li></ul>
                    </div>
                  </div>
                  <script src="script.js"></script>
                </body>
                </html>

                HTML],
            ['filename' => 'style.css', 'type' => 'css', 'sort_order' => 1, 'content' => <<<'CSS'
                body { font-family: system-ui, sans-serif; max-width: 960px; margin: 0 auto; padding: 40px 20px; color: #1f2937; text-align: center; }
                .plans { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 32px; text-align: left; }
                .plan { border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; }
                .plan.featured { border-color: #4f46e5; box-shadow: 0 4px 20px rgba(79,70,229,0.15); }
                .price { font-size: 2rem; font-weight: 700; margin: 8px 0 16px; }
                .price span { font-size: 1rem; font-weight: 400; color: #6b7280; }
                ul { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 8px; }
                li::before { content: "✓ "; color: #16a34a; }
                CSS],
            ['filename' => 'script.js', 'type' => 'js', 'sort_order' => 2, 'content' => "console.log('ready');\n"],
        ];
    }

    private static function proposal(string $name): array
    {
        return [
            ['filename' => 'index.html', 'type' => 'html', 'sort_order' => 0, 'content' => <<<HTML
                <!DOCTYPE html>
                <html lang="en">
                <head>
                  <meta charset="UTF-8">
                  <title>{$name}</title>
                  <link rel="stylesheet" href="style.css">
                </head>
                <body>
                  <section class="cover">
                    <h1>{$name}</h1>
                    <p>Prepared for [Client Name] &middot; [Date]</p>
                  </section>

                  <section>
                    <h2>Scope of work</h2>
                    <p>Describe what's included in this engagement.</p>
                  </section>

                  <section>
                    <h2>Timeline</h2>
                    <ol>
                      <li>Week 1&ndash;2: Discovery</li>
                      <li>Week 3&ndash;6: Execution</li>
                      <li>Week 7: Review &amp; handoff</li>
                    </ol>
                  </section>

                  <section>
                    <h2>Investment</h2>
                    <p>Outline pricing here, or link to a pricing page.</p>
                  </section>

                  <script src="script.js"></script>
                </body>
                </html>

                HTML],
            ['filename' => 'style.css', 'type' => 'css', 'sort_order' => 1, 'content' => <<<'CSS'
                body { font-family: system-ui, sans-serif; max-width: 760px; margin: 0 auto; padding: 0 20px 60px; color: #1f2937; }
                .cover { padding: 80px 0 40px; text-align: center; border-bottom: 1px solid #e5e7eb; margin-bottom: 40px; }
                .cover h1 { font-size: 2.25rem; margin-bottom: 8px; }
                .cover p { color: #6b7280; }
                section { margin-bottom: 40px; }
                ol { padding-left: 20px; }
                ol li { margin-bottom: 6px; }
                CSS],
            ['filename' => 'script.js', 'type' => 'js', 'sort_order' => 2, 'content' => "console.log('ready');\n"],
        ];
    }
}
