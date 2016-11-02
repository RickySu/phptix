<?php
namespace AppBundle\EventListener;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * @DI\Service()
 */
class CORSListener
{

    /** @var  array */
    protected $allowOrigin;

    /**
     * @DI\InjectParams({
     *    "allowOrigin" = @DI\Inject("%allow_origin%")
     * })
     */
    public function injectAllowOrigin($allowOrigin)
    {
        $this->allowOrigin = $allowOrigin;
    }

    /**
     * on request before router
     * @DI\Observe("kernel.request", priority=48)
     */
    public function onRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if(!$event->isMasterRequest()){
            return;
        }

        if(($origin = $request->headers->get('origin')) === null){
            return;
        }

        $originHost = $this->extractHost($origin);

        if($originHost == $request->headers->get('host')){
            return;
        }

        if(!in_array($originHost, $this->allowOrigin)){
            $event->setResponse(new Response('', Response::HTTP_FORBIDDEN));
            return;
        }

        if($request->getMethod() == Request::METHOD_OPTIONS){
            $event->setResponse($this->makeAccessControllAllowResponse(new Response(), $origin));
            return;
        }

        $request->attributes->set('_cors_allow', true);
    }

    /**
     * @DI\Observe("kernel.response")
     */
    public function onResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if(!$event->isMasterRequest()){
            return;
        }

        if(!$request->attributes->get('_cors_allow')){
            return;
        }

        $this->makeAccessControllAllowResponse($response, $request->headers->get('origin'));
    }

    protected function makeAccessControllAllowResponse(Response $response, $origin)
    {
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Authorization');
        $response->headers->set('Access-Control-Max-Age', 3600);
        return $response;
    }

    protected function extractHost($origin)
    {
        if(!preg_match('/^https?:\/\/(.*)/i', $origin, $match)){
            return null;
        }

        return strtolower($match[1]);
    }
}