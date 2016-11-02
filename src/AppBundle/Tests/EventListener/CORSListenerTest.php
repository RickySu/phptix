<?php
namespace AppBundle\Tests\EventListener;

use AppBundle\EventListener\CORSListener;
use AppBundle\Tests\BaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CORSListenerTest extends BaseTestCase
{
    public function test_injectAllowOrigin()
    {
        //arrange
        $allowOrigin = array(
            'example.com',
            'test.com',
        );
        $listener = $this->getMockBuilder(CORSListener::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        //act
        $listener->injectAllowOrigin($allowOrigin);

        //assert
        $this->assertEquals($allowOrigin, $this->getObjectAttribute($listener, 'allowOrigin'));
    }

    public function test_onRequest_if_request_with_origin_and_same_host_then_should_not_do_anything()
    {
        //arrange
        $host = 'badsite.com';
        $origin = 'http://' . $host;
        $allowOrigin = array(
            'example.com',
            'test.com',
        );

        $request = new Request();
        $request->setMethod(Request::METHOD_OPTIONS);
        $request->headers->set('origin', $origin);
        $request->headers->set('host', $host);

        $kernel = $this->getMockForAbstractClass(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener = $this->getMockBuilder(CORSListener::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->setObjectAttribute($listener, 'allowOrigin', $allowOrigin);

        //act
        $listener->onRequest($event);
        $response = $event->getResponse();

        //assert
        $this->assertNull($response);
    }

    public function test_onRequest_if_request_with_invalid_origin_then_should_return_403_response()
    {
        //arrange
        $allowOrigin = array(
            'example.com',
            'test.com',
        );

        $request = new Request();
        $request->setMethod(Request::METHOD_OPTIONS);
        $request->headers->set('origin', 'http://badsite.com');

        $kernel = $this->getMockForAbstractClass(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener = $this->getMockBuilder(CORSListener::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->setObjectAttribute($listener, 'allowOrigin', $allowOrigin);

        //act
        $listener->onRequest($event);
        $response = $event->getResponse();

        //assert
        $this->assertNotNull($response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test_onRequest_if_request_methods_is_options_and_with_valid_origin_then_should_return_cors_allow_response()
    {
        //arrange
        $origin = 'http://example.com';
        $allowOrigin = array(
            'example.com',
            'test.com',
        );

        $request = new Request();
        $request->setMethod(Request::METHOD_OPTIONS);
        $request->headers->set('origin', $origin);

        $kernel = $this->getMockForAbstractClass(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener = $this->getMockBuilder(CORSListener::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->setObjectAttribute($listener, 'allowOrigin', $allowOrigin);

        //act
        $listener->onRequest($event);
        $response = $event->getResponse();

        //assert
        $this->assertNotNull($response);
        $this->assertEquals($origin, $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, POST, PUT, DELETE, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals('X-Requested-With, Content-Type, Accept, Authorization', $response->headers->get('Access-Control-Allow-Headers'));
        $this->assertEquals('3600', $response->headers->get('Access-Control-Max-Age'));
    }

    public function test_onRequest_if_request_methods_is_not_options_and_with_valid_origin_then_should_return_cors_allow_response()
    {
        //arrange
        $origin = 'http://example.com';
        $allowOrigin = array(
            'example.com',
            'test.com',
        );

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $request->headers->set('origin', $origin);

        $kernel = $this->getMockForAbstractClass(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener = $this->getMockBuilder(CORSListener::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->setObjectAttribute($listener, 'allowOrigin', $allowOrigin);

        //act
        $listener->onRequest($event);
        $response = $event->getResponse();

        //assert
        $this->assertNull($response);
        $this->assertTrue($request->attributes->get('_cors_allow'));
    }

    public function test_onRequest_if_request_is_not_master_request_should_not_do_anything()
    {
        //arrange
        $origin = 'http://example.com';
        $allowOrigin = array(
            'example.com',
            'test.com',
        );

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $request->headers->set('origin', $origin);

        $kernel = $this->getMockForAbstractClass(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);
        $listener = $this->getMockBuilder(CORSListener::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->setObjectAttribute($listener, 'allowOrigin', $allowOrigin);

        //act
        $listener->onRequest($event);
        $response = $event->getResponse();

        //assert
        $this->assertNull($response);
        $this->assertNull($request->attributes->get('_cors_allow'));
    }

    public function test_onResponse_if_request_has_no_cors_allow_response_should_not_do_anything()
    {
        //arrange
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $response = new Response();
        $kernel = $this->getMockForAbstractClass(KernelInterface::class);
        $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
        $listener = $this->getMockBuilder(CORSListener::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        //act
        $listener->onResponse($event);

        //assert
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNull($response->headers->get('Access-Control-Allow-Methods'));
        $this->assertNull($response->headers->get('Access-Control-Allow-Headers'));
        $this->assertNull($response->headers->get('Access-Control-Max-Age'));
    }

    public function test_onResponse_if_request_has_cors_allow_response_should_add_cors_allow_response_headers()
    {
        //arrange
        $origin = 'http://example.com';
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $request->headers->set('origin', $origin);
        $request->attributes->set('_cors_allow', true);

        $response = new Response();
        $kernel = $this->getMockForAbstractClass(KernelInterface::class);
        $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
        $listener = $this->getMockBuilder(CORSListener::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        //act
        $listener->onResponse($event);

        //assert
        $this->assertEquals($origin, $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, POST, PUT, DELETE, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals('X-Requested-With, Content-Type, Accept, Authorization', $response->headers->get('Access-Control-Allow-Headers'));
        $this->assertEquals('3600', $response->headers->get('Access-Control-Max-Age'));
    }

    public function test_onResponse_if_response_is_not_master_request_should_not_do_anything()
    {
        //arrange
        $origin = 'http://example.com';
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $request->headers->set('origin', $origin);
        $request->attributes->set('_cors_allow', true);

        $response = new Response();
        $kernel = $this->getMockForAbstractClass(KernelInterface::class);
        $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);
        $listener = $this->getMockBuilder(CORSListener::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        //act
        $listener->onResponse($event);

        //assert
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNull($response->headers->get('Access-Control-Allow-Methods'));
        $this->assertNull($response->headers->get('Access-Control-Allow-Headers'));
        $this->assertNull($response->headers->get('Access-Control-Max-Age'));
    }
}