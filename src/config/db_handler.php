<?php
/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author andreasonny83@gmail.com
 */
namespace Config\Database;
use Config\Database\DB_Connect;
use Config\SecureSessionHandler;

class DbHandler {

	/**
	 * [$conn description]
	 * @var [type]
	 */
	private $conn;

	/**
	 * [$session description]
	 * @var [type]
	 */
	private $session;

	function __construct() {
		$db            = new DB_Connect();
		$this->session = new SecureSessionHandler( 'language_quiz' );
		$this->conn    = $db->connect();
	}

	/**
	 * checkBrute
	 * @user_id String
	 * @return  Boolean
	 */
	public function checkBrute( $user_uid ) {
		// Get timestamp of current time
		$now = time();
		// All login attempts are counted from the past hour
		$valid_attempts = $now - ( 3600 );
		// insert query
		$stmt = $this->conn->prepare( 'SELECT time FROM LQ_login_attempts WHERE user_uid = ? AND time > ?' );
		$stmt->bind_param( 'si', $user_uid, $valid_attempts );
		$stmt->execute();
		$stmt->store_result();

		if ( $stmt->num_rows > 5 ) {
			// Sospend the user account according to the $valid_attempts time
			$stmt->close();
			return true;
		}
		else {
			$stmt->close();
			return false;
		}
	}

	/**
	 * Check user exists in the database
	 * @input_email	String		User login email id
	 * @return		Boolean		User login status success/fail
	 */
	public function checkUserExisits( $input_email ) {
		// fetching user by email
		$stmt = $this->conn->prepare( 'SELECT user_uid FROM LQ_users
			WHERE email = ? LIMIT 1' );
		$stmt->bind_param( 's', $input_email );
		$stmt->execute();
		$stmt->bind_result( $db_uid );
		$stmt->store_result();

		if ( $stmt->num_rows === 1 ) {
			// If 1 and only 1 user if Found
			$stmt->fetch();
			$stmt->close();

			return $db_uid;
		}
		else {
			// No user exists.
			$stmt->close();
			return false;
		}
	}

	/**
	 * Check user credentials
	 * @input_email		String		User login email id
	 * @input_password	String		User login password
	 * @return			Boolean		User login status success/fail
	 */
	public function checkUserLogin( $input_email, $input_password ) {
		// fetching user by email
		$stmt = $this->conn->prepare( 'SELECT user_uid, password, salt
			FROM LQ_users
			WHERE email = ? LIMIT 1' );
		$stmt->bind_param( 's', $input_email );
		$stmt->execute();
		$stmt->bind_result( $db_uid, $db_password, $db_salt );
		$stmt->store_result();

		// Continue only if 1 and only 1 user if found
		if ( $stmt->num_rows !== 1 ) {
			$stmt->close();
			return false;
		}

		$stmt->fetch();
		$stmt->close();
		// Salt the input password with the salt from the database
		$password = hash( 'sha512', $input_password );
		$password = hash( 'sha512', $input_password . $db_salt );
		$now      = time();

		// Check if the password matches
		if ( $db_password === $password ) {
			// password is correct
			$this->session->start();
			// Generate a new session every time
			$this->session->refresh();

			// Expire the session after 2 weeks
			$session_expiration = $now + 1209600;
			$session_id         = session_id();

			$stmt = $this->conn->prepare( "UPDATE LQ_users
					SET session_id=?, session_expiration=?
					WHERE user_uid=?" );

			$stmt->bind_param( 'sii', $session_id, $session_expiration, $db_uid );
			$stmt->execute();
			$stmt->close();

			// store the user id into the user's cookie
			setcookie( 'lq_user_id', $db_uid, $session_expiration, '/' );
			/**
			 * TODO
			 * Will use the following information to store inside the database
			 * The user agent information
			**/
			// $user_agent = $_SERVER['HTTP_USER_AGENT'];
			// XSS protection as we might print this value
			// $user_id = preg_replace( '/[^0-9]+/', '', $db_id );
			// XSS protection as we might print this value
			// $username = preg_replace( '/[^a-zA-Z0-9_\-]+/', '', $db_username );
			// $session->put( 'LQ_user_agent', $user_agent );
			// setcookie( 'lq_userid', $db_uid, time() + ( 86400 * 30 ), '/' ); // 1 day
			return true;
		}
		else {
			// user password is incorrect
			// record this attempt in the database
			$stmt = $this->conn->prepare( "INSERT INTO LQ_login_attempts( user_uid, time )
				VALUES ( ?, ? )" );
			$stmt->bind_param( 'si', $db_uid, $now );
			$stmt->execute();
			$stmt->close();

			return false;
		}

	}

	/**
	 * [checkRegister description]
	 * @param  [type] $user [description]
	 * @return [type]       [description]
	 */
	public function checkRegister( $user ) {
		$stmt = $this->conn->prepare( 'SELECT id FROM LQ_users WHERE email = ? OR username = ? LIMIT 1' );
		$stmt->bind_param( 'ss', $user['email'], $user['name'] );
		$stmt->execute();
		$stmt->store_result();
		$result = $stmt->num_rows;
		$stmt->close();

		if ( $result ) {
			// If a user with the same email already exists in the database
			// Terminate the request
			return false;
		}
		else {
			// create the new user
			$status     = 1;
			$user_level = 1;
			$now        = time();

			// Create a salted password
			$random_salt = hash( 'sha512', uniqid( mt_rand( 1, mt_getrandmax() ), true ) );
			$password    = hash( 'sha512', $user['password'] . $random_salt );
			$user_uid    = uniqid();

			// Register a new user
			$stmt = $this->conn->prepare( "INSERT INTO LQ_users(
				user_uid, username, email, password, salt, status, registration_date )
				VALUES ( ?, ?, ?, ?, ?, ?, ? )" );
			// Register user data
			$stmt2 = $this->conn->prepare( "INSERT INTO LQ_users_data(
				user_uid, lang, level )
				VALUES ( ?, ?, ? )" );

			$stmt->bind_param( 'sssssii',
				$user_uid, $user['name'], $user['email'], $password, $random_salt, $status, $now );
			$stmt2->bind_param( 'ssi',
				$user_uid, $user['language'], $user_level );

			if ( $stmt->execute() ) {
				// Registration succeed
				$stmt2->execute();
				$stmt->close();
				$stmt2->close();
				return true;
			}
			else {
				// can't perform the database request
				$stmt->close();
				return false;
			}
		}
	}

	/**
	 * getUser
	 * @userUID String		User unique id
	 * @return Array		The user name
	 */
	public function getUser( $userUID ) {
		$stmt = $this->conn->prepare( 'SELECT
			username
			FROM LQ_users WHERE user_uid = ? LIMIT 1' );
		$stmt->bind_param( 's', $userUID );
		$stmt->execute();
		$stmt->bind_result( $username );
		$stmt->store_result();
		$stmt->fetch();
		$stmt->close();

		if ( isset ( $username ) ) {
			return array(
				"username" => $username,
			);
		}
		else {
			return false;
		}
	}

	/**
	 * getCollections
	 * @collectionType	boolean		0 = global, 1 = private
	 * @userId			integer		the user uid
	 * @page			integer		the page to load. Each page contains 10 collections
	 * @return			array		return the collections, if any, and other informations
	 */
	public function getCollections( $collectionType, $userId, $page = 0 ) {
		$collections    = array();
		$loadMore       = true;
		$items_per_page = 10;
		$page           = $page * $items_per_page;

		// Prepare to read the global collections
		if ( ! $collectionType ) {
			$stmt = $this->conn->prepare( 'SELECT
				uid, name, description, lang, rank, score, flash, taboo
				FROM LQ_collections
				WHERE status = 2 LIMIT ?, 10' );
		}
		// Otherwise read the user private collections
		else {
			// Still Work In Progress
			$stmt = $this->conn->prepare( 'SELECT
				uid, name, description, lang, rank, score, flash, taboo
				FROM LQ_collections
				WHERE status = 2 LIMIT ?, 10' );
		}

		$stmt->bind_param( 'd', $page );
		$stmt->execute();
		$stmt->bind_result( $uid, $name, $description, $lang, $rank, $score, $flash, $taboo );
		$stmt->store_result();

		while( $stmt->fetch() ) {

			$collections[] = array(
				'uid'         => $uid,
				'name'        => $name,
				'description' => $description,
				'lang'        => $lang,
				'rank'        => $rank,
				'score'       => $score,
				'flash'       => $flash,
				'taboo'       => $taboo,
			);
		}

		$stmt->close();

		// disable loading further collections
		// if we already reached the end of the table
		if ( sizeof ( $collections ) < 10 ) {
			$loadMore = false;
		}

		return array(
			'collections' => $collections,
			'loadMore' => $loadMore
		);
	}

	public function logOut( $user_session ) {
		$now = time() + 2;

		//Expire the current session
		if ( $stmt = $this->conn->prepare( "UPDATE LQ_users
			SET session_expiration = ?
			WHERE session_id = ?" ) ) {
				$stmt->bind_param( 'ii', $now, $user_session );
				$stmt->execute();
		}

		$this->session->forget();
	}

	public function fetchGlobalCollections( $useruid, $page ) {
		$collections    = array();
		$items_per_page = 10;
		$page           = $page * $items_per_page;

		if ( $stmt = $this->conn->prepare( 'SELECT uid, name, description, lang, rank, score, flash, taboo FROM LQ_collections WHERE status = 2 LIMIT ?, 10' ) ) {
			$stmt->bind_param( 'd', $page );
			$stmt->store_result();
			$stmt->bind_result( $uid, $name, $description, $lang, $rank, $score, $flash, $taboo );
			$stmt->execute();
			while( $stmt->fetch() ) {
				$collections[] = array(
					'uid'         => $uid,
					'name'        => $name,
					'description' => $description,
					'lang'        => $lang,
					'rank'        => $rank,
					'score'       => $score,
					'flash'       => $flash,
					'taboo'       => $taboo,
				);
			}

			$stmt->close();
		}

		if ( $stmt = $this->conn->prepare( 'SELECT lq_collection_uid, favourite FROM LQ_users_rel WHERE lq_user_uid = ?' ) ) {
			$stmt->bind_param( 's', $useruid );
			$stmt->store_result();
			$stmt->bind_result( $collection_uid, $favourite );
			$stmt->execute();

			while( $stmt->fetch() ) {
				// if ( isset ( $collections[$collection_uid] ) ) {
				// 	$collections[$collection_uid]['favourite'] = $favourite;
				// }
			}

			$stmt->close();
		}

		return $collections;
	}

	public function fetchPrivateCollections( $useruid ) {
		$collections = array();

		if ( $stmt = $this->conn->prepare( 'SELECT uid, name, description, lang, rank, score, flash, taboo, favourite FROM LQ_collections INNER JOIN LQ_users_rel ON lq_collection_uid = uid WHERE lq_user_uid = ? AND permission = 9' ) ) {
			$stmt->bind_param( 'd', $useruid );
			$stmt->store_result();
			$stmt->bind_result( $uid, $name, $description, $lang, $rank, $score, $flash, $taboo, $favourite );
			$stmt->execute();

			while( $stmt->fetch() ) {
				$collections[] = array(
					'uid'         => $uid,
					'name'        => $name,
					'description' => $description,
					'lang'        => $lang,
					'rank'        => $rank,
					'score'       => $score,
					'flash'       => $flash,
					'taboo'       => $taboo,
					'favourite'   => $favourite,
				);
			}

			$stmt->close();
		}

		return $collections;
	}

	public function newCollection( $collection ) {
		$uid = uniqid();

		if ( $stmt = $this->conn->prepare( 'INSERT INTO LQ_collections(uid, name, description, lang, status) VALUES (? ? ? ? ?)' ) ) {
			$stmt->bind_param( 'ssssd', $uid, 'test', 'testdescript', '', 0 );
			$stmt->execute();
			return $uid;
		}
		return false;
	}

	/**
	 * Authenticate the request
	 * The request need to have the LQ-API-KEY correctly set
	 * and the user session needs to be verified
	 * @userUID		String		User unique ID
	 * @userSession	String		The session ID
	 * @return		Boolean
	 */
	public function authenticate( $userUID, $userSession ) {
		$stmt = $this->conn->prepare( 'SELECT
			session_id, session_expiration
			FROM LQ_users
			WHERE user_uid = ?'
		);
		$stmt->bind_param( 's', $userUID );
		$stmt->store_result();
		$stmt->bind_result( $db_session_id, $db_session_exp );
		$stmt->execute();
		$stmt->fetch();
		$stmt->close();

		if ( $db_session_id !== $userSession ) {
			return false;
		}

		if ( time() > $db_session_exp ) {
			return false;
		}

		return true;
	}

}
