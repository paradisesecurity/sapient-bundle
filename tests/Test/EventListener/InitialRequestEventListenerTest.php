<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Test\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ParadiseSecurity\Bundle\SapientBundle\Checker\BadStateCheckerInterface;
use ParadiseSecurity\Bundle\SapientBundle\Handler\StateHandlerInterface;
use ParadiseSecurity\Bundle\SapientBundle\Helper\HttpHelperInterface;
use ParadiseSecurity\Bundle\SapientBundle\Provider\ClientNameProviderInterface;
use ParadiseSecurity\Bundle\SapientBundle\Sapient\SapientInterface;
use Psr\Log\LoggerInterface;
use ParadiseSecurity\Bundle\SapientBundle\EventListener\InitialRequestEventListener;
use ParadiseSecurity\Bundle\GuzzleBundle\Event\PreTransactionEvent;
use GuzzleHttp\Psr7\Request;
use ParadiseSecurity\Bundle\SapientBundle\SapientHeaders;
use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;

class InitialRequestEventListenerTest extends TestCase
{
    protected SapientInterface&MockObject $sapient;
    protected BadStateCheckerInterface&MockObject $checker;
    protected StateHandlerInterface&MockObject $handler;
    protected ClientNameProviderInterface&MockObject $provider;
    protected HttpHelperInterface&MockObject $helper;
    protected LoggerInterface&MockObject $logger;

    public function setUp(): void
    {
        $this->sapient = $this->getMockBuilder(SapientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checker = $this->getMockBuilder(BadStateCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->handler = $this->getMockBuilder(StateHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = $this->getMockBuilder(ClientNameProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper = $this->getMockBuilder(HttpHelperInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testStateCanBeSetNewOnRequest()
    {
        $request = new Request(
            'GET',
            'https://localhost/api',
            [
                SapientHeaders::HEADER_STATE => PayloadInterface::STATE_WAITING
            ],
            'sample data'
        );
        $event = new PreTransactionEvent('test-client', $request);

        $clients = [
            'unique-server-id' => [
                'alias' => 'cool_api',
                'guzzle_alias' => 'test-client',
                'host' => 'localhost',
                'endpoints' => ['/api']
            ]
        ];
        $this->sapient->expects($this->once())->method('getClients')->willReturn($clients);

        $this->helper->expects($this->once())->method('getHost')->willReturn('localhost');
        $this->helper->expects($this->once())->method('getPath')->willReturn('/api');

        $this->checker->expects($this->once())->method('check')->willReturn(true);

        $this->handler->expects($this->once())->method('canCreate')->willReturn(true);
        $newRequest = $request->withHeader(
            SapientHeaders::HEADER_STATE,
            [PayloadInterface::STATE_NEW]
        );
        $this->handler->expects($this->once())->method('create')->willReturn($newRequest);

        $listener = new InitialRequestEventListener(
            $this->sapient,
            $this->checker,
            $this->handler,
            $this->provider,
            $this->helper,
            $this->logger
        );
        $listener->onPreTransaction($event);

        $modifiedRequest = $event->getTransaction();
        $state = $modifiedRequest->getHeader(SapientHeaders::HEADER_STATE);
        $this->assertSame([PayloadInterface::STATE_NEW], $state);
    }
}
