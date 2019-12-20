<?php declare(strict_types=1);

require_once './vendor/autoload.php';

/**
 * Class FormCarterException
 */
class FormCarterException extends Exception
{
}

/**
 * Class FormCarter
 */
class FormCarter
{
    /**
     * @var Swift_Mailer
     */
    private $mailer;

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
     * FormCarter constructor.
     * @param Swift_Mailer $mailer
     * @param array $config
     */
    public function __construct(Swift_Mailer $mailer, array $config)
    {
        $this->mailer = $mailer;
        $this->config = $config;
    }

    /**
     * @param $data
     * @return string
     * @throws FormCarterException
     */
    public function run($data)
    {
        $this->cors();

        $this->parse($data);
        return $this->send();
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
     * @param $data
     * @throws FormCarterException
     */
    private function parse($data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->protected)) {
                $validated = $this->validation($value);
                if (is_string($validated)) {
                    throw new FormCarterException('Invalid email address: ' . $validated, 422);
                }
                $this->parsed[$key] = $validated;
            } else {
                $this->parsed[$key] = $value;
            }
        }
    }

    /**
     * @return string
     * @throws FormCarterException
     */
    private function send(): string
    {
        $data = $this->parsed;

        if (isset($data['captcha-response']) && !empty($data['captcha-response']) && !empty($this->config['site_key'])) {
            $verify = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $this->config['secret'] . '&response=' . $data['captcha-response']));
            if (!$verify->success) {
                throw new FormCarterException('Invalid Captcha Code', 422);
            }
        }

        $swift = new Swift_Message();
        $swift->setSubject($data['_subject'] ?? $this->config['default_title'] . ($_SERVER['HTTP_HOST'] ?? ''));
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

        $content .= PHP_EOL . '--' . PHP_EOL . ($_SERVER['REMOTE_ADDR'] ?? '');

        $swift->setBody(nl2br($content), 'text/html');

        $this->mailer->send($swift);

        return 'OK';
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
        } else {
            header('Access-Control-Allow-Origin: *');
        }
    }
}


$data = array_merge($_POST, $_GET);

if (array_filter($data)) {

    $config = require_once 'config.php';
    if ($config['host'] === '') {
        $transport = new Swift_SendmailTransport('/usr/sbin/sendmail -bs');
    } else {
        $transport = (new Swift_SmtpTransport($config['host'], $config['port'], $config['encryption']));
        $transport->setUsername($config['username']);
        $transport->setPassword($config['password']);
        $transport->setStreamOptions([
            'ssl' => [
                'allow_self_signed' => true,
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
    }

    $mailer = new Swift_Mailer($transport);
    $FormCarter = new FormCarter($mailer, $config);
    $status = 200;

    try {
        $message = $FormCarter->run($data);
    } catch (FormCarterException $e) {
        file_put_contents('st-errors.txt', date('Y-m-d H:i:s') . ': ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        $message = $e->getMessage();
        $status = $e->getCode() !== 0 ? $e->getCode() : 400;
    } catch (Exception $e) {
        file_put_contents('st-errors.txt', date('Y-m-d H:i:s') . ': ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        $message = 'Unexpected error occurred. Check log file for details.';
        $status = 500;
    }

    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode([
        'data' => [
            'message' => $message,
            'status' => $status
        ]
    ]);
}