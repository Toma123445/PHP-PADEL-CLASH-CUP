<?php



/**

 * Basic database credentials for the Smash Cup 5x5 project.

 * Adjust these constants to match your local MySQL instance.

 */

const DB_HOST = 'sql100.infinityfree.com';

const DB_PORT = 3306;

const DB_NAME = 'if0_40385331_php_padel';

const DB_USER = 'if0_40385331';

const DB_PASS = 'hzhz62uSoW2';



/**

 * Application level configuration values.

 */

const APP_URL = '/';

/**
 * reCAPTCHA Configuration
 * Obține cheile de la: https://www.google.com/recaptcha/admin
 * Pentru development, poți folosi cheile de test:
 * Site Key: 6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
 * Secret Key: 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
 */
const RECAPTCHA_SITE_KEY = '6LeyvTMsAAAAAMbDBpdjD7gdwPvVvwXxHyczP-QH';
const RECAPTCHA_SECRET_KEY = '6LeyvTMsAAAAAC68xwgYgUHvuapLNhlrxRr0RB2E';

/**
 * Security settings
 */
const SESSION_LIFETIME = 3600; // 1 ora
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCKOUT_TIME = 900; // 15 minute


