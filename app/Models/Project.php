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
                    window.addEventListener('load', reportHeight);
                    window.addEventListener('resize', reportHeight);
                    new ResizeObserver(reportHeight).observe(document.documentElement);
                    setTimeout(reportHeight, 300);
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
