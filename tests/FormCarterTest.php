<?php

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

require_once './vendor/autoload.php';
require_once './index.php';

class FormCarterTest extends TestCase
{
    /**
     * @var Swift_Mailer | MockObject
     */
    private $mailer;

    /**
     * @var FormCarter
     */
    private $formCarter;

    /**
     * @var array
     */
    private $config = [
        'host' => 'smtp.example.com',
        'port' => '465',
        'username' => 'john@example.com',
        'password' => 'qwerty!!!',
        'encryption' => 'ssl',
        'from_name' => 'John from Example.com',
        'from_address' => 'dev@example.com',
        'to' => ['contact@example.com']
    ];

    public function setUp(): void
    {
        $this->mailer = $this->getMockBuilder(Swift_Mailer::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->formCarter = new FormCarter($this->mailer, $this->config);
    }


    public function testThatMailerCallsSendMethod()
    {
        $data = [
            '_replyto' => 'validEmail@test.com',
        ];

        $this->mailer->expects($this->once())->method('send');
        $this->assertEquals('OK', $this->formCarter->run($data));
    }

    public function testThatInvalidEmailWillThrowFormCarterException()
    {
        $data = [
            '_replyto' => 'invalidEmail_test.com',
        ];

        $this->expectException(FormCarterException::class);
        $this->mailer->expects($this->never())->method('send');
        $this->assertNull('OK', $this->formCarter->run($data));
    }

    public function testThatInvalidEmailWillThrowException()
    {
        $data = [];

        $this->expectException(Exception::class);
        $this->mailer->expects($this->once())->method('send')->willThrowException(new Exception);
        $this->assertNull('OK', $this->formCarter->run($data));
    }

}
