<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class DocumentationController extends Controller
{
    protected $pages = [
        'getting-started' => [
            'title' => 'Getting Started',
            'file' => 'getting-started.md'
        ],
        'configuration' => [
            'title' => 'Configuration',
            'file' => 'configuration.md'
        ],
        'field-mapping' => [
            'title' => 'Field Mapping',
            'file' => 'field-mapping.md'
        ],
        'webhooks' => [
            'title' => 'Webhooks',
            'file' => 'webhooks.md'
        ],
        'troubleshooting' => [
            'title' => 'Troubleshooting',
            'file' => 'troubleshooting.md'
        ]
    ];

    public function index()
    {
        return $this->show('getting-started');
    }

    public function show($page)
    {
        if (!isset($this->pages[$page])) {
            abort(404);
        }

        $pageInfo = $this->pages[$page];
        $content = $this->getPageContent($pageInfo['file']);
        $title = __($pageInfo['title']);

        return view('mondayintegration::docs.index', compact('page', 'content', 'title'));
    }

    protected function getPageContent($file)
    {
        $path = module_path('MondayIntegration', 'Resources/docs/' . $file);
        
        if (!file_exists($path)) {
            return __('Documentation page not found.');
        }

        $content = file_get_contents($path);
        return $this->parseMarkdown($content);
    }

    protected function parseMarkdown($content)
    {
        // Convert headers
        $content = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $content);
        $content = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $content);
        $content = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $content);

        // Convert code blocks
        $content = preg_replace('/```(.*?)\n(.*?)```/s', '<pre><code>$2</code></pre>', $content);

        // Convert inline code
        $content = preg_replace('/`(.*?)`/', '<code>$1</code>', $content);

        // Convert lists
        $content = preg_replace('/^\* (.*?)$/m', '<li>$1</li>', $content);
        $content = preg_replace('/(<li>.*?<\/li>\n)+/', "<ul>\n$0</ul>\n", $content);

        // Convert numbered lists
        $content = preg_replace('/^\d+\. (.*?)$/m', '<li>$1</li>', $content);
        $content = preg_replace('/(<li>.*?<\/li>\n)+/', "<ol>\n$0</ol>\n", $content);

        // Convert links
        $content = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $content);

        // Convert emphasis
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);

        // Convert blockquotes
        $content = preg_replace('/^> (.*?)$/m', '<blockquote>$1</blockquote>', $content);

        // Convert paragraphs
        $content = preg_replace('/^(?!<[a-z])(.*?)$/m', '<p>$1</p>', $content);

        return $content;
    }
}
