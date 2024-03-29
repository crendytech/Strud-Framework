<?php

namespace Strud\Helper\Authentication;

use Strud\Database\Throwable\Error;
use Strud\Database\Connection;
use Strud\Database\Expression\Comparision;
use Strud\Database\Expression\Criteria;
use Strud\Database\Throwable\IntegrityConstraintViolationException;
use Strud\Registry;
use Strud\Request;
use Strud\Route\Component\Controller;

require_once __DIR__ . '/Exceptions.php';

/** Component that provides all features and utilities for secure authentication of individual users */
final class Authentication extends UserManager{

	const SESSION_FIELD_LOGGED_IN = 'auth_logged_in';
	const SESSION_FIELD_USER_ID = 'auth_user_id';
	const SESSION_FIELD_EMAIL = 'auth_email';
	const SESSION_FIELD_USERNAME = 'auth_username';
	const SESSION_FIELD_STATUS = 'auth_status';
	const SESSION_FIELD_REMEMBERED = 'auth_remembered';
	const COOKIE_CONTENT_SEPARATOR = '~';
	const COOKIE_NAME_REMEMBER = 'auth_remember';
	const IP_ADDRESS_HASH_ALGORITHM = 'sha256';
	const HTTP_STATUS_CODE_TOO_MANY_REQUESTS = 429;

	/** @var boolean whether HTTPS (TLS/SSL) will be used (recommended) */
	private $useHttps;
    /** @var Registry - Stores the instance of cookies and session */
    private $registry;
	/** @var boolean whether cookies should be accessible via client-side scripts (*not* recommended) */
	private $allowCookiesScriptAccess;
	/** @var string the user's current IP address */
	private $ipAddress;
	/** @var int the number of actions allowed (in throttling) per time bucket */
	private $throttlingActionsPerTimeBucket;
	/** @var int the size of the time buckets (used for throttling) in seconds */
	private $throttlingTimeBucketSize;

    /**
     * @var Authentication
     */
    private static $instance;

    protected $connection;

    public static function getInstance(Connection $connection)
    {
        if(!static::$instance)
        {
            static::$instance = new static($connection);
        }

        return static::$instance;
    }

	/**
	 * @param PdoDatabase|PdoDsn|\PDO $databaseConnection the database connection to operate on
	 * @param bool $useHttps whether HTTPS (TLS/SSL) will be used (recommended)
	 * @param bool $allowCookiesScriptAccess whether cookies should be accessible via client-side scripts (*not* recommended)
	 * @param string $ipAddress the IP address that should be used instead of the default setting (if any), e.g. when behind a proxy
	 */
	public function __construct(Connection $connection, $useHttps = false, $allowCookiesScriptAccess = false, $ipAddress = null) {
		parent::__construct($connection);

		$this->useHttps = $useHttps;
		$this->allowCookiesScriptAccess = $allowCookiesScriptAccess;
		$this->ipAddress = empty($ipAddress) ? $_SERVER['REMOTE_ADDR'] : $ipAddress;
		$this->throttlingActionsPerTimeBucket = 20;
		$this->throttlingTimeBucketSize = 3600;
		$this->registry = Registry::getInstance();

		$this->initSession();
		$this->enhanceHttpSecurity();

		$this->processRememberDirective();
	}

	/** Initializes the session and sets the correct configuration */
	private function initSession() {
        // get our cookie settings
		$params = $this->createCookieSettings();
		// define our new cookie settings
//		session_set_cookie_params($params['lifetime'], $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	}

	/** Improves the application's security over HTTP(S) by setting specific headers */
	private function enhanceHttpSecurity() {
		// remove exposure of PHP version (at least where possible)
		header_remove('X-Powered-By');

		// if the user is signed in
		if ($this->isLoggedIn()) {
			// prevent clickjacking
			header('X-Frame-Options: sameorigin');
			// prevent content sniffing (MIME sniffing)
			header('X-Content-Type-Options: nosniff');

			// disable caching of potentially sensitive data
			header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true);
			header('Expires: Thu, 19 Nov 1981 00:00:00 GMT', true);
			header('Pragma: no-cache', true);
		}
	}

	public static function isLoginRequired($status = false)
    {
        if($status === true && self::isLoggedIn() === false)
        {
            header("Location: /account/login?next=".  Request::getInstance()->getCurrentLocation());
        }
    }

	/** Checks if there is a "remember me" directive set and handles the automatic login (if appropriate) */
	private function processRememberDirective() {
		// if the user is not signed in yet
		if (!$this->isLoggedIn()) {
			// if a remember cookie is set
            if($this->registry->has(self::COOKIE_NAME_REMEMBER)) {
				// split the cookie's content into selector and token
				$parts = explode(self::COOKIE_CONTENT_SEPARATOR, $this->registry->get(self::COOKIE_NAME_REMEMBER), 2);
				// if both selector and token were found
				if (isset($parts[0]) && isset($parts[1])) {
					try {
					    $rememberData =  $this->connection->createSelectStatement(AuthMap::getUserRememberedTable())
                            ->addJoinExpression(AuthMap::getJoinExpression())
                            ->addWhereExpression(new Comparision(AuthMap::COLUMN_SELECTOR, Criteria::EQUALS_TO, $parts[0], AuthMap::getUserRememberedTable()->getQualifiedNameWithAlias()))
                            ->execute($this->connection)
                            ->fetch();
					}
					catch (Error $e) {
						throw new DatabaseError();
					}

					if (!empty($rememberData)) {
						if ($rememberData[AuthMap::COLUMN_EXPIRES] >= time()) {
							if (password_verify($parts[1], $rememberData[AuthMap::COLUMN_TOKEN])) {
								$this->onLoginSuccessful($rememberData[AuthMap::COLUMN_USER], $rememberData[AuthMap::COLUMN_EMAIL], $rememberData[AuthMap::COLUMN_USERNAME], $rememberData[AuthMap::COLUMN_STATUS], true);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Attempts to sign up a user
	 *
	 * If you want the user's account to be activated by default, pass `null` as the callback
	 *
	 * If you want to make the user verify their email address first, pass an anonymous function as the callback
	 *
	 * The callback function must have the following signature:
	 *
	 * `function ($selector, $token)`
	 *
	 * Both pieces of information must be sent to the user, usually embedded in a link
	 *
	 * When the user wants to verify their email address as a next step, both pieces will be required again
	 *
	 * @param string $email the email address to register
	 * @param string $password the password for the new account
	 * @param string|null $username (optional) the username that will be displayed
	 * @param callable|null $callback (optional) the function that sends the confirmation email to the user
	 * @return int the ID of the user that has been created (if any)
	 * @throws InvalidEmailException if the email address was invalid
	 * @throws InvalidPasswordException if the password was invalid
	 * @throws UserAlreadyExistsException if a user with the specified email address already exists
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	public function register($email, $password, $username = null, callable $callback = null) {
		return $this->createUserInternal(false, $email, $password, $username, $callback);
	}

	/**
	 * Attempts to sign up a user while ensuring that the username is unique
	 *
	 * If you want the user's account to be activated by default, pass `null` as the callback
	 *
	 * If you want to make the user verify their email address first, pass an anonymous function as the callback
	 *
	 * The callback function must have the following signature:
	 *
	 * `function ($selector, $token)`
	 *
	 * Both pieces of information must be sent to the user, usually embedded in a link
	 *
	 * When the user wants to verify their email address as a next step, both pieces will be required again
	 *
	 * @param string $email the email address to register
	 * @param string $password the password for the new account
	 * @param string|null $username (optional) the username that will be displayed
	 * @param callable|null $callback (optional) the function that sends the confirmation email to the user
	 * @return int the ID of the user that has been created (if any)
	 * @throws InvalidEmailException if the email address was invalid
	 * @throws InvalidPasswordException if the password was invalid
	 * @throws UserAlreadyExistsException if a user with the specified email address already exists
	 * @throws DuplicateUsernameException if the specified username wasn't unique
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	public function registerWithUniqueUsername($email, $password, $username = null, callable $callback = null) {
		return $this->createUserInternal(true, $email, $password, $username, $callback);
	}

	/**
	 * Attempts to sign in a user with their email address and password
	 *
	 * @param string $email the user's email address
	 * @param string $password the user's password
	 * @param int|null $rememberDuration (optional) the duration in seconds to keep the user logged in ("remember me"), e.g. `60 * 60 * 24 * 365.25` for one year
	 * @param callable|null $onBeforeSuccess (optional) a function that receives the user's ID as its single parameter and is executed before successful authentication; must return `true` to proceed or `false` to cancel
	 * @throws InvalidEmailException if the email address was invalid or could not be found
	 * @throws InvalidPasswordException if the password was invalid
	 * @throws EmailNotVerifiedException if the email address has not been verified yet via confirmation email
	 * @throws AttemptCancelledException if the attempt has been cancelled by the supplied callback that is executed before success
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	public function login($email, $password, $forceLogin = false, $rememberDuration = null, callable $onBeforeSuccess = null) {
		$this->authenticateUserInternal($forceLogin, $password, $email, null, $rememberDuration, $onBeforeSuccess);
	}

	/**
	 * Attempts to sign in a user with their username and password
	 *
	 * When using this method to authenticate users, you should ensure that usernames are unique
	 *
	 * Consistently using {@see registerWithUniqueUsername} instead of {@see register} can be helpful
	 *
	 * @param string $username the user's username
	 * @param string $password the user's password
	 * @param int|null $rememberDuration (optional) the duration in seconds to keep the user logged in ("remember me"), e.g. `60 * 60 * 24 * 365.25` for one year
	 * @param callable|null $onBeforeSuccess (optional) a function that receives the user's ID as its single parameter and is executed before successful authentication; must return `true` to proceed or `false` to cancel
	 * @throws UnknownUsernameException if the specified username does not exist
	 * @throws AmbiguousUsernameException if the specified username is ambiguous, i.e. there are multiple users with that name
	 * @throws InvalidPasswordException if the password was invalid
	 * @throws EmailNotVerifiedException if the email address has not been verified yet via confirmation email
	 * @throws AttemptCancelledException if the attempt has been cancelled by the supplied callback that is executed before success
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	public function loginWithUsername($username, $password, $forceLogin = false, $rememberDuration = null, callable $onBeforeSuccess = null) {
		$this->authenticateUserInternal($forceLogin, $password, null, $username, $rememberDuration, $onBeforeSuccess);
	}

	/**
	 * Creates a new directive keeping the user logged in ("remember me")
	 *
	 * @param int $userId the user ID to keep signed in
	 * @param int $duration the duration in seconds
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	private function createRememberDirective($userId, $duration) {
		$selector = self::createRandomString(24);
		$token = self::createRandomString(32);
		$tokenHashed = password_hash($token, PASSWORD_DEFAULT);
		$expires = time() + ((int) $duration);

		try {
		    $statement = $this->connection->createInsertStatement(AuthMap::getUserRememberedTable());
		    $statement->addValue(AuthMap::COLUMN_USER, $userId);
		    $statement->addValue(AuthMap::COLUMN_SELECTOR, $selector);
		    $statement->addValue(AuthMap::COLUMN_TOKEN, $tokenHashed);
		    $statement->addValue(AuthMap::COLUMN_EXPIRES, $expires);
			$statement->execute($this->connection);
		}
		catch (Error $e) {
			throw new DatabaseError();
		}

		$this->setRememberCookie($selector, $token, $expires);
	}

	/**
	 * Clears an existing directive that keeps the user logged in ("remember me")
	 *
	 * @param int $userId the user ID that shouldn't be kept signed in anymore
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	private function deleteRememberDirective($userId) {
		try {
		    $statement = $this->connection->createDeleteStatement(AuthMap::getUserRememberedTable());
            $statement->addWhereExpression(new Comparision(AuthMap::COLUMN_USER, Criteria::EQUALS_TO, $userId));
            $statement->execute($this->connection);
		}
		catch (Error $e) {
			throw new DatabaseError();
		}

		$this->setRememberCookie(null, null, time() - 3600);
	}

	/**
	 * Sets or updates the cookie that manages the "remember me" token
	 *
	 * @param string $selector the selector from the selector/token pair
	 * @param string $token the token from the selector/token pair
	 * @param int $expires the interval in seconds after which the token should expire
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	private function setRememberCookie($selector, $token, $expires) {
		// get our cookie settings
		$params = $this->createCookieSettings();

		if (isset($selector) && isset($token)) {
			$content = $selector . self::COOKIE_CONTENT_SEPARATOR . $token;
		}
		else {
			$content = '';
		}

		// set the cookie with the selector and token
		$cookie = $this->registry->put(self::COOKIE_NAME_REMEMBER, $content, ["expires" => $expires, "params" => $params]);

		if ($cookie === false) {
			throw new HeadersAlreadySentError();
		}
	}

	/**
	 * Called when the user has successfully logged in (via standard login or "remember me")
	 *
	 * @param int $userId the ID of the user who has just logged in
	 * @param string $email the email address of the user who has just logged in
	 * @param string $username the username (if any)
	 * @param int $status the status as one of the constants from the {@see Status} class
	 * @param bool $remembered whether the user was remembered ("remember me") or logged in actively
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	private function onLoginSuccessful($userId, $email, $username, $status, $remembered) {
		try {
			$this->connection->createUpdateStatement(AuthMap::getTable())
            ->addValue(AuthMap::COLUMN_LAST_LOGIN, time())
            ->addWhereExpression(new Comparision(AuthMap::COLUMN_USER_ID, Criteria::EQUALS_TO, $userId))
            ->execute($this->connection);
		}
		catch (Error $e) {
			throw new DatabaseError();
		}

		// re-generate the session ID to prevent session fixation attacks
		$this->registry->regenerate(true, false);

		// save the user data in the session
		$this->setLoggedIn(true);
		$this->setUserId($userId);
		$this->setEmail($email);
		$this->setUsername($username);
		$this->setStatus($status);
		$this->setRemembered($remembered);
	}

	/**
	 * Logs out the user and destroys all session data
	 *
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	public function logout() {
		// if the user has been signed in
		if ($this->isLoggedIn()) {
			// get the user's ID
			$userId = $this->getUserId();
			// if a user ID was set
			if (isset($userId)) {
				// delete any existing remember directives
				$this->deleteRememberDirective($userId);
			}
		}

		// unset the session variables
		$this->registry->remove();

		// delete the cookie
		$this->deleteSessionCookie();

		// destroy the session
		session_destroy();
	}

	/**
	 * Deletes the session cookie on the client
	 *
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	private function deleteSessionCookie() {
		// get our cookie settings
		$params = $this->createCookieSettings();

		// cause the session cookie to be deleted
		$this->registry->remove(session_name());
	}

	/**
	 * Confirms an email address and activates the account by supplying the correct selector/token pair
	 *
	 * The selector/token pair must have been generated previously by registering a new account
	 *
	 * @param string $selector the selector from the selector/token pair
	 * @param string $token the token from the selector/token pair
	 * @throws InvalidSelectorTokenPairException if either the selector or the token was not correct
	 * @throws TokenExpiredException if the token has already expired
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	public function confirmEmail($selector, $token) {
		$this->throttle(self::THROTTLE_ACTION_CONSUME_TOKEN);
		$this->throttle(self::THROTTLE_ACTION_CONSUME_TOKEN, $selector);

		try {
			$confirmationData = $this->connection->createSelectStatement(AuthMap::getConfirmationTable())
            ->addWhereExpression(new Comparision(AuthMap::COLUMN_SELECTOR, Criteria::EQUALS_TO, $selector))
            ->execute($this->connection)
            ->fetch();
		}
		catch (Error $e) {
			throw new DatabaseError();
		}

		if (!empty($confirmationData)) {
			if (password_verify($token, $confirmationData[AuthMap::COLUMN_TOKEN])) {
				if ($confirmationData[AuthMap::COLUMN_EXPIRES] >= time()) {
					try {
						$this->connection->createUpdateStatement(AuthMap::getTable())
                        ->addValue(AuthMap::COLUMN_VERIFIED, 1)
                        ->addWhereExpression(new Comparision(AuthMap::COLUMN_EMAIL, Criteria::EQUALS_TO, $confirmationData[AuthMap::COLUMN_EMAIL]))
                        ->execute($this->connection);
					}
					catch (Error $e) {
						throw new DatabaseError();
					}

					try {
						$statement = $this->connection->createDeleteStatement(AuthMap::getConfirmationTable());
                        $statement->addWhereExpression(new Comparision(AuthMap::COLUMN_ID, $confirmationData[AuthMap::COLUMN_ID]));
                        $statement->execute($this->connection);
					}
					catch (Error $e) {
						throw new DatabaseError();
					}
				}
				else {
					throw new TokenExpiredException();
				}
			}
			else {
				throw new InvalidSelectorTokenPairException();
			}
		}
		else {
			throw new InvalidSelectorTokenPairException();
		}
	}

	/**
	 * Changes the (currently logged-in) user's password
	 *
	 * @param string $oldPassword the old password to verify account ownership
	 * @param string $newPassword the new password that should be used
	 * @throws NotLoggedInException if the user is not currently logged in
	 * @throws InvalidPasswordException if either the old password was wrong or the new password was invalid
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	public function changePassword($oldPassword, $newPassword) {
		if ($this->isLoggedIn()) {
			$oldPassword = self::validatePassword($oldPassword);
			$newPassword = self::validatePassword($newPassword);

			$userId = $this->getUserId();

			try {
				$passwordInDatabase = $this->connection->createSelectStatement(AuthMap::getTable())
                ->addWhereExpression(new Comparision(AuthMap::COLUMN_USER_ID, Criteria::EQUALS_TO, $userId))
                ->execute($this->connection)
                ->fetch();
			}
			catch (Error $e) {
				throw new DatabaseError();
			}

			if (!empty($passwordInDatabase)) {
				if (password_verify($oldPassword, $passwordInDatabase[AuthMap::COLUMN_PASSWORD])) {
					// update the password in the database
					$this->updatePassword($userId, $newPassword);

					// delete any remaining remember directives
					$this->deleteRememberDirective($userId);
				}
				else {
					throw new InvalidPasswordException();
				}
			}
			else {
				throw new NotLoggedInException();
			}
		}
		else {
			throw new NotLoggedInException();
		}
	}

	/**
	 * Updates the given user's password by setting it to the new specified password
	 *
	 * @param int $userId the ID of the user whose password should be updated
	 * @param string $newPassword the new password
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	private function updatePassword($userId, $newPassword) {
		$newPassword = password_hash($newPassword, PASSWORD_DEFAULT);

		try {
			$this->connection->createUpdateStatement(AuthMap::getTable())
                ->addValue(AuthMap::COLUMN_PASSWORD, $newPassword)
                ->addWhereExpression(new Comparision(AuthMap::COLUMN_USER_ID, Criteria::EQUALS_TO, $userId))
                ->execute($this->connection);
		}
		catch (Error $e) {
			throw new DatabaseError();
		}
	}

	/**
	 * Initiates a password reset request for the user with the specified email address
	 *
	 * The callback function must have the following signature:
	 *
	 * `function ($selector, $token)`
	 *
	 * Both pieces of information must be sent to the user, usually embedded in a link
	 *
	 * When the user wants to proceed to the second step of the password reset, both pieces will be required again
	 *
	 * @param string $email the email address of the user who wants to request the password reset
	 * @param callable $callback the function that sends the password reset information to the user
	 * @param int|null $requestExpiresAfter (optional) the interval in seconds after which the request should expire
	 * @param int|null $maxOpenRequests (optional) the maximum number of unexpired and unused requests per user
	 * @throws InvalidEmailException if the email address was invalid or could not be found
	 * @throws EmailNotVerifiedException if the email address has not been verified yet via confirmation email
	 * @throws TooManyRequestsException if the number of allowed attempts/requests has been exceeded
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	public function forgotPassword($email, callable $callback, $requestExpiresAfter = null, $maxOpenRequests = null) {
		$email = self::validateEmailAddress($email);

		if ($requestExpiresAfter === null) {
			// use six hours as the default
			$requestExpiresAfter = 60 * 60 * 6;
		}
		else {
			$requestExpiresAfter = (int) $requestExpiresAfter;
		}

		if ($maxOpenRequests === null) {
			// use two requests per user as the default
			$maxOpenRequests = 2;
		}
		else {
			$maxOpenRequests = (int) $maxOpenRequests;
		}

		$userData = $this->getUserDataByEmailAddress($email);

		// ensure that the account has been verified before initiating a password reset
		if ((int) $userData['verified'] !== 1) {
			throw new EmailNotVerifiedException();
		}

		$openRequests = (int) $this->getOpenPasswordResetRequests($userData['userId']);

		if ($openRequests < $maxOpenRequests) {
			$this->createPasswordResetRequest($userData['userId'], $requestExpiresAfter, $callback);
		}
		else {
			self::onTooManyRequests($requestExpiresAfter);
		}
	}

	/**
	 * Authenticates an existing user
	 *
	 * @param string $password the user's password
	 * @param string|null $email (optional) the user's email address
	 * @param string|null $username (optional) the user's username
	 * @param int|null $rememberDuration (optional) the duration in seconds to keep the user logged in ("remember me"), e.g. `60 * 60 * 24 * 365.25` for one year
	 * @param callable|null $onBeforeSuccess (optional) a function that receives the user's ID as its single parameter and is executed before successful authentication; must return `true` to proceed or `false` to cancel
	 * @throws InvalidEmailException if the email address was invalid or could not be found
	 * @throws UnknownUsernameException if an attempt has been made to authenticate with a non-existing username
	 * @throws AmbiguousUsernameException if an attempt has been made to authenticate with an ambiguous username
	 * @throws InvalidPasswordException if the password was invalid
	 * @throws EmailNotVerifiedException if the email address has not been verified yet via confirmation email
	 * @throws AttemptCancelledException if the attempt has been cancelled by the supplied callback that is executed before success
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	private function authenticateUserInternal($forceLogin = false, $password, $email = null, $username = null, $rememberDuration = null, callable $onBeforeSuccess = null) {
		if ($email !== null) {
			$email = self::validateEmailAddress($email);

			// attempt to look up the account information using the specified email address
			try {
				$userData = $this->getUserDataByEmailAddress($email);
			}
			// if there is no user with the specified email address
			catch (InvalidEmailException $e) {
				// throttle this operation
				$this->throttle(self::THROTTLE_ACTION_LOGIN);
				$this->throttle(self::THROTTLE_ACTION_LOGIN, $email);

				// and re-throw the exception
				throw new InvalidEmailException();
			}
		}
		elseif ($username !== null) {
			$username = trim($username);

			// attempt to look up the account information using the specified username
			try {
				$userData = $this->getUserDataByUsername($username);
			}
			// if there is no user with the specified username
			catch (UnknownUsernameException $e) {
				// throttle this operation
				$this->throttle(self::THROTTLE_ACTION_LOGIN);
				$this->throttle(self::THROTTLE_ACTION_LOGIN, $username);

				// and re-throw the exception
				throw new UnknownUsernameException();
			}
			// if there are multiple users with the specified username
			catch (AmbiguousUsernameException $e) {
				// throttle this operation
				$this->throttle(self::THROTTLE_ACTION_LOGIN);
				$this->throttle(self::THROTTLE_ACTION_LOGIN, $username);

				// and re-throw the exception
				throw new AmbiguousUsernameException();
			}
		}
		// if neither an email address nor a username has been provided
		else {
			// we can't do anything here because the method call has been invalid
			throw new EmailOrUsernameRequiredError();
		}

		$password = self::validatePassword($password);

		if (password_verify($password, $userData['password'])) {
			// if the password needs to be re-hashed to keep up with improving password cracking techniques
			if (password_needs_rehash($userData['password'], PASSWORD_DEFAULT)) {
				// create a new hash from the password and update it in the database
				$this->updatePassword($userData['id'], $password);
			}

			if ((int) $userData['verified'] === 1 || $forceLogin === true) {
				if (!isset($onBeforeSuccess) || (\is_callable($onBeforeSuccess) && $onBeforeSuccess($userData['userId']) === true)) {
					$this->onLoginSuccessful($userData['userId'], $userData['email'], $userData['username'], $userData['status'], false);

					// continue to support the old parameter format
					if ($rememberDuration === true) {
						$rememberDuration = 60 * 60 * 24 * 28;
					}
					elseif ($rememberDuration === false) {
						$rememberDuration = null;
					}

					if ($rememberDuration !== null) {
						$this->createRememberDirective($userData['userId'], $rememberDuration);
					}

					return;
				}
				else {
					throw new AttemptCancelledException();
				}
			}
			else {
				throw new EmailNotVerifiedException();
			}
		}
		else {
			// throttle this operation
			$this->throttle(self::THROTTLE_ACTION_LOGIN);
			if (isset($email)) {
				$this->throttle(self::THROTTLE_ACTION_LOGIN, $email);
			}
			elseif (isset($username)) {
				$this->throttle(self::THROTTLE_ACTION_LOGIN, $username);
			}

			// we cannot authenticate the user due to the password being wrong
			throw new InvalidPasswordException();
		}
	}

	/**
	 * Returns the requested user data for the account with the specified email address (if any)
	 *
	 * You must never pass untrusted input to the parameter that takes the column list
	 *
	 * @param string $email the email address to look for
	 * @param array $requestedColumns the columns to request from the user's record
	 * @return array the user data (if an account was found)
	 * @throws InvalidEmailException if the email address could not be found
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	private function getUserDataByEmailAddress($email) {
		try {
			$userData = $this->connection->createSelectStatement(AuthMap::getTable())
            ->addWhereExpression(new Comparision(AuthMap::COLUMN_EMAIL, Criteria::EQUALS_TO, $email))
            ->execute($this->connection)
            ->fetch();
		}
		catch (Error $e) {
			throw new DatabaseError();
		}

		if (!empty($userData)) {
			return $userData;
		}
		else {
			throw new InvalidEmailException();
		}
	}

	/**
	 * Returns the number of open requests for a password reset by the specified user
	 *
	 * @param int $userId the ID of the user to check the requests for
	 * @return int the number of open requests for a password reset
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	private function getOpenPasswordResetRequests($userId) {
		try {
			$requests = $this->connection->createSelectStatement(AuthMap::getUserResetTable())
            ->addWhereExpression(new Comparision(AuthMap::COLUMN_USER, Criteria::EQUALS_TO, $userId))
            ->addWhereExpression(new Comparision(AuthMap::COLUMN_EXPIRES, Criteria::GREATER_THAN, time()))
            ->execute($this->connection)
            ->getLength();

			if (!empty($requests)) {
				return $requests;
			}
			else {
				return 0;
			}
		}
		catch (Error $e) {
			throw new DatabaseError();
		}
	}

	/**
	 * Creates a new password reset request
	 *
	 * The callback function must have the following signature:
	 *
	 * `function ($selector, $token)`
	 *
	 * Both pieces of information must be sent to the user, usually embedded in a link
	 *
	 * When the user wants to proceed to the second step of the password reset, both pieces will be required again
	 *
	 * @param int $userId the ID of the user who requested the reset
	 * @param int $expiresAfter the interval in seconds after which the request should expire
	 * @param callable $callback the function that sends the password reset information to the user
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	private function createPasswordResetRequest($userId, $expiresAfter, callable $callback) {
		$selector = self::createRandomString(20);
		$token = self::createRandomString(20);
		$tokenHashed = password_hash($token, PASSWORD_DEFAULT);
		$expiresAt = time() + $expiresAfter;

		try {
			$statement = $this->connection->createInsertStatement(AuthMap::getUserResetTable());
            $statement->addValue(AuthMap::COLUMN_USER, $userId);
            $statement->addValue(AuthMap::COLUMN_SELECTOR, $selector);
            $statement->addValue(AuthMap::COLUMN_TOKEN, $tokenHashed);
            $statement->addValue(AuthMap::COLUMN_EXPIRES, $expiresAt);
            $statement->execute($this->connection);
		}
		catch (Error $e) {
			throw new DatabaseError();
		}

		if (isset($callback) && is_callable($callback)) {
			$callback($selector, $token);
		}
		else {
			throw new MissingCallbackError();
		}
	}

	/**
	 * Resets the password for a particular account by supplying the correct selector/token pair
	 *
	 * The selector/token pair must have been generated previously by calling `Auth#forgotPassword(...)`
	 *
	 * @param string $selector the selector from the selector/token pair
	 * @param string $token the token from the selector/token pair
	 * @param string $newPassword the new password to set for the account
	 * @throws InvalidSelectorTokenPairException if either the selector or the token was not correct
	 * @throws TokenExpiredException if the token has already expired
	 * @throws InvalidPasswordException if the new password was invalid
	 * @throws TooManyRequestsException if the number of allowed attempts/requests has been exceeded
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	public function resetPassword($selector, $token, $newPassword) {
		$this->throttle(self::THROTTLE_ACTION_CONSUME_TOKEN);
		$this->throttle(self::THROTTLE_ACTION_CONSUME_TOKEN, $selector);

		try {
			$resetData = $this->connection->createSelectStatement(AuthMap::getUserResetTable())
            ->addWhereExpression(new Comparision(AuthMap::COLUMN_SELECTOR, Criteria::EQUALS_TO, $selector))
            ->execute($this->connection)
            ->fetch();
		}
		catch (Error $e) {
			throw new DatabaseError();
		}

		if (!empty($resetData)) {
			if (password_verify($token, $resetData['token'])) {
				if ($resetData['expires'] >= time()) {
					$newPassword = self::validatePassword($newPassword);

					// update the password in the database
					$this->updatePassword($resetData['user'], $newPassword);

					// delete any remaining remember directives
					$this->deleteRememberDirective($resetData['user']);

					try {
						$statement = $this->connection->createDeleteStatement(AuthMap::getUserResetTable());
                        $statement->addWhereExpression(new Comparision(AuthMap::COLUMN_ID, Criteria::EQUALS_TO, $resetData['resetId']));
                        $statement->execute($this->connection);
					}
					catch (Error $e) {
						throw new DatabaseError();
					}
				}
				else {
					throw new TokenExpiredException();
				}
			}
			else {
				throw new InvalidSelectorTokenPairException();
			}
		}
		else {
			throw new InvalidSelectorTokenPairException();
		}
	}

	/**
	 * Check if the supplied selector/token pair can be used to reset a password
	 *
	 * The selector/token pair must have been generated previously by calling `Auth#forgotPassword(...)`
	 *
	 * @param string $selector the selector from the selector/token pair
	 * @param string $token the token from the selector/token pair
	 * @return bool whether the password can be reset using the supplied information
	 * @throws AuthError if an internal problem occurred (do *not* catch)
	 */
	public function canResetPassword($selector, $token) {
		try {
			// pass an invalid password intentionally to force an expected error
			$this->resetPassword($selector, $token, null);

			// we should already be in the `catch` block now so this is not expected
			throw new AuthError();
		}
		// if the password is the only thing that's invalid
		catch (InvalidPasswordException $e) {
			// the password can be reset
			return true;
		}
		// if some other things failed (as well)
		catch (AuthException $e) {
			return false;
		}
	}

	/**
	 * Sets whether the user is currently logged in and updates the session
	 *
	 * @param bool $loggedIn whether the user is logged in or not
	 */
	private function setLoggedIn($loggedIn) {
		$this->registry->put(self::SESSION_FIELD_LOGGED_IN, $loggedIn);
	}

	/**
	 * Returns whether the user is currently logged in by reading from the session
	 *
	 * @return boolean whether the user is logged in or not
	 */
	public function isLoggedIn() {
        return (Registry::getInstance()->has(self::SESSION_FIELD_LOGGED_IN) && Registry::getInstance()->get(self::SESSION_FIELD_LOGGED_IN) === true)?true:false;
	}

	/**
	 * Shorthand/alias for ´isLoggedIn()´
	 *
	 * @return boolean
	 */
	public function check() {
		return $this->isLoggedIn();
	}

	/**
	 * Sets the currently signed-in user's ID and updates the session
	 *
	 * @param int $userId the user's ID
	 */
	private function setUserId($userId) {
		$this->registry->put(self::SESSION_FIELD_USER_ID, intval($userId));
	}

	/**
	 * Returns the currently signed-in user's ID by reading from the session
	 *
	 * @return int the user ID
	 */
	public function getUserId() {
        return ($this->registry->has(self::SESSION_FIELD_USER_ID))?$this->registry->get(self::SESSION_FIELD_USER_ID):null;
	}

	/**
	 * Shorthand/alias for `getUserId()`
	 *
	 * @return int
	 */
	public function id() {
		return $this->getUserId();
	}

	/**
	 * Sets the currently signed-in user's email address and updates the session
	 *
	 * @param string $email the email address
	 */
	private function setEmail($email) {
	    $this->registry->put(self::SESSION_FIELD_EMAIL, $email);
	}

	/**
	 * Returns the currently signed-in user's email address by reading from the session
	 *
	 * @return string the email address
	 */
	public function getEmail() {
        return ($this->registry->has(self::SESSION_FIELD_EMAIL))?$this->registry->get(self::SESSION_FIELD_EMAIL):null;
	}

	/**
	 * Sets the currently signed-in user's display name and updates the session
	 *
	 * @param string $username the display name
	 */
	private function setUsername($username) {
		$this->registry->put(self::SESSION_FIELD_USERNAME, $username);
	}

	/**
	 * Returns the currently signed-in user's display name by reading from the session
	 *
	 * @return string the display name
	 */
	public function getUsername() {
        return ($this->registry->has(self::SESSION_FIELD_USERNAME))?$this->registry->get(self::SESSION_FIELD_USERNAME):null;
	}

	/**
	 * Sets the currently signed-in user's status and updates the session
	 *
	 * @param int $status the status as one of the constants from the {@see Status} class
	 */
	private function setStatus($status) {
	    $this->registry->put(self::SESSION_FIELD_STATUS, (int) $status);
	}

	/**
	 * Returns the currently signed-in user's status by reading from the session
	 *
	 * @return int the status as one of the constants from the {@see Status} class
	 */
	public function getStatus() {
        return ($this->registry->has(self::SESSION_FIELD_STATUS))?$this->registry->get(self::SESSION_FIELD_STATUS):null;
	}

	/**
	 * Returns whether the currently signed-in user is in "normal" state
	 *
	 * @return bool
	 *
	 * @see Status
	 * @see Auth::getStatus
	 */
	public function isNormal() {
		return $this->getStatus() === Status::NORMAL;
	}

	/**
	 * Returns whether the currently signed-in user is in "archived" state
	 *
	 * @return bool
	 *
	 * @see Status
	 * @see Auth::getStatus
	 */
	public function isArchived() {
		return $this->getStatus() === Status::ARCHIVED;
	}

	/**
	 * Returns whether the currently signed-in user is in "banned" state
	 *
	 * @return bool
	 *
	 * @see Status
	 * @see Auth::getStatus
	 */
	public function isBanned() {
		return $this->getStatus() === Status::BANNED;
	}

	/**
	 * Returns whether the currently signed-in user is in "locked" state
	 *
	 * @return bool
	 *
	 * @see Status
	 * @see Auth::getStatus
	 */
	public function isLocked() {
		return $this->getStatus() === Status::LOCKED;
	}

	/**
	 * Returns whether the currently signed-in user is in "pending review" state
	 *
	 * @return bool
	 *
	 * @see Status
	 * @see Auth::getStatus
	 */
	public function isPendingReview() {
		return $this->getStatus() === Status::PENDING_REVIEW;
	}

	/**
	 * Returns whether the currently signed-in user is in "suspended" state
	 *
	 * @return bool
	 *
	 * @see Status
	 * @see Auth::getStatus
	 */
	public function isSuspended() {
		return $this->getStatus() === Status::SUSPENDED;
	}

	/**
	 * Sets whether the currently signed-in user has been remembered by a long-lived cookie
	 *
	 * @param bool $remembered whether the user was remembered
	 */
	private function setRemembered($remembered) {
		$this->registry->put(self::SESSION_FIELD_REMEMBERED, $remembered);
	}

	/**
	 * Returns whether the currently signed-in user has been remembered by a long-lived cookie
	 *
	 * @return bool whether they have been remembered
	 */
	public function isRemembered() {
        return ($this->registry->has(self::SESSION_FIELD_REMEMBERED))?$this->registry->get(self::SESSION_FIELD_REMEMBERED):null;
	}

	public function loginRequired($redirect = "/account/login", callable $ajaxLogin = null)
    {
        if(!self::check())
        {
            Registry::getInstance()->put("returnUrl", Request::getInstance()->getCurrentLocation());
            if(!Request::getInstance()->isAjax())
            {
                header("Location: /account/login");
                exit(0);
            }
            else{
                call_user_func($ajaxLogin);
            }
        }
    }

	/**
	 * Hashes the supplied data
	 *
	 * @param mixed $data the data to hash
	 * @return string the hash in Base64-encoded format
	 */
	private static function hash($data) {
		$hashRaw = hash(self::IP_ADDRESS_HASH_ALGORITHM, $data, true);

		return base64_encode($hashRaw);
	}

	/**
	 * Returns the user's current IP address
	 *
	 * @return string the IP address (IPv4 or IPv6)
	 */
	public function getIpAddress() {
		return $this->ipAddress;
	}

	/**
	 * Returns the current time bucket that is used for throttling purposes
	 *
	 * @return int the time bucket
	 */
	private function getTimeBucket() {
		return (int) (time() / $this->throttlingTimeBucketSize);
	}

	protected function throttle($actionType, $customSelector = null) {
		// if a custom selector has been provided (e.g. username, user ID or confirmation token)
		if (isset($customSelector)) {
			// use the provided selector for throttling
			$selector = self::hash($customSelector);
		}
		// if no custom selector was provided
		else {
			// throttle by the user's IP address
			$selector = self::hash($this->getIpAddress());
		}

		// get the time bucket that we do the throttling for
		$timeBucket = self::getTimeBucket();

		try {
			$statement = $this->connection->createInsertStatement(AuthMap::getUserThrottlingTable());
			$statement->addValue(AuthMap::COLUMN_ACTION_TYPE, $actionType);
			$statement->addValue(AuthMap::COLUMN_SELECTOR, $selector);
			$statement->addValue(AuthMap::COLUMN_TIME_BUCKET, $timeBucket);
			$statement->addValue(AuthMap::COLUMN_ATTEMPTS, 1);
			$statement->execute($this->connection);
		}
		catch (IntegrityConstraintViolationException $e) {
			// if we have a duplicate entry, update the old entry
			try {
                $data = $this->connection->createSelectStatement(AuthMap::getUserThrottlingTable())
                    ->addWhereExpression(new Comparision(AuthMap::COLUMN_ACTION_TYPE, Criteria::EQUALS_TO, $actionType))
                    ->addWhereExpression(new Comparision(AuthMap::COLUMN_SELECTOR, Criteria::EQUALS_TO, $selector))
                    ->addWhereExpression(new Comparision(AuthMap::COLUMN_TIME_BUCKET, Criteria::EQUALS_TO, $timeBucket))
                    ->execute($this->connection)
                    ->fetch();
				$this->connection->createUpdateStatement(AuthMap::getUserThrottlingTable())
                    ->addValue(AuthMap::COLUMN_ATTEMPTS, (int) $data[AuthMap::COLUMN_ATTEMPTS]+1)
                    ->addWhereExpression(new Comparision(AuthMap::COLUMN_ACTION_TYPE, Criteria::EQUALS_TO, $actionType))
                    ->addWhereExpression(new Comparision(AuthMap::COLUMN_SELECTOR, Criteria::EQUALS_TO, $selector))
                    ->addWhereExpression(new Comparision(AuthMap::COLUMN_TIME_BUCKET, Criteria::EQUALS_TO, $timeBucket))
                    ->execute($this->connection);
			}
			catch (Error $e) {
				throw new DatabaseError();
			}
		}
		catch (Error $e) {
			throw new DatabaseError();
		}

		try {
			$attempts = $this->connection->createSelectStatement(AuthMap::getUserThrottlingTable())
                ->addWhereExpression(new Comparision(AuthMap::COLUMN_ACTION_TYPE, Criteria::EQUALS_TO, $actionType))
                ->addWhereExpression(new Comparision(AuthMap::COLUMN_SELECTOR, Criteria::EQUALS_TO, $selector))
                ->addWhereExpression(new Comparision(AuthMap::COLUMN_TIME_BUCKET, Criteria::EQUALS_TO, $timeBucket))
                ->execute($this->connection)
                ->fetch();
		}
		catch (Error $e) {
			throw new DatabaseError();
		}

		if (!empty($attempts)) {
			// if the number of attempts has acceeded our accepted limit
			if ($attempts[AuthMap::COLUMN_ATTEMPTS] > $this->throttlingActionsPerTimeBucket) {
				self::onTooManyRequests($this->throttlingTimeBucketSize);
			}
		}
	}

	/**
	 * Called when there have been too many requests for some action or object
	 *
	 * @param int|null $retryAfterInterval (optional) the interval in seconds after which the client should retry
	 * @throws TooManyRequestsException to inform any calling method about this problem
	 */
	private static function onTooManyRequests($retryAfterInterval = null) {
		// if no interval has been provided after which the client should retry
		if ($retryAfterInterval === null) {
			// use one day as the default
			$retryAfterInterval = 60 * 60 * 24;
		}

		// send an appropriate HTTP status code
		http_response_code(self::HTTP_STATUS_CODE_TOO_MANY_REQUESTS);
		// tell the client when they should try again
		@header('Retry-After: '.$retryAfterInterval);
		// throw an exception
		throw new TooManyRequestsException();
	}

	/**
	 * Customizes the throttling options
	 *
	 * @param int $actionsPerTimeBucket the number of allowed attempts/requests per time bucket
	 * @param int $timeBucketSize the size of the time buckets in seconds
	 */
	public function setThrottlingOptions($actionsPerTimeBucket, $timeBucketSize) {
		$this->throttlingActionsPerTimeBucket = intval($actionsPerTimeBucket);

		if (isset($timeBucketSize)) {
			$this->throttlingTimeBucketSize = intval($timeBucketSize);
		}
	}

	/**
	 * Returns the component that can be used for administrative tasks
	 *
	 * You must offer access to this interface to authorized users only (restricted via your own access control)
	 *
	 * @return Administration
	 */
	public function admin() {
//		return new Administration($this->db);
	}

	/**
	 * Creates the cookie settings that will be used to create and update cookies on the client
	 *
	 * @return array the cookie settings
	 */
	private function createCookieSettings() {
		// get the default cookie settings
		$params = session_get_cookie_params();

		// check if we want to send cookies via SSL/TLS only
		$params['secure'] = $params['secure'] || $this->useHttps;
		// check if we want to send cookies via HTTP(S) only
		$params['httponly'] = $params['httponly'] || !$this->allowCookiesScriptAccess;

		// return the modified settings
		return $params;
	}

	/**
	 * Creates a UUID v4 as per RFC 4122
	 *
	 * The UUID contains 128 bits of data (where 122 are random), i.e. 36 characters
	 *
	 * @return string the UUID
	 * @author Jack @ Stack Overflow
	 */
	public static function createUuid() {
		$data = openssl_random_pseudo_bytes(16);

		// set the version to 0100
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		// set bits 6-7 to 10
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

}
