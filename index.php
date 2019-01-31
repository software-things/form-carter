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
        'host' => 'smtp.example.com', // mail server aka. host | if you want to use PHP mail() function instead SMTP, leave this empty
        'port' => '465', // port
        'username' => 'john@example.com', // mail user
        'password' => 'qwerty!!!', // mail user password
        'encryption' => 'ssl', // encryption if is necessary or just null
        'from_name' => 'John from Example.com', // from who?
        'from_address' => 'dev@example.com', // company e-mail address, should be in thease same domain
        'to' => 'contact@example.com' // recipient
    ];

    private $captcha = [
        'site_key' => '', // Site key
        'secret' => '' // Secret key
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

    private $errors = 'st-errors.txt';

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

        if (isset($data['captcha-response']) && !empty($data['captcha-response']) && !empty($this->captcha['site_key'])) {
            $verify = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $this->captcha['secret'] . '&response=' . $data['captcha-response']));
            if (!$verify->success) {
                exit($this->response('Invalid Captcha Code', 422));
            }
        }

        try {
            if ($this->smtp['host'] === '') {
                $transport = new Swift_SendmailTransport('/usr/sbin/sendmail -bs');
            } else {
                $transport = (new Swift_SmtpTransport($this->smtp['host'], $this->smtp['port'], $this->smtp['encryption']));
                $transport->setUsername($this->smtp['username']);
                $transport->setPassword($this->smtp['password']);
                $transport->setStreamOptions([
                    'ssl' => [
                        'allow_self_signed' => true,
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ]);
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

            $content .= PHP_EOL . '--' . PHP_EOL . $_SERVER['REMOTE_ADDR'];

            $swift->setBody(nl2br($content), 'text/html');

            $mailer = new Swift_Mailer($transport);
            $mailer->send($swift);

            echo $this->response();
        } catch (Exception $exception) {
            file_put_contents($this->errors, date('Y-m-d H:i:s') . ': ' . $exception->getMessage() . PHP_EOL, FILE_APPEND);

            echo $this->response($exception->getMessage(), 400);
        }
    }
}

$PostmanPat = new PostmanPat($_REQUEST);
