# PostmanPat

### Configuration
Just open file index.php and edit following information:

#### SMTP
```php
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
```

#### Translation

You can simple translate "programing-style" keys to human readable format.

```php
private $translation = [
    'first_name' => 'First Name',
    'last_name' => 'Last Name',
    'message' => 'Message'
];
```

#### reCAPTCHA

If you want to protect against spam, you could turn on captcha mechanism. 
Just edit $catpcha array within data from:

    https://www.google.com/recaptcha/admin

```php
private $captcha = [
    'site_key' => '', // Site key
    'secret' => '' // Secret key
];
```

### How to use it?

Using is very simple and cleary. You just need to set proper action url (to main folder of PostmanPat).

```html
<form action="https://example.com/postman-pat" method="POST">
    <input type="text" name="name">
    <input type="email" name="_replyto">
    <input type="submit" value="Send">
</form>
```

You should also remember to add valid enctype attribute if you want to send files.
 
```
enctype="multipart/form-data"
```

#### Google Invisible reCAPTCHA

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
<form action="https://example.com/postman-pat" method="POST">
    <input type="text" name="name">
    <input type="email" name="_replyto">
    <div class="g-recaptcha" data-sitekey="" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
    <input type="hidden" id="captcha-response" name="captcha-response" />
    <input type="submit" value="Send">
</form>
```