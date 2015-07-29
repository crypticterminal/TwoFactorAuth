TwoFactorAuth
============

Author: Arno0x0x - [@Arno0x0x](http://twitter.com/Arno0x0x)

TwoFactorAuth is a web authentication portal providing two factor authentication (*2FA*). The first factor is a password, the second factor is an OTP (*One Time Password*) generated by an application like Google Authenticator or anything compatible.

TwoFactorAuth is written in PHP and should be pretty easy to integrate with an existing PHP application. It also features a Nginx auth_request module compatible script that integrates easily. See below for Nginx integration.

The aim of TwoFactorAuth is to provide a secure (2FA) authentication of users and, once authentication is passed, let your application handle everything else such as user's authorisations, profile etc...

The app is distributed under the terms of the [GPLv3 licence](http://www.gnu.org/copyleft/gpl.html).


Dependencies
----------------

TwoFactorAuth requires PHP5. It also relies on the following libraries:

- The [Google Authenticator PHP Class](https://github.com/PHPGangsta/GoogleAuthenticator) writen by Michael Kliewe, to generate GAuth secret and OTP validation. **Beware** that I've modified this class to rely on a local library for QRCode generation rather than GoogleChart URL, so don't install the source library, use the one provided with TwoFactorAuth only.

- The [PHP QRCode library](http://phpqrcode.sourceforge.net/) written by Dominik Dzienia, for 2D QRCode generation.

**Both these libraries are included** in the TwoFactorAuth package so you don't have to install them :-)

TwoFactorAuth also relies on some PHP5 libraries that you'll have to install on your own:

- The GD library (on debian like systems: sudo apt-get install php5-gd)
- The SQLite3 library (on debian like systems: sudo apt-get install php5-sqlite)

Features
-----------

TwoFactorAuth uses a SQLite3 database for its users database. The database type can be easily changed by overiding the **/twofactorauth/lib/DBManager.php** class to use any other database.

Main features are :

- User's database management (restricted to users with the "admin" privilege) : add a user / delete a user / delete the whole database / change any user's password / renew any user's GAuth secret / show any user's secret as a QRCode
- Each user can also manage his own account : change his password / renew his GAuth secret  / show his current secret as a QRCode
- Nginx auth_request module integration (*optionnal*)

Screenshots
-----------
The login page :

![login page](http://i.imgur.com/9SBEgMV.jpg)

The user management page :

![user page](http://i.imgur.com/DXyWGiL.jpg)

The QRCode display :

![qrcode page](http://i.imgur.com/Jm6OhXl.jpg)

The administration page :

![admin page](http://i.imgur.com/ivF0hRf.jpg)

Adding a user :

![addUser page](http://i.imgur.com/TwzUSvl.jpg)

How does it work ?
-----------------

After a user is created, a QRCode is displayed representing the random GAuth secret generated for this user. This QRCode must be scanned with the Google Authenticator application. This should be done only once for each user, unless the user lost/changed his phone and needs to re-enter his QRCode.
Once a QRCode has been scanned with the Google Authenticator application, a OTP token is being generated every 30s:

![qrcode](http://i.imgur.com/fJgQwZT.jpg)

This token must be entered on the login page along with the user's password:

Once a user has logged in, a PHP session is created, which name can be configured (*optionnal*) to match the one of your own PHP application if required. This session holds the following variables:

- $_SESSION["authenticated"] : a boolean (true or false) indicating that the user has been successfully authenticated
- $_SESSION["isAdmin"] : a boolean (true or false) indicating whether or not this use has TwoFactorAuth admin rights 
- $_SESSION["username"] :  a string containing the authenticated username. This username can be reused by your own app for further authorization checks and profile handling


Installation
------------
1. Unzip the TwoFactorAuth package in your web server's directory and ensure all files and folders have appropriate user:group ownership, depending on your installation (*might be something like www-data:www-data*).

2. **Edit the configuration file /twofactorauth/config.php** and make it match your needs and personnal settings. See the configuration section below.

3. Next, navigate to the install.php page (*exact path will vary depending on where you installed the TwoFactorAuth application*) :
http://www.exemple.com/twofactorauth/admin/install.php . This page will create the SQLite3 user database and the user table schema. It will also create a first "admin" account, with password "AdminAdmin" and will display a corresponding QRCode to scan. Feel free to either delete this admin account once you created your own administrator's account, or at least **change its password** !

From that point, the main features are available at these page:

- Login page : http://www.exemple.com/twofactorauth/login/login.php

- Global administration page : http://www.exemple.com/twofactorauth/admin/admin.php

- Per user administration page : http://www.exemple.com/twofactorauth/user/user.php

Configuration
--------------
Edit the **/twofactorauth/config.php** file to match your needs. Most settings can be kept to their default values. However, pay attention to the following settings :

- **QRCODE_TITLE** : This is the title that will appear on top of the OTP token in the Google Athenticator app. Set it to your own application name, or maybe server name, whatever relevant and sensible to your users

- **SESSION_NAME** : This is the PHP session name (*also used for the session cookie*). You can set it to your own application session name if you plan to re-use it for further user authorization and profile

- **AUTH\_SUCCEED\_REDIRECT\_URL** : The login page supports a URL parameter "from" (*ex: "http://www.exemple.com/twofactorauth/login/login.php?from=/myapp"*). Upon successful login, the login page will redirect the user to the path specified in the "from" parameter (*NB: it can only be a path local to the FQDN, no cross-site*). However, if the "from" parameter is not present in the URL, the login page will redirect the user to the URL specified in AUTH\_SUCCEED\_REDIRECT\_URL


[OPTIONNAL] NGINX auth_request integration
---------------------
The Nginx auth_request module allows authentication of each request against an internal subrequest specified as a URL. The subrequest must answer with the proper HTTP status code:

- HTTP 401 if the authentication failed
- HTTP 200 if the authentication succeeded

This mechanism is a perfect replacement for the auth_basic authentication and allows for custom made mechanism, written in any language. It also allows a whole website (not per application) authentication mechanism.

TwoFactorAuth provides such a script: **/twofactorauth/nginx/auth.php**.

You'll have to edit your Nginx configuration file. Assuming the TwoFactorAuth application was deployed in a location named /twofactorauth/ on your webserver, add the following line under the "server" directive:


    auth_request /twofactorauth/nginx/auth.php;

    error_page 401 = @error401;
 
    location @error401 {
		return 302 $scheme://$host/twofactorauth/login/login.php?from=$uri;
    }

    location = /twofactorauth/nginx/auth.php {
                fastcgi_pass unix:/var/run/php5-fpm.sock;
                include fastcgi.conf;
                fastcgi_param  CONTENT_LENGTH "";
    }
 
    location /twofactorauth/db/ {
				deny all;
	}
	
    location /twofactorauth/login/ {
		auth_request off;

		location ~ \.php$ {
				fastcgi_pass unix:/var/run/php5-fpm.sock;
				include fastcgi.conf;
		}
    }

Credits
--------
Many thanks to Dominique Climenti ([kyos.ch](http://kyos.ch))for his help fixing few bugs (*installation procedure, cookie setting when server is run on a non-standard port, login form security improvement*) as well as discovering an XSS vulnerability (!). This is now all fixed.

Todo
--------
Although I already had some useful feedback and fixed some bugs, there might still be some bugs or security concerns to fix. If you have a feature request, feel free to contact me on my twitter page.