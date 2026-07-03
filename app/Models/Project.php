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

        $doc = str_contains($html, '</head>')
            ? str_replace('</head>', "<style>{$css}</style></head>", $html)
            : "<style>{$css}</style>".$html;

        $doc = str_contains($doc, '</body>')
            ? str_replace('</body>', "<script>{$js}</script></body>", $doc)
            : $doc."<script>{$js}</script>";

        if ($reportHeight) {
            $doc .= <<<'HTML'
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
                </script>
                HTML;
        }

        return $doc;
    }
}
