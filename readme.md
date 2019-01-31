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