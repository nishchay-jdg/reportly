<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'folder_id',
        'created_by',
        'name',
        'tag',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'folder_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->orderBy('sort_order');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProjectVersion::class)->latest();
    }

    /**
     * Combine the project's HTML/CSS/JS files into one renderable document.
     * Falls back to prepending/appending the CSS/JS when the HTML has no
     * <head>/<body> tags (e.g. bare snippets rather than full documents).
     *
     * When $reportHeight is true, a script is appended that posts the
     * rendered document's full height to the parent window. The share
     * viewer uses this to size the iframe to its content instead of
     * scrolling internally — comment pins are positioned as a percentage
     * of the iframe's box, so an internal scroll region would desync a
     * pin from the spot it was placed at as soon as that scroll resets.
     */
    public function renderPreviewHtml(bool $reportHeight = false): string
    {
        $html = $this->files->firstWhere('type', 'html')?->content ?? '';
        $css = $this->files->where('type', 'css')->pluck('content')->join("\n");
        $js = $this->files->where('type', 'js')->pluck('content')->join("\n");

        // Bridge scripts must land in <head> — they run before the body's own <script> tag,
        // so window.ReportlyAgreement exists by the time a template's on-load check runs.
        // Appending them after the body's script (as was done originally) meant the bridge
        // didn't exist yet when the template asked "has this already been signed?".
        $head = $reportHeight ? $this->bridgeScript() : '';

        $doc = str_contains($html, '</head>')
            ? str_replace('</head>', "<style>{$css}</style>{$head}</head>", $html)
            : "<style>{$css}</style>{$head}".$html;

        $doc = str_contains($doc, '</body>')
            ? str_replace('</body>', "<script>{$js}</script></body>", $doc)
            : $doc."<script>{$js}</script>";

        return $doc;
    }

    private function bridgeScript(): string
    {
        return <<<'HTML'
            <script>
                (function () {
                    function reportHeight() {
                        var height = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);
                        parent.postMessage({ reportToolHeight: height }, '*');
                    }

                    function elementKey(element, index) {
                        return element.id
                            || element.getAttribute('data-tab')
                            || element.getAttribute('data-target')
                            || element.getAttribute('aria-controls')
                            || element.getAttribute('href')
                            || String(index);
                    }

                    function isVisible(element) {
                        var style = window.getComputedStyle(element);
                        return !element.hidden && style.display !== 'none' && style.visibility !== 'hidden';
                    }

                    function reportContext() {
                        var parts = [];
                        var activeTabs = document.querySelectorAll(
                            '[role="tab"][aria-selected="true"], [role="tab"].active, .tab.active, .tabs button.active, .tab-button.active, .nav-tab.active, [data-tab].active'
                        );
                        activeTabs.forEach(function (element, index) {
                            parts.push('tab:' + elementKey(element, index));
                        });

                        var panels = document.querySelectorAll(
                            '[role="tabpanel"], section[id], .tab-content, .tab-pane, .tab-panel, .tab-section, .content-section, [data-tab-content]'
                        );
                        panels.forEach(function (element, index) {
                            if (isVisible(element)) parts.push('panel:' + elementKey(element, index));
                        });

                        if (window.location.hash) parts.push('hash:' + window.location.hash);

                        var context = parts.length ? parts.join('|').slice(0, 500) : 'default';
                        parent.postMessage({ reportlyContext: context }, '*');
                    }

                    var contextTimer;
                    function queueContextReport() {
                        clearTimeout(contextTimer);
                        contextTimer = setTimeout(reportContext, 0);
                    }

                    window.addEventListener('load', reportHeight);
                    window.addEventListener('resize', reportHeight);
                    window.addEventListener('hashchange', queueContextReport);
                    document.addEventListener('click', queueContextReport);
                    document.addEventListener('change', queueContextReport);
                    new ResizeObserver(reportHeight).observe(document.documentElement);
                    new MutationObserver(queueContextReport).observe(document.documentElement, {
                        subtree: true,
                        childList: true,
                        attributes: true,
                        attributeFilter: ['class', 'style', 'hidden', 'aria-selected'],
                    });
                    setTimeout(reportHeight, 300);
                    setTimeout(reportContext, 0);
                })();

                // Bridge for report content (running in a sandboxed, origin-less iframe)
                // to ask the parent page — which has the real session/cookies — to check
                // or persist an agreement signature via the backend.
                window.ReportlyAgreement = (function () {
                    var pending = {};
                    var nextId = 0;
                    function send(type, payload) {
                        return new Promise(function (resolve) {
                            var id = ++nextId;
                            pending[id] = resolve;
                            parent.postMessage({ reportlyAgreement: { id: id, type: type, payload: payload } }, '*');
                        });
                    }
                    window.addEventListener('message', function (e) {
                        var msg = e.data && e.data.reportlyAgreementResult;
                        if (msg && pending[msg.id]) {
                            pending[msg.id](msg.result);
                            delete pending[msg.id];
                        }
                    });
                    return {
                        check: function () { return send('check', {}); },
                        submit: function (payload) { return send('submit', payload); },
                    };
                })();
            </script>
            HTML;
    }
}
