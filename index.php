<?php declare(strict_types=1);

require_once './vendor/autoload.php';

/**
 * Class FormCarter
 */
class FormCarter
{
    /**
     * @var array
     */
    private $config;

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
     * @var string
     */
    private $errors = 'st-errors.txt';

    /**
     * FormCarter constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->config = require_once 'config.php';

        $this->cors();

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
                    echo $this->response('Invalid email address: '. $validated, 422);
                    return false;
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

        if (isset($data['captcha-response']) && !empty($data['captcha-response']) && !empty($this->config['site_key'])) {
            $verify = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $this->config['secret'] . '&response=' . $data['captcha-response']));
            if (!$verify->success) {
                exit($this->response('Invalid Captcha Code', 422));
            }
        }

        try {
            if ($this->config['host'] === '') {
                $transport = new Swift_SendmailTransport('/usr/sbin/sendmail -bs');
            } else {
                $transport = (new Swift_SmtpTransport($this->config['host'], $this->config['port'], $this->config['encryption']));
                $transport->setUsername($this->config['username']);
                $transport->setPassword($this->config['password']);
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
            $swift->setFrom($this->config['from_address'], $this->config['from_name']);

            if (isset($data['to']) && preg_match('@^[a-zA-Z0-9/+]*={0,2}$@', $data['to'])) {
                $to = base64_decode($data['to']);
                if (in_array($to, $this->config['to'])) {
                    $swift->setTo($to);
                }
            }

            if ($swift->getTo() === null) {
                $swift->setTo($this->config['to'][0]);
            }

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

    /**
     *
     */
    private function cors(): void
    {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $origin = $_SERVER['HTTP_ORIGIN'];
            if (in_array($origin, $this->config['domains'])) {
                header('Access-Control-Allow-Origin: ' . $origin);
            }
        }
    }
}

$data = array_merge($_POST, $_GET);

if (array_filter($data)) {
    $FormCarter = new FormCarter($data);
}