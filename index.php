<?php declare(strict_types=1);

require_once './vendor/autoload.php';

/**
 * Class PostmanPat
 */
class PostmanPat
{
    /**
     * @var array
     */
    private $smtp = [
        'host' => 'pro16.linuxpl.com',
        'port' => '465',
        'username' => 'dev@softwarethings.pro',
        'password' => 'b]N(s{fCmM4c',
        'encryption' => 'ssl',
        'from_name' => 'PostmanPat',
        'from_address' => 'dev@softwarethings.pro',
        'to' => 'kamiljedrkiewicz@gmail.com'
    ];

    /**
     * @var array
     */
    private $protected = [
        '_replyto',
        '_cc',
        '_bcc'
    ];

    /**
     * @var array
     */
    private $translation = [
        'first_name' => 'Imię',
        'last_name' => 'Nazwisko',
        'message' => 'Treść wiadmości'
    ];

    /**
     * @var array
     */
    private $parsed = [];

    /**
     * PostmanPat constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        if ($this->parse($data)) {
            $this->send();
        }
    }

    /**
     * @param string $value
     * @return array|string
     */
    private function validation(string $value)
    {
        $validated = [];
        foreach (explode(';', $value) as $email) {
            $email = trim($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return $email;
            $validated[] = $email;
        }
        return $validated;
    }

    /**
     * @param string $message
     * @param int $status
     * @return string
     */
    private function response(string $message = 'OK', int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode([
            'data' => [
                'message' => $message,
                'status' => $status
            ]
        ]);
    }

    /**
     * @param $data
     * @return bool
     */
    private function parse($data): bool
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->protected)) {
                $validated = $this->validation($value);
                if (is_string($validated)) {
                    echo $this->response('Invalid email address: ' . $validated, 422);
                }
                $this->parsed[$key] = $validated;
            } else {
                $this->parsed[$key] = $value;
            }
        }
        return true;
    }

    /**
     *
     */
    private function send(): void
    {
        $data = $this->parsed;

        try {
            if($this->smtp['host'] === '') {
                $transport = new Swift_SendmailTransport('/usr/sbin/sendmail -bs');
            } else {
                $transport = (new Swift_SmtpTransport($this->smtp['host'], $this->smtp['port'], $this->smtp['encryption']))
                    ->setUsername($this->smtp['username'])
                    ->setPassword($this->smtp['password']);
            }

            $swift = new Swift_Message();
            $swift->setSubject($data['_subject'] ?? 'Wiadomość z serwisu ' . $_SERVER['HTTP_HOST']);
            $swift->setFrom($this->smtp['from_address'], $this->smtp['from_name']);
            $swift->setTo($this->smtp['to']);
            $swift->setReplyTo($data['_replyto'] ?? []);
            $swift->setCc($data['_cc'] ?? []);
            $swift->setBcc($data['_bcc'] ?? []);

            foreach ($_FILES as $file) {
                $swift->attach(
                    Swift_Attachment::fromPath($file['tmp_name'])->setFilename($file['name'])
                );
            }

            $content = null;
            foreach ($data as $key => $value) {
                if ($key[0] === '_') continue;

                $content .= '<strong>' . ($this->translation[$key] ?? $key) . ':</strong> ' . $value . PHP_EOL;
            }

            $content .= PHP_EOL . '--' . PHP_EOL . 'Przesłano z adresu IP: ' . $_SERVER['REMOTE_ADDR'];

            $swift->setBody(nl2br($content), 'text/html');

            $mailer = new Swift_Mailer($transport);
            $mailer->send($swift);

            echo $this->response();
        } catch (Exception $exception) {
            echo $this->response($exception->getMessage(), 400);
        }
    }
}

$PostmanPat = new PostmanPat($_REQUEST);
