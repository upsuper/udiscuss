<?php

class User extends Model
{
    const CREATE_EMAIL_INVALID = 1;
    const CREATE_USERNAME_EXISTS = 2;
    const CREATE_EMAIL_EXISTS = 3;
    const CREATE_UNKNOWN = -1;

    private $user_id;
    private $username;
    private $email;
    private $filled = false;
    private $dirty = false;

    /**
     * Construct function
     *
     * @param int $user_id
     */
    public function __construct($user_id)
    {
        if (!is_int($user_id) || $user_id <= 0)
            throw new Exception('Wrong user id');
        $this->user_id = $user_id;
    }

    /**
     * Destruct function
     */
    public function __destruct()
    {
        $this->put_info();
    }

    public function __sleep()
    {
        $this->put_info();
        return array('user_id');
    }

    /**
     * Save to database
     *
     * @return bool
     */
    public function put_info()
    {
        if (!$this->dirty)
            return true;

        global $db;
        $result = query($db,
            'UPDATE users SET username=%s, email=%s
            WHERE user_id=%s',
            $this->username, $this->email, $this->user_id);
        if ($result)
            $this->dirty = false;
        return !!$result;
    }

    /**
     * Fill info with given data
     * If no data given, query the database
     *
     * @param mixed $user given data
     */
    private function fill_info($user = NULL)
    {
        if ($user === NULL) {
            if ($this->filled)
                return;
            global $db;
            $user = query_one($db,
                "SELECT username, email
                FROM users WHERE user_id=%s", $this->user_id);
            if (!$user)
                throw new Exception('User not found');
        }
        $this->username = $user->username;
        $this->email = $user->email;
        $this->filled = true;
    }

    /**
     * Get user instance, if not found, return false
     *
     * @param string $username Either username or email is acceptable
     * @return User|false
     */
    public static function get_user($username)
    {
        global $db;
        if (strpos($username, '@') !== false) {
            $user = query_one($db, '
                SELECT user_id FROM users
                WHERE email=%s', $username);
        } else {
            $user = query_one($db, '
                SELECT user_id FROM users
                WHERE username=%s', $username);
        }
        if (!$user)
            return false;
        return new User($user->user_id);
    }

    /**
     * Get User instances by user ids
     *
     * @param array $ids
     * @return array Key is user_id, value is corresponding instance
     */
    public static function get_by_ids($ids)
    {
        $ids = array_unique(array_filter($ids, 'is_int'));
        if (!$ids)
            return array();
        $ids = implode(', ', $ids);

        global $db;
        $query = query($db,
            "SELECT user_id, username, email
            FROM users WHERE user_id IN ($ids)");

        $result = array();
        while (($row = $query->fetch()) !== false) {
            $inst = new User($row->user_id);
            $inst->fill_info($row);
            $result[$row->user_id] = $inst;
        }
        return $result;
    }

    /**
     * Create user
     *
     * @param string $username
     * @param string $email
     * @return User|int If create failed, return User::CREATE_* for error
     */
    public static function create_user($username, $email)
    {
        if (!is_valid_email($email))
            return self::CREATE_EMAIL_INVALID;

        global $db;
        $result = query($db,
            'INSERT INTO users (username, email) VALUES (%s, %s)',
            $username, $email);
        if (!$result) {
            $error = $db->errorInfo();
            if (strpos($error, 'username') !== false)
                return self::CREATE_USERNAME_EXISTS;
            if (strpos($error, 'email') !== false)
                return self::CREATE_EMAIL_EXISTS;
            return self::CREATE_UNKNOWN;
        }
        $user_id = intval($db->lastInsertId('users_user_id_seq'));
        return new User($user_id);
    }

    /**
     * Get user ID
     *
     * @return int
     */
    public function _get_id()
    {
        return $this->user_id;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function _get_username()
    {
        $this->fill_info();
        return $this->username;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return bool
     */
    public function _set_username($username)
    {
        $this->fill_info();
        if ($this->username != $username) {
            if (!$username)
                return false;
            $this->dirty = true;
        }
        $this->username = username;
        return true;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function _get_email()
    {
        $this->fill_info();
        return $this->email;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return bool
     */
    public function _set_email($email)
    {
        $this->fill_info();
        if ($this->email != $email) {
            if (!is_valid_email($email))
                return false;
            $this->dirty = true;
        }
        $this->email = email;
        return true;
    }

    /**
     * Check password
     *
     * @param string $password
     * @return bool
     */
    public function check_password($password)
    {
        global $db;
        $result = query_one($db,
            'SELECT password FROM users WHERE user_id=%s',
            $this->user_id);
        $password = crypt($password, $result->password);
        return $password == $result->password;
    }

    /**
     * Generate fixed length salt
     *
     * Because most algorithms crypt supports only accept ./0-9A-Za-z
     * as salt, the string generated here will only contain them.
     *
     * @param int $length
     * @return string
     */
    private static function generate_salt($length)
    {
        static $alphabet =
            './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $ret = '';
        for ($i = 0; $i < $length; ++$i)
            $ret .= $alphabet[mt_rand(0, 63)];
        return $ret;
    }

    /**
     * Set password
     *
     * @param string $password
     */
    public function _set_password($password)
    {
        global $db;
        $salt = '$1$'.self::generate_salt(8).'$';
        $password = crypt($password, $salt);
        $result = query($db, 
            'UPDATE users SET password=%s WHERE user_id=%s',
            $password, $this->user_id);
    }
}

?>
