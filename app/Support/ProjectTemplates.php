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
            'agreement' => [
                'name' => 'Agreement / NDA',
                'description' => 'A signable agreement with a typed signature, date, and terms checkbox.',
            ],
        ];
    }

    public static function files(?string $key, string $projectName): array
    {
        $method = match ($key) {
            'seo-report' => 'seoReport',
            'pricing-page' => 'pricingPage',
            'proposal' => 'proposal',
            'agreement' => 'agreement',
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

    private static function agreement(string $name): array
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
                  <div class="card">
                    <h1>{$name}</h1>
                    <p class="intro">Replace this paragraph with the terms of the agreement or NDA the signer is accepting.</p>

                    <label for="full_name">Full Name</label>
                    <div class="field-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                      <input type="text" id="full_name" placeholder="Enter your full name" required>
                    </div>

                    <div class="row">
                      <div>
                        <label for="email">Email</label>
                        <div class="field-icon">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                          <input type="email" id="email" placeholder="Enter your email address" required>
                        </div>
                      </div>
                      <div>
                        <label for="company">Company Name</label>
                        <div class="field-icon">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l8-4v18M13 21V11l6 3v7M9 9v.01M9 12v.01M9 15v.01"/></svg>
                          <input type="text" id="company" placeholder="Enter your company name">
                        </div>
                      </div>
                    </div>

                    <label for="signature">Type Your Signature</label>
                    <input type="text" id="signature" class="signature" placeholder="" required>

                    <label for="signed_at">Date</label>
                    <div class="field-icon date-field">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                      <span id="signed_at"></span>
                    </div>

                    <hr>

                    <div class="footer-row">
                      <label class="agree">
                        <input type="checkbox" id="agree_terms">
                        I agree to the <a id="terms_link" href="#" target="_blank" rel="noopener">terms and conditions</a>
                      </label>
                      <button type="button" id="submit_btn">SUBMIT <span class="dot"></span></button>
                    </div>

                    <p id="confirmation" class="confirmation" hidden></p>
                  </div>

                  <script src="script.js"></script>
                </body>
                </html>

                HTML],
            ['filename' => 'style.css', 'type' => 'css', 'sort_order' => 1, 'content' => <<<'CSS'
                body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 60px 20px; display: flex; justify-content: center; }
                .card { background: #0b0b0d; color: #fff; max-width: 640px; width: 100%; border-radius: 16px; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,.25); box-sizing: border-box; }
                .card h1 { margin: 0 0 8px; font-size: 1.5rem; }
                .intro { color: #9ca3af; margin: 0 0 28px; line-height: 1.5; }
                label { display: block; font-weight: 600; margin: 20px 0 8px; }
                .row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
                .row > div { min-width: 0; }
                .field-icon { position: relative; }
                .field-icon svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #6b7280; }
                .field-icon input, .field-icon span { display: block; width: 100%; box-sizing: border-box; padding: 14px 14px 14px 44px; border-radius: 8px; border: 1px solid #27272a; background: #18181b; color: #fff; font-size: .95rem; }
                .field-icon input::placeholder { color: #6b7280; }
                .date-field span { color: #d1d5db; }
                .signature { width: 100%; box-sizing: border-box; padding: 24px 20px; border-radius: 8px; border: 2px solid #93c5fd; background: #fff; color: #111827; font-family: 'Brush Script MT', 'Segoe Script', cursive; font-size: 2.5rem; }
                .signature::placeholder { font-family: system-ui, sans-serif; font-size: 1rem; color: #9ca3af; }
                hr { border: none; border-top: 1px solid #27272a; margin: 32px 0 24px; }
                .footer-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
                .agree { display: flex; align-items: center; gap: 10px; font-weight: 400; margin: 0; }
                .agree input { width: 18px; height: 18px; }
                .agree a { color: #6366f1; text-decoration: none; }
                .agree a:hover { text-decoration: underline; }
                #submit_btn { background: #4f46e5; color: #fff; border: none; padding: 14px 28px; border-radius: 999px; font-weight: 700; letter-spacing: .03em; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
                #submit_btn:hover { background: #4338ca; }
                #submit_btn:disabled { opacity: .6; cursor: default; }
                #submit_btn .dot { width: 8px; height: 8px; border-radius: 50%; background: #fff; }
                .confirmation { margin-top: 20px; font-weight: 600; }
                CSS],
            ['filename' => 'script.js', 'type' => 'js', 'sort_order' => 2, 'content' => <<<'JS'
                // Set this to the terms and conditions page you want signers to read — any URL, including
                // a page on your own site.
                const TERMS_URL = "https://example.com/terms";

                const fullName = document.getElementById('full_name');
                const email = document.getElementById('email');
                const company = document.getElementById('company');
                const signature = document.getElementById('signature');
                const agreeTerms = document.getElementById('agree_terms');
                const submitBtn = document.getElementById('submit_btn');
                const confirmation = document.getElementById('confirmation');
                const signedAtEl = document.getElementById('signed_at');

                document.getElementById('terms_link').href = TERMS_URL;
                signedAtEl.textContent = new Date().toISOString().slice(0, 10);

                // Repopulates the fields from the saved record (not just a caption) so the
                // page still shows what was actually signed after a refresh — a visible
                // record to point to, not a blank locked form.
                function lockForm(state) {
                  fullName.value = state.full_name ?? fullName.value;
                  email.value = state.email ?? email.value;
                  if (state.company_name) company.value = state.company_name;
                  signature.value = state.signature_text ?? signature.value;
                  agreeTerms.checked = true;
                  [fullName, email, company, signature, agreeTerms, submitBtn].forEach(field => field.disabled = true);
                  confirmation.hidden = false;
                  confirmation.style.color = '#16a34a';
                  confirmation.textContent = `Signed by ${state.full_name} on ${state.signed_at}.`;
                }

                function showError(message) {
                  confirmation.hidden = false;
                  confirmation.style.color = '#f87171';
                  confirmation.textContent = message;
                }

                // Once someone has signed via this link, the agreement is locked — reloading
                // (even in a different tab) shows the same read-only receipt, it never resets.
                (async function checkAlreadySigned() {
                  if (!window.ReportlyAgreement) return;
                  const state = await window.ReportlyAgreement.check();
                  if (state.signed) lockForm(state);
                })();

                submitBtn.addEventListener('click', async () => {
                  const missing = [fullName, email, signature].filter(field => !field.value.trim());
                  [fullName, email, signature].forEach(field => field.style.borderColor = '');
                  missing.forEach(field => field.style.borderColor = '#ef4444');

                  if (missing.length || !agreeTerms.checked) {
                    showError(!agreeTerms.checked
                      ? 'Please agree to the terms and conditions before submitting.'
                      : 'Please fill in all required fields.');
                    return;
                  }

                  if (!window.ReportlyAgreement) {
                    // No share link yet (e.g. viewing inside the editor) — nothing to save to.
                    lockForm({
                      full_name: fullName.value.trim(),
                      email: email.value.trim(),
                      company_name: company.value.trim(),
                      signature_text: signature.value.trim(),
                      signed_at: signedAtEl.textContent + ' (preview only — publish a share link to save real signatures)',
                    });
                    return;
                  }

                  submitBtn.disabled = true;
                  const result = await window.ReportlyAgreement.submit({
                    full_name: fullName.value.trim(),
                    email: email.value.trim(),
                    company_name: company.value.trim(),
                    signature_text: signature.value.trim(),
                    terms_url: TERMS_URL,
                    agree_terms: agreeTerms.checked,
                  });

                  if (result.signed) {
                    lockForm(result);
                  } else {
                    submitBtn.disabled = false;
                    showError(result.error || 'Could not submit — please try again.');
                  }
                });
                JS],
        ];
    }
}
