<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use HTMLPurifier;
use HTMLPurifier_Config;

class Page extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_description',
        'meta_keywords',
        'is_published',
        'template',
        'custom_css',
        'custom_js',
        'featured_image',
        'client_identifier'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
            // Sanitize HTML content
            $page->content = static::sanitizeHtml($page->content);
        });

        static::updating(function ($page) {
            // Sanitize HTML content on update
            if ($page->isDirty('content')) {
                $page->content = static::sanitizeHtml($page->content);
            }
        });
    }

    /**
     * Sanitize HTML content using HTMLPurifier
     *
     * @param string $content
     * @return string
     */
    protected static function sanitizeHtml($content)
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,b,strong,i,em,u,a[href|title],ul,ol,li,br,span[style],img[src|alt|title|width|height],h1,h2,h3,h4,h5,h6,blockquote,pre,code,table,thead,tbody,tr,td,th');
        $config->set('CSS.AllowedProperties', 'font-weight,font-style,text-decoration,color,background-color,text-align');
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        
        // Set cache directory to Laravel storage directory
        $cacheDir = storage_path('app/htmlpurifier');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $config->set('Cache.DefinitionImpl', 'Serializer');
        $config->set('Cache.SerializerPath', $cacheDir);
        
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($content);
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Scope a query to only include published pages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
 