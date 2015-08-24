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
	 * [check_brute description]
	 * @param  [type] $user_id [description]
	 * @return [type]          [description]
	 */
	private function check_brute( $user_id ) {
		// Get timestamp of current time
		$now = time();

		// All login attempts are counted from the past hour
		$valid_attempts = $now - ( 3600 );

		// insert query
		$stmt = $this->conn->prepare( 'SELECT time FROM LQ_login_attempts WHERE user_id = ? AND time > ?' );
		$stmt->bind_param( 'ii', $user_id, $valid_attempts );
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
	 * Checking user login
	 * @param String $email User login email id
	 * @param String $password User login password
	 * @return boolean User login status success/fail
	 */
	public function checkLogin( $input_email, $input_password ) {
		// fetching user by email
		$stmt = $this->conn->prepare( 'SELECT id, email, password, salt FROM LQ_users WHERE email = ? LIMIT 1' );
		$stmt->bind_param( 's', $input_email );
		$stmt->execute();
		$stmt->bind_result( $db_id, $db_email, $db_password, $db_salt );
		$stmt->store_result();

		if ( $stmt->num_rows === 1 ) {
			// If 1 and only 1 user if Found
			$stmt->fetch();
			$stmt->close();

			// Salt the input password with the salt from the database
			$password = hash( 'sha512', $input_password );
			$password = hash( 'sha512', $input_password . $db_salt );

			// Check the brute force
			if ( $this->check_brute( $db_id ) === true ) {
				// Account is locked
				/**
				 * TODO
				 * Send an email to unlock the accout
				**/
				return false;
			}
			else {
				// Check if the password in the database matches
				// the password the user submitted.
				if ( $db_password === $password ) {
					// password is correct
					$this->session->start();
					// Generate a new session every time
					$this->session->refresh();

					$session_id       = session_id();
					$now              = time();
					$user_token       = hash( 'md5', time() . uniqid() . $db_email );

					// Expire the user token after 1 hour and the session after 2 weeks
					$token_expiration_time   = time() + 3600;
					$session_expiration_time = time() + 1209600;
					$token_expiration = date( 'Y-m-d H:i:s', $time_expiration );

					$stmt = $this->conn->prepare( "UPDATE LQ_users
							SET user_token=?, user_session_id=?, session_expiration=?, token_expiration=?
							WHERE id=?" );

					$stmt->bind_param( 'ssiii', $user_token, $session_id, $session_expiration_time, $token_expiration, $db_id );
					$stmt->execute();
					$stmt->close();

					// store the user token into the user's cookie and expire in 1 hour
					setcookie( 'lq_user', $user_token, $time_expiration, '/' );

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
					$now = time();
					$stmt = $this->conn->prepare( "INSERT INTO LQ_login_attempts( user_id, time ) VALUES ( ?, ? )" );
					$stmt->bind_param( 'ii', $db_id, $now );
					$stmt->execute();
					$stmt->close();

					return false;
				}
			}
		}
		else {
			// No user exists.
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
		$stmt = $this->conn->prepare( 'SELECT id FROM LQ_users WHERE email = ? LIMIT 1' );
		$stmt->execute();
		$stmt->bind_result( $db_id );
		$stmt->store_result();

		if ( $stmt->num_rows > 0 ) {
			$stmt->close();
			// If a user with the same email already exists in the database
			// Terminate the request
			return false;
		}
		else {
			// create the new user
			$stmt->close();
			// Until we set an email verification procedure
			// All new user will automatically verified once created
			$status = 1;
			$now    = time();

			// Create a salted password
			$random_salt = hash( 'sha512', uniqid( mt_rand( 1, mt_getrandmax() ), true ) );
			$password    = hash( 'sha512', $user['password'] . $random_salt );

			// Register a new user
			$this->conn->prepare( "INSERT INTO LQ_users(
				status, username, email, password, salt, registration )
				VALUES ( ?, ?, ?, ?, ?, ? )" );

			$stmt->bind_param( 'issssi',
				$status, $user['name'], $user['email'], $password, $random_salt, $now );

			$result = $stmt->execute();
			$stmt->close();

			if ( $result ) {
				// Registration succeed
				return true;
			}
			else {
				// can't perform the database request
				return false;
			}
		}
	}

	/**
	 * [getUser description]
	 * @param  [type] $userId      [description]
	 * @param  [type] $userSession [description]
	 * @return [type]              [description]
	 */
	public function getUser( $userId, $userSession ) {
		$stmt = $this->conn->prepare( 'SELECT
			user_session_id, username, email, level, rank
			FROM LQ_users WHERE lq_user = ? LIMIT 1' );
		$stmt->bind_param( 's', $userId );
		$stmt->execute();
		$stmt->bind_result( $user_session_id, $username, $email, $level, $rank );
		$stmt->store_result();
		$stmt->fetch();
		$stmt->close();

		if ( $user_session_id === $userSession ) {
			return array(
				"username" => $username,
			);
		}
		else {
			return false;
		}
	}

	/**
	 * [getCollections description]
	 * @collectionType	boolean		global, private
	 * @userId			integer		the user uid
	 * @page			integer		the page to load. Each page contains 10 collections
	 * @return			array		return the collections, if any, and other informations
	 */
	public function getCollections( $collectionType, $userId, $page = 0 ) {
		$collections    = array();
		$items_per_page = 10;
		$page           = $page * $items_per_page;
		$loadMore       = true;

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
			WHERE user_session_id = ?" ) ) {
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

}
