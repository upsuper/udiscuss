<?php

class Discuss extends Model
{
    private $discuss_id;
    private $initiater;
    private $title;
    private $permission;
    private $last_update;
    private $filled = false;
    private $dirty = false;

    const PERMISSION_NONE = 0;
    const PERMISSION_READ = 1;
    const PERMISSION_REPLY = 2;
    const PERMISSION_INVITE = 3;

    /**
     * Construct function
     *
     * @param int $discuss_id
     */
    public function __construct($discuss_id)
    {
        if (!is_int($discuss_id) || $discuss_id <= 0)
            throw new Exception('Wrong discuss id');
        $this->discuss_id = $discuss_id;
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
        return array('discuss_id');
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
            'UPDATE discuss SET title=%s, permission=%s
            WHERE discuss_id=%s',
            $this->title, self::convert_permission($this->permission),
            $this->discuss_id);
        if ($result)
            $this->dirty = false;
        return !!$result;
    }

    /**
     * Fill info with given data
     * If no data given, query the database
     *
     * @param mixed $discuss given data
     */
    private function fill_info($discuss = NULL)
    {
        if ($discuss === NULL) {
            if ($this->filled)
                return;
            global $db;
            $discuss = query_one($db,
                'SELECT initiater, title, permission, last_update
                FROM discuss WHERE discuss_id=%s',
                $this->discuss_id);
            if (!$discuss)
                throw new Exception('Discuss not found');
        }
        $this->initiater = $discuss->initiater;
        $this->title = $discuss->title;
        $this->permission =
            self::convert_permission($discuss->permission, true);
        $this->last_update = new Datetime($discuss->last_update);
        $this->filled = true;
    }

    /**
     * Get discussions
     *
     * @param string $condition
     * @param int $limit If equal to 0, no limit set
     * @param int $offset
     * @return array of Discuss instances
     */
    private static function get_discuss($condition,
        $limit = 10, $offset = 0)
    {
        global $db;
        
        $limit = intval($limit);
        $offset = intval($offset);
        $limits = ($limit > 0 ? "LIMIT $limit " : "").
            ($offset > 0? "OFFSET $offset" : "");

        $query = query($db, 
            "SELECT discuss_id, initiater, title, permission, last_update
            FROM discuss WHERE $condition
            ORDER BY last_update DESC $limits");

        $result = array();
        while (($row = $query->fetch()) !== false) {
            $inst = new Discuss($row->discuss_id);
            $inst->fill_info($row);
            $result[] = $inst;
        }
        return $result;
    }

    /**
     * Get discussions as user
     *
     * If user_id = 0, return result for guest.
     *
     * @param int $user_id
     * @param int $limit If equal to 0, no limit set
     * @param int $offset
     * @return array of Discuss instances
     */
    public static function get_discuss_as_user($user_id,
        $limit = 10, $offset = 0)
    {
        if ($user_id > 0) {
            $condition = "permission>='read' OR 
                initiater=$user_id OR
                discuss_id IN (
                    SELECT discuss_id FROM user_permission
                    WHERE user_id=$user_id AND permission>='read')";
        } else {
            $condition = 'permission>=\'read\'';
        }
        return self::get_discuss($condition, $limit, $offset);
    }

    /**
     * Get discussions as user
     *
     * If user_id = 0, return result for guest.
     *
     * @param int $user_id
     * @param int $limit If equal to 0, no limit set
     * @param int $offset
     * @return array of Discuss instances
     */
    public static function get_discuss_by_initiater($initiater,
        $limit = 10, $offset = 0)
    {
        $initiater = intval($initiater);
        return self::get_discuss("initiater=$initiater", $limit, $offset);
    }

    /**
     * Get Discuss instances by discuss ids
     *
     * @param array $ids
     * @return array Key is discuss_id, value is corresponding instance
     */
    public static function get_by_ids($ids)
    {
        $ids = array_unique(array_filter($ids, 'is_int'));
        if (!$ids)
            return array();
        $ids = implode(', ', $ids);

        global $db;
        $query = query($db,
            "SELECT discuss_id, initiater, title, permission, last_update
            FROM discuss WHERE discuss_id IN ($ids)");

        $result = array();
        while (($row = $query->fetch()) !== false) {
            $inst = new Discuss($row->discuss_id);
            $inst->fill_info($row);
            $result[$row->discuss_id] = $inst;
        }
        return $result;
    }

    /**
     * Create discuss
     *
     * @param int $initiater User id of initiater
     * @param string $title
     * @param int $permission Discuss::PERMISSION_*
     * @param string $content
     * @return Discuss|false
     */
    public static function create_discuss($initiater, $title,
        $permission, $content)
    {
        global $db;

        $db->beginTransaction();
        $result = query($db, 
            'INSERT INTO discuss (initiater, title, permission)
            VALUES (%s, %s, %s)',
            $initiater, $title,
            self::convert_permission($permission));
        if (!$result)
            goto failed;
        $discuss_id = intval($db->lastInsertId('discuss_discuss_id_seq'));

        $result = Reply::create_reply($discuss_id, $initiater, $content);
        if (!$result)
            goto failed;

        $db->commit();
        return new Discuss($discuss_id);

    failed:
        $db->rollBack();
        return false;
    }

    /**
     * Get discuss ID
     *
     * @return int
     */
    public function _get_id()
    {
        return $this->discuss_id;
    }

    /**
     * Get user ID of initiater
     *
     * @return int
     */
    public function _get_initiater()
    {
        $this->fill_info();
        return $this->initiater;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function _get_title()
    {
        $this->fill_info();
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return bool
     */
    public function _set_title($title)
    {
        $this->fill_info();
        if ($this->title != $title)
            $this->dirty = true;
        $this->title = $title;
        return true;
    }

    /**
     * Get default permission
     *
     * @return int Discuss::PERMISSION_*
     */
    public function _get_permission()
    {
        $this->fill_info();
        return $this->permission;
    }

    /**
     * Set default permission
     *
     * @return bool
     */
    public function _set_permission($permission)
    {
        $this->fill_info();
        if ($this->permission != $permission)
            $this->dirty = true;
        $this->permission = $permission;
        return true;
    }

    /**
     * Get last update time
     *
     * @return Datetime
     */
    public function _get_last_update()
    {
        $this->fill_info();
        return $this->last_update;
    }

    /**
     * Update last update time
     *
     * @return bool
     */
    public function update_time()
    {
        global $db;

        $result = query($db,
            'UPDATE discuss SET last_update=DEFAULT WHERE discuss_id=%s',
            $this->discuss_id);
        if (!$result)
            return false;
        $this->last_update = new Datetime();
        return true;
    }

    /**
     * Convert permission between database and model
     *
     * @param int|string $permission
     * @param bool $from_db If convert from db to model, set true
     * @return string|int
     */
    public static function convert_permission($permission, $from_db = false)
    {
        static $perm_todb = array(
            self::PERMISSION_NONE => 'none',
            self::PERMISSION_READ => 'read',
            self::PERMISSION_REPLY => 'reply',
            self::PERMISSION_INVITE => 'invite');
        static $perm_fromdb = NULL;
        if (!$perm_fromdb)
            $perm_fromdb = array_flip($perm_todb);

        return $from_db ? $perm_fromdb[$permission] : $perm_todb[$permission];
    }

    /**
     * Get permission for given user.
     * If there are no specified permission for the user,
     * it returns false.
     *
     * @param int $user_id
     * @return int|false int for Discuss::PERMISSION_*
     */
    public function get_user_permission($user_id)
    {
        global $db;

        $result = query_one($db,
            'SELECT permission FROM user_permission
            WHERE discuss_id=%s AND user_id=%s',
            $this->discuss_id, $user_id);
        if ($result === false)
            return false;
        return Discuss::convert_permission($result);
    }

    /**
     * Set permission for given user
     *
     * @param int $user_id
     * @param int $permission Discuss::PERMISSION_*
     * @return bool
     */
    public function set_user_permission($user_id, $permission)
    {
        global $db;

        $db->beginTransaction();
        $result = query_one($db,
            'SELECT permission FROM user_permission
            WHERE discuss_id=%s AND user_id=%s
            FOR UPDATE',
            $this->discuss_id, $user_id);
        if ($result === false) {
            $result = query($db,
                'INSERT INTO user_permission
                (discuss_id, user_id, permission)
                VALUES (%s, %s, %s)',
                $this->discuss_id, $user_id,
                self::convert_permission($permission));
        } else {
            $result = query($db,
                'UPDATE user_permission SET permission=%s
                WHERE discuss_id=%s AND user_id=%s',
                self::convert_permission($permission),
                $this->discuss_id, $user_id);
        }
        if ($result)
            $db->commit();
        else
            $db->rollBack();
        return !!$result;
    }

    /**
     * Remove special permission setting of a user
     *
     * @param int $user_id
     * @return bool
     */
    public function remove_user_permission($user_id)
    {
        global $db;
        $result = query($db,
            'DELETE FROM user_permission
            WHERE discuss_id=%s AND user_id=%s',
            $this->discuss_id, $user_id);
        return !!$result;
    }

    /**
     * List all special permission setting of discuss
     *
     * @return array Keys are user_ids, values are permission
     */
    public function list_user_permission()
    {
        global $db;
        $query = query($db,
            'SELECT user_id, permission
            FROM user_permission WHERE discuss_id=%s',
            $this->discuss_id);
        $result = array();
        while (($row = $query->fetch()) !== false) {
            $result[intval($row->user_id)] =
                self::convert_permission($row->permission);
        }
        return $result;
    }
}

?>
