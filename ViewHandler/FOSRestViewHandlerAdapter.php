<?php declare(strict_types=1);

namespace FOS\CommentBundle\ViewHandler;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class FOSRestViewHandlerAdapter implements ViewHandlerInterface
{
    private ViewHandlerInterface $decorated;

    private Environment $twig;

    private RequestStack $requestStack;

    public function __construct(ViewHandlerInterface $decorated, Environment $twig, RequestStack $requestStack)
    {
        $this->decorated = $decorated;
        $this->twig = $twig;
        $this->requestStack = $requestStack;
    }

    public function supports($format): bool
    {
        return $this->decorated->supports($format);
    }

    public function registerHandler($format, $callable): void
    {
        $this->decorated->registerHandler($format, $callable);
    }

    public function handle(View $view, Request $request = null): Response
    {
        $data = $view->getData();

        if ($request === null) {
            $request = $this->requestStack->getCurrentRequest();
        }

        if ('html' === ($view->getFormat() ?: $request->getRequestFormat()) && is_array($data)) {
            $template = $data['template'];
            $templateVar = $data['templateVar'] ?? 'data';
            $templateData = $data['templateData'] ?? [];
            $data = $data['data'];
            if ($data instanceof FormInterface) {
                $data = $data->createView();
            }
            $templateData[$templateVar] = $data;
            $response = $this->twig->render($template, $templateData);

            return new Response($response);
        }

        if (is_array($data)) {
            $view->setData($data['data'] ?? $data);
        }

        return $this->decorated->handle($view, $request);
    }

    public function createRedirectResponse(View $view, $location, $format): Response
    {
        return $this->decorated->createRedirectResponse($view, $location, $format);
    }

    public function createResponse(View $view, Request $request, $format): Response
    {
        return $this->decorated->createResponse($view, $request, $format);
    }
}