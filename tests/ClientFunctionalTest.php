<?php

declare(strict_types=1);
use Http\Discovery\Psr17FactoryDiscovery;

/*
 * This file is part of the Nexylan packages.
 *
 * (c) Nexylan SAS <contact@nexylan.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Nexy\Slack\ActionConfirmation;
use Nexy\Slack\Attachment;
use Nexy\Slack\AttachmentAction;
use Nexy\Slack\AttachmentField;
use Nexy\Slack\Client;
use PHPUnit\Framework\TestCase;

class ClientFunctionalTest extends TestCase
{
    private \Http\Mock\Client $mockHttpClient;

    protected function setUp(): void
    {
        $this->mockHttpClient = new \Http\Mock\Client();
    }

    public function testPlainMessage(): void
    {
        $expectedHttpData = [
            'text'         => 'Message',
            'channel'      => '@regan',
            'username'     => 'Archer',
            'link_names'   => 0,
            'unfurl_links' => false,
            'unfurl_media' => true,
            'mrkdwn'       => true,
            'attachments'  => [],
        ];

        $client = new Client(
            $this->mockHttpClient,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
            'http://fake.endpoint',
            []
        );

        $message = $client->to('@regan')->from('Archer')->setText('Message');

        $client->sendMessage($message);

        $this->assertSame(
            $expectedHttpData,
            \json_decode((string) $this->mockHttpClient->getLastRequest()->getBody(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testStickyChannel(): void
    {
        $expectedHttpData = [
            'text'         => 'Message',
            'channel'      => '#default',
            'username'     => 'Archer',
            'link_names'   => 0,
            'unfurl_links' => false,
            'unfurl_media' => true,
            'mrkdwn'       => true,
            'attachments'  => [],
        ];

        $client = new Client(
            $this->mockHttpClient,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
            'http://fake.endpoint',
            [
                'channel'        => '#default',
                'sticky_channel' => true,
            ]
        );

        $message = $client->to('@regan')->from('Archer')->setText('Message');

        $client->sendMessage($message);

        $this->assertSame(
            $expectedHttpData,
            \json_decode((string) $this->mockHttpClient->getLastRequest()->getBody(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testMessageWithAttachments(): void
    {
        $now = new DateTime();

        $attachment = (new Attachment())
            ->setFallback('Some fallback text')
            ->setText('Some text to appear in the attachment')
            ->setColor('bad')
            ->setFooter('Footer')
            ->setFooterIcon('https://platform.slack-edge.com/img/default_application_icon.png')
            ->setTimestamp($now)
            ->setMarkdownFields(['pretext', 'text'])
            ->setImageUrl('http://fake.host/image.png')
            ->setThumbUrl('http://fake.host/image.png')
            ->setAuthorName('Joe Bloggs')
            ->setAuthorLink('http://fake.host/')
            ->setAuthorIcon('http://fake.host/image.png');

        $client = new Client(
            $this->mockHttpClient,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
            'http://fake.endpoint',
            [
                'username' => 'Test',
                'channel'  => '#general',
            ]
        );

        $message = $client->createMessage()->setText('Message');

        $message->attach($attachment);

        $client->sendMessage($message);

        // Subtle difference with timestamp
        $attachmentOutput = [
            'fallback'    => 'Some fallback text',
            'text'        => 'Some text to appear in the attachment',
            'pretext'     => null,
            'color'       => 'bad',
            'footer'      => 'Footer',
            'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
            'ts'          => $now->getTimestamp(),
            'mrkdwn_in'   => ['pretext', 'text'],
            'image_url'   => 'http://fake.host/image.png',
            'thumb_url'   => 'http://fake.host/image.png',
            'title'       => null,
            'title_link'  => null,
            'author_name' => 'Joe Bloggs',
            'author_link' => 'http://fake.host/',
            'author_icon' => 'http://fake.host/image.png',
            'fields'      => [],
            'actions'     => [],
        ];

        $expectedHttpData = [
            'text'         => 'Message',
            'channel'      => '#general',
            'username'     => 'Test',
            'link_names'   => 0,
            'unfurl_links' => false,
            'unfurl_media' => true,
            'mrkdwn'       => true,
            'attachments'  => [$attachmentOutput],
        ];

        $this->assertSame(
            $expectedHttpData,
            \json_decode((string) $this->mockHttpClient->getLastRequest()->getBody(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testMessageWithAttachmentsAndFields(): void
    {
        $now = new DateTime();

        $attachment = (new Attachment())
            ->setFallback('Some fallback text')
            ->setText('Some text to appear in the attachment')
            ->setColor('bad')
            ->setFooter('Footer')
            ->setFooterIcon('https://platform.slack-edge.com/img/default_application_icon.png')
            ->setTimestamp($now)
            ->setImageUrl('http://fake.host/image.png')
            ->setThumbUrl('http://fake.host/image.png')
            ->setTitle('A title')
            ->setTitleLink('http://fake.host/')
            ->setAuthorName('Joe Bloggs')
            ->setAuthorLink('http://fake.host/')
            ->setAuthorIcon('http://fake.host/image.png')
            ->setFields(
                [
                    (new AttachmentField('Field 1', 'Value 1')),
                    (new AttachmentField('Field 2', 'Value 2')),
                ]
            );

        $attachmentOutput = [
            'fallback'    => 'Some fallback text',
            'text'        => 'Some text to appear in the attachment',
            'pretext'     => null,
            'color'       => 'bad',
            'footer'      => 'Footer',
            'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
            'ts'          => $now->getTimestamp(),
            'mrkdwn_in'   => [],
            'image_url'   => 'http://fake.host/image.png',
            'thumb_url'   => 'http://fake.host/image.png',
            'title'       => 'A title',
            'title_link'  => 'http://fake.host/',
            'author_name' => 'Joe Bloggs',
            'author_link' => 'http://fake.host/',
            'author_icon' => 'http://fake.host/image.png',
            'fields'      => [
                [
                    'title' => 'Field 1',
                    'value' => 'Value 1',
                    'short' => false,
                ],
                [
                    'title' => 'Field 2',
                    'value' => 'Value 2',
                    'short' => false,
                ],
            ],
            'actions' => [],
        ];

        $client = new Client(
            $this->mockHttpClient,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
            'http://fake.endpoint',
            [
                'username' => 'Test',
                'channel'  => '#general',
            ]
        );

        $message = $client->createMessage()->setText('Message');

        $message->attach($attachment);

        $client->sendMessage($message);

        $expectedHttpData = [
            'text'         => 'Message',
            'channel'      => '#general',
            'username'     => 'Test',
            'link_names'   => 0,
            'unfurl_links' => false,
            'unfurl_media' => true,
            'mrkdwn'       => true,
            'attachments'  => [$attachmentOutput],
        ];

        $this->assertSame(
            $expectedHttpData,
            \json_decode((string) $this->mockHttpClient->getLastRequest()->getBody(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testMessageWithAttachmentsAndActions(): void
    {
        $now = new DateTime();

        $attachment = (new Attachment())
            ->setFallback('Some fallback text')
            ->setText('Some text to appear in the attachment')
            ->setColor('bad')
            ->setFooter('Footer')
            ->setFooterIcon('https://platform.slack-edge.com/img/default_application_icon.png')
            ->setTimestamp($now)
            ->setImageUrl('http://fake.host/image.png')
            ->setThumbUrl('http://fake.host/image.png')
            ->setTitle('A title')
            ->setTitleLink('http://fake.host/')
            ->setAuthorName('Joe Bloggs')
            ->setAuthorLink('http://fake.host/')
            ->setAuthorIcon('http://fake.host/image.png')
            ->setActions(
                [
                    (new AttachmentAction('Name 1', 'Text 1'))
                        ->setStyle('default')
                        ->setType('button')
                        ->setValue('Value 1')
                        ->setConfirm(
                            (new ActionConfirmation('Title 1', 'Text 1'))
                            ->setOkText('OK Text 1')
                            ->setDismissText('Dismiss Text 1')
                        ),
                    (new AttachmentAction('Name 2', 'Text 2'))
                        ->setStyle('default')
                        ->setType('button')
                        ->setValue('Value 2')
                        ->setConfirm(
                            (new ActionConfirmation('Title 2', 'Text 2'))
                            ->setOkText('OK Text 2')
                            ->setDismissText('Dismiss Text 2')
                        ),
                    (new AttachmentAction('Button Name 1', 'Button Label 1'))
                        ->setStyle('default')
                        ->setType('button')
                        ->setUrl('https://www.google.com'),
                ]
            );

        $attachmentOutput = [
            'fallback'    => 'Some fallback text',
            'text'        => 'Some text to appear in the attachment',
            'pretext'     => null,
            'color'       => 'bad',
            'footer'      => 'Footer',
            'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
            'ts'          => $now->getTimestamp(),
            'mrkdwn_in'   => [],
            'image_url'   => 'http://fake.host/image.png',
            'thumb_url'   => 'http://fake.host/image.png',
            'title'       => 'A title',
            'title_link'  => 'http://fake.host/',
            'author_name' => 'Joe Bloggs',
            'author_link' => 'http://fake.host/',
            'author_icon' => 'http://fake.host/image.png',
            'fields'      => [],
            'actions'     => [
                [
                    'text'    => 'Text 1',
                    'style'   => 'default',
                    'type'    => 'button',
                    'name'    => 'Name 1',
                    'value'   => 'Value 1',
                    'confirm' => [
                        'title'        => 'Title 1',
                        'text'         => 'Text 1',
                        'ok_text'      => 'OK Text 1',
                        'dismiss_text' => 'Dismiss Text 1',
                    ],
                ],
                [
                    'text'    => 'Text 2',
                    'style'   => 'default',
                    'type'    => 'button',
                    'name'    => 'Name 2',
                    'value'   => 'Value 2',
                    'confirm' => [
                        'title'        => 'Title 2',
                        'text'         => 'Text 2',
                        'ok_text'      => 'OK Text 2',
                        'dismiss_text' => 'Dismiss Text 2',
                    ],
                ],
                [
                    'text'  => 'Button Label 1',
                    'style' => 'default',
                    'type'  => 'button',
                    'url'   => 'https://www.google.com',
                ],
            ],
        ];

        $client = new Client(
            $this->mockHttpClient,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
            'http://fake.endpoint',
            [
                'username' => 'Test',
                'channel'  => '#general',
            ]
        );

        $message = $client->createMessage()->setText('Message');

        $message->attach($attachment);

        $client->sendMessage($message);

        $expectedHttpData = [
            'text'         => 'Message',
            'channel'      => '#general',
            'username'     => 'Test',
            'link_names'   => 0,
            'unfurl_links' => false,
            'unfurl_media' => true,
            'mrkdwn'       => true,
            'attachments'  => [$attachmentOutput],
        ];

        $this->assertSame(
            $expectedHttpData,
            \json_decode((string) $this->mockHttpClient->getLastRequest()->getBody(), true, 512, JSON_THROW_ON_ERROR)
        );
    }
}
