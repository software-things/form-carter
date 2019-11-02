![Form Carter Logo](https://softwarethings.pro/files/formcarter/formcarter-logo-color.png)

# FormCarter

FormCarter is a PHP library allowing people to easily handle sending emails. In details It's a wrapper on SwiftMailer which you can use on your website.

## Why should I use FormCarter?

- FormCarter is a time saver – you don't need to configure sending forms on every website you build.
- FormCarter will accept every field sent from frontend so you can configure once and forget about backend.
- FormCarter is frontend developer friendly.

## Configuration
Just open file config.php and edit following information:

### SMTP
```php
private $smtp = [
    'host' => 'smtp.example.com', // mail server aka. host | if you want to use PHP mail() function instead of SMTP, leave this empty
    'port' => '465', // port
    'username' => 'john@example.com', // mail user name
    'password' => 'qwerty!!!', // mail user password
    'encryption' => 'ssl', // encryption if is necessary or just null
    'from_name' => 'John from Example.com', // from who?
    'from_address' => 'dev@example.com', // company e-mail address, should be in thease same domain
    'to' => ['contact@example.com'] // recipient
];
```

### Translation

You can simple translate "programing-style" keys to human readable format in index.php file.

```php
private $translation = [
    'first_name' => 'First Name',
    'last_name' => 'Last Name',
    'message' => 'Message'
];
```

### reCAPTCHA

If you want to protect against spam, you could turn on captcha mechanism. 
Just edit $catpcha array within data from:

    https://www.google.com/recaptcha/admin

```php
'site_key' => '',
'secret' => '',
```

## How to use it?

To use FormCarter from frontend you just need to set proper action url (to main folder of FormCarter).

```html
<form action="https://example.com/form-carter" method="POST">
    <input type="text" name="name">
    <input type="email" name="_replyto">
    <input type="submit" value="Send">
</form>
```

You should also remember to add valid enctype attribute if you want to send files.
 
```
enctype="multipart/form-data"
```

## Google Invisible reCAPTCHA example on Frontend

```html
<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback" async defer></script>
<script>
var onloadCallback = function() {
    grecaptcha.execute();
};

function setResponse(response) { 
    document.getElementById('captcha-response').value = response; 
}
</script>
```

```html
<form action="https://example.com/form-carter" method="POST">
    <input type="text" name="name">
    <input type="email" name="_replyto">
    <div class="g-recaptcha" data-sitekey="" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
    <input type="hidden" id="captcha-response" name="captcha-response" />
    <input type="submit" value="Send">
</form>
```

## To run
First, install dependencies ```docker-compose run composer install```  
To start simple enviroment run  ```docker-compose up -d``` and set your smtp configuration to ```config.php```
Then you can send email by ```http://localhost:8080/?name=example_name&email=example_email```


## Running tests
Tests can be run with following command:
`vendor\bin\phpunit tests`
