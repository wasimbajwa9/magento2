<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cms\Test\Unit\Controller\Adminhtml\Wysiwyg;

use Magento\Backend\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Wysiwyg\Directive;
use Magento\Cms\Model\Template\Filter;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Image\Adapter\AdapterInterface;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Url\DecoderInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;

/**
 * @covers \Magento\Cms\Controller\Adminhtml\Wysiwyg\Directive
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DirectiveTest extends TestCase
{
    const IMAGE_PATH = 'pub/media/wysiwyg/image.jpg';

    /**
     * @var Directive
     */
    protected $wysiwygDirective;

    /**
     * @var Context|PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionContextMock;

    /**
     * @var RequestInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var DecoderInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlDecoderMock;

    /**
     * @var ObjectManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var Filter|PHPUnit_Framework_MockObject_MockObject
     */
    protected $templateFilterMock;

    /**
     * @var AdapterFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $imageAdapterFactoryMock;

    /**
     * @var AdapterInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $imageAdapterMock;

    /**
     * @var ResponseInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var File|PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileMock;

    /**
     * @var Config|PHPUnit_Framework_MockObject_MockObject
     */
    protected $wysiwygConfigMock;

    /**
     * @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var RawFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $rawFactoryMock;

    /**
     * @var Raw|PHPUnit_Framework_MockObject_MockObject
     */
    protected $rawMock;

    protected function setUp()
    {
        $this->actionContextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlDecoderMock = $this->getMockBuilder(DecoderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->templateFilterMock = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->imageAdapterFactoryMock = $this->getMockBuilder(AdapterFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->imageAdapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getMimeType',
                    'getColorAt',
                    'getImage',
                    'watermark',
                    'refreshImageDimensions',
                    'checkDependencies',
                    'createPngFromString',
                    'open',
                    'resize',
                    'crop',
                    'save',
                    'rotate'
                ]
            )
            ->getMock();
        $this->responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setHeader', 'setBody', 'sendResponse'])
            ->getMock();
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->setMethods(['fileGetContents'])
            ->getMock();
        $this->wysiwygConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rawFactoryMock = $this->getMockBuilder(RawFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rawMock = $this->getMockBuilder(Raw::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionContextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->actionContextMock->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);
        $this->actionContextMock->expects($this->any())
            ->method('getObjectManager')
            ->willReturn($this->objectManagerMock);

        $objectManager = new ObjectManager($this);
        $this->wysiwygDirective = $objectManager->getObject(
            Directive::class,
            [
                'context' => $this->actionContextMock,
                'urlDecoder' => $this->urlDecoderMock,
                'resultRawFactory' => $this->rawFactoryMock,
                'adapterFactory' => $this->imageAdapterFactoryMock,
                'logger' => $this->loggerMock,
                'config' => $this->wysiwygConfigMock,
                'filter' => $this->templateFilterMock,
                'file' => $this->fileMock,
            ]
        );
    }

    /**
     * @covers \Magento\Cms\Controller\Adminhtml\Wysiwyg\Directive::execute
     */
    public function testExecute()
    {
        $mimeType = 'image/jpeg';
        $imageBody = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $this->prepareExecuteTest();

        $this->imageAdapterMock->expects($this->once())
            ->method('open')
            ->with(self::IMAGE_PATH);
        $this->imageAdapterMock->expects($this->atLeastOnce())
            ->method('getMimeType')
            ->willReturn($mimeType);
        $this->rawMock->expects($this->atLeastOnce())
            ->method('setHeader')
            ->with('Content-Type', $mimeType)
            ->willReturnSelf();
        $this->rawMock->expects($this->atLeastOnce())
            ->method('setContents')
            ->with($imageBody)
            ->willReturnSelf();
        $this->imageAdapterMock->expects($this->once())
            ->method('getImage')
            ->willReturn($imageBody);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn($imageBody);
        $this->rawFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->rawMock);
        $this->imageAdapterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->imageAdapterMock);

        $this->assertSame(
            $this->rawMock,
            $this->wysiwygDirective->execute()
        );
    }

    /**
     * @covers \Magento\Cms\Controller\Adminhtml\Wysiwyg\Directive::execute
     */
    public function testExecuteException()
    {
        $exception = new \Exception('epic fail');
        $placeholderPath = 'pub/static/adminhtml/Magento/backend/en_US/Magento_Cms/images/wysiwyg_skin_image.png';
        $mimeType = 'image/png';
        $imageBody = '0123456789abcdefghijklmnopqrstuvwxyz';
        $this->prepareExecuteTest();

        $this->imageAdapterMock->expects($this->at(0))
            ->method('open')
            ->with(self::IMAGE_PATH)
            ->willThrowException($exception);
        $this->wysiwygConfigMock->expects($this->once())
            ->method('getSkinImagePlaceholderPath')
            ->willReturn($placeholderPath);
        $this->imageAdapterMock->expects($this->at(1))
            ->method('open')
            ->with($placeholderPath);
        $this->imageAdapterMock->expects($this->atLeastOnce())
            ->method('getMimeType')
            ->willReturn($mimeType);
        $this->rawMock->expects($this->atLeastOnce())
            ->method('setHeader')
            ->with('Content-Type', $mimeType)
            ->willReturnSelf();
        $this->rawMock->expects($this->atLeastOnce())
            ->method('setContents')
            ->with($imageBody)
            ->willReturnSelf();
        $this->imageAdapterMock->expects($this->any())
            ->method('getImage')
            ->willReturn($imageBody);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn($imageBody);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($exception);
        $this->rawFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->rawMock);

        $this->imageAdapterFactoryMock->expects($this->exactly(1))
            ->method('create')
            ->willReturn($this->imageAdapterMock);

        $this->assertSame(
            $this->rawMock,
            $this->wysiwygDirective->execute()
        );
    }

    protected function prepareExecuteTest()
    {
        $directiveParam = 'e3ttZWRpYSB1cmw9Ind5c2l3eWcvYnVubnkuanBnIn19';
        $directive = '{{media url="wysiwyg/image.jpg"}}';

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('___directive')
            ->willReturn($directiveParam);
        $this->urlDecoderMock->expects($this->once())
            ->method('decode')
            ->with($directiveParam)
            ->willReturn($directive);

        $this->templateFilterMock->expects($this->once())
            ->method('filter')
            ->with($directive)
            ->willReturn(self::IMAGE_PATH);

        $this->imageAdapterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->imageAdapterMock);
    }

    /**
     * Test Execute With Deleted Image
     *
     * @covers \Magento\Cms\Controller\Adminhtml\Wysiwyg\Directive::execute
     */
    public function testExecuteWithDeletedImage()
    {
        $exception = new \Exception('epic fail');
        $placeholderPath = 'pub/static/adminhtml/Magento/backend/en_US/Magento_Cms/images/wysiwyg_skin_image.png';
        $this->prepareExecuteTest();

        $this->imageAdapterMock->expects($this->any())
            ->method('open')
            ->with(self::IMAGE_PATH)
            ->willThrowException($exception);

        $this->wysiwygConfigMock->expects($this->once())
            ->method('getSkinImagePlaceholderPath')
            ->willReturn($placeholderPath);

        $this->imageAdapterMock->expects($this->any())
            ->method('open')
            ->with($placeholderPath)
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($exception);

        $this->rawMock->expects($this->once())
            ->method('setHeader')
            ->with('Content-Type', null)
            ->willReturnSelf();

        $this->rawMock->expects($this->once())
            ->method('setContents')
            ->with(null)
            ->willReturnSelf();

        $this->rawFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->rawMock);

        $this->wysiwygDirective->execute();
    }
}
