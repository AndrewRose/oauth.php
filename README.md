
## oauth.php

 This little bit of code has one only one aim in life - to allow you to put links to identity providers Google/Microsoft on your login page which allows users to confirm their email address (identity) with a simple click and redirect to your app where if you trust Google/Microsoft you can login the user with the specified email address immediately with no requirement for a username/password, magic.

Currently only Google/Microsoft identify providers are configured but you shouldn't have any issues adding more to oauth.ini.

To install put oauth.php into your web root, configure oauth.ini and place OUTSIDE of your web root with your client id/secrets (see instructions below to obtain) and on your login page embed the login links, you can do this by visiting oauth.php directly and copying the html links and pasting directly onto your login page, or include them directly using jQuery i.e.

```ini
<div id="sso"></div>  
<script type="text/javascript">
    jQuery('#sso').load('oauth.php');
</script>
```

After the user clicks the link to their identity provider, the provider will authentic them (hopefully) and redirect back to oauth.php with the users email address which can then be used in your own login process i.e.

```php
session_start();
$_SESSION['ssoEmail'] = $data[$settings[$idp]['emailIdentifier']];
header('Location: '.$settings['default']['uri']);
session_write_close();
```

The above sets a session variable ssoEmail and then redirects the user to the main application that checks for this session variable and if set will log the user in.  You will need to edit the above code in ouath.php if your requirements are more advanced / different.

To register for Google SSO go to https://console.cloud.google.com and on the left select Credentials -> Create Credentials -> OAuth client ID.  Application type is a Web Application and the Redirect URI will need to point to oauth.php in your web root i.e. https://your.comain/oauth.php.

To register for Microsoft SSO goto https://portal.azure.com and Azure Active Director -> App Registrations.  Make sure the redirect URI points to oauth.php in your web root i.e. https://your.comain/oauth.php

Contributions more than welcome!
