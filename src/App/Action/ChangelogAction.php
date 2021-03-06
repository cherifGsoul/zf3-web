<?php

namespace App\Action;

use App\Model\Changelog;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template;

class ChangelogAction implements MiddlewareInterface
{
    /** @var Changelog */
    private $changelog;

    /** @var Template\TemplateRendererInterface */
    private $template;

    public function __construct(Changelog $changelog, Template\TemplateRendererInterface $template)
    {
        $this->changelog = $changelog;
        $this->template  = $template;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $allChangelog = $this->changelog->getAll();
        $changelog = $request->getAttribute('changelog', basename(key($allChangelog), '.md'));

        $file = sprintf('data/changelog/%s.md', $changelog);
        if (! $changelog || ! file_exists($file)) {
            return new HtmlResponse($this->template->render('error::404'));
        }
        $content = $this->changelog->getFromFile($file);
        $content['layout'] = 'layout::default';
        $content['changelog'] = $changelog;
        $content['versions'] = array_map(function ($value) {
            return basename($value, '.md');
        }, array_keys($allChangelog));

        return new HtmlResponse($this->template->render('app::changelog', $content));
    }
}
