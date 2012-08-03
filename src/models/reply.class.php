<?php

class Reply extends Model
{
    private $discuss_id;
    private $user_id;
    private $content;
    private $last_update;
    private $filled = false;

    /**
     * Construct function
     *
     * @param int $discuss_id
     * @param int $user_id
     */
    public function __construct($discuss_id, $user_id)
    {
        if (!is_int($discuss_id) || $discuss_id <= 0)
            throw new Exception('Wrong discuss id');
        if (!is_int($user_id) || $user_id <= 0)
            throw new Exception('Wrong user id');
        $this->discuss_id = $discuss_id;
        $this->user_id = $user_id;
    }

    /**
     * Fill info with given data
     * If no data given, query the database
     *
     * @param mixed $reply given data
     */
    private function fill_info($reply = NULL)
    {
        if ($reply === NULL) {
            if ($this->filled)
                return;
            global $db;
            $reply = query_one($db, 
                'SELECT content, last_update FROM reply
                WHERE discuss_id=%s AND user_id=%s',
                $this->discuss_id, $this->user_id);
            if (!$reply)
                throw new Exception('Reply not found');
        }
        $this->content = $reply->content;
        $this->last_update = new Datetime($reply->last_update);
        $this->filled = true;
    }

    /**
     * Get replies
     *
     * @param string $condition
     * @param int $limit
     * @param int $offset
     * @return array of Reply instances
     */
    private static function get_reply($condition, $limit, $offset)
    {
        $limit = intval($limit);
        $offset = intval($offset);

        global $db;
        $query = query($db,
            "SELECT discuss_id, user_id, content, last_update
            FROM reply WHERE $condition
            ORDER BY last_update DESC
            LIMIT $limit OFFSET $offset");

        $result = array();
        while (($row = $query->fetch()) !== false) {
            $inst = new Reply($row->discuss_id, $row->user_id);
            $inst->fill_info($row);
            $result[] = $inst;
        }
        return $result;
    }

    /**
     * Get replies in one discuss
     *
     * @param int $discuss_id
     * @param int $limit
     * @param int $offset
     * @return array of Reply instances
     */
    public static function get_reply_by_discuss($discuss_id,
        $limit = 10, $offset = 0)
    {
        $discuss_id = intval($discuss_id);
        return $this->get_reply('discuss_id='.$discuss_id, $limit, $offset);
    }

    /**
     * Get replies by one user
     *
     * @param int $user_id
     * @param int $limit
     * @param int $offset
     * @return array of Reply instances
     */
    public static function get_reply_by_user($user_id,
        $limit = 10, $offset = 0)
    {
        $user_id = intval($user_id);
        return $this->get_reply('user_id='.$user_id, $limit, $offset);
    }

    /**
     * Create reply
     *
     * @param int $discuss_id
     * @param int $user_id
     * @param string $content
     * @return bool
     */
    public static function create_reply($discuss_id, $user_id, $content)
    {
        global $db;

        $in_transaction = $db->inTransaction();
        if (!$in_transaction)
            $db->beginTransaction();

        $result = query($db,
            'INSERT INTO reply (discuss_id, user_id, content)
            VALUES (%s, %s, %s)',
            $discuss_id, $user_id, $content);
        if (!$result)
            goto failed;

        $result = History::create_history($discuss_id, $user_id, $content);
        if (!$result)
            goto failed;

        $discuss = new Discuss($discuss_id);
        $result = $discuss->update_time();
        if (!$result)
            goto failed;

        if (!$in_transaction)
            $db->commit();
        return true;

    failed:
        if (!$in_transaction)
            $db->rollBack();
        return false;
    }

    /**
     * Get discuss ID
     *
     * @return int
     */
    public function _get_discuss_id()
    {
        return $this->discuss_id;
    }

    /**
     * Get user ID
     *
     * @return int
     */
    public function _get_user_id()
    {
        return $this->user_id;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function _get_content()
    {
        $this->fill_info();
        return $this->content;
    }

    /**
     * Update content
     *
     * This method is likely to create a history item
     * Since this method could do something other than
     * a simple setter, we do not declare it as setter.
     *
     * @param string $content
     * @return bool
     */
    public function update_content($content)
    {
        static $five_minutes = new DateInterval('PT5M');
        $last_update = $this->_get_last_update();
        $now = new Datetime();

        global $db;
        $db->beginTransaction();
        if ($last_update.add($five_minutes) > $now) {
            // we can change a history within 5 minutes
            $history = query_one($db,
                'SELECT history_id FROM history
                WHERE discuss_id=%s AND user_id=%s
                ORDER BY history_id DESC LIMIT 1 FOR UPDATE',
                $this->discuss_id, $this->user_id);
            if (!$history)
                goto failed;

            $history = new History($history->history_id);
            if (!$history->update_content($content))
                goto failed;

            $result = query($db,
                'UPDATE reply SET content=%s
                WHERE discuss_id=%s AND user_id=%s',
                $content, $this->discuss_id, $this->user_id);
            if (!$result)
                goto failed;
        } else {
            // otherwise, we will create new history
            $history = History::create_history(
                $this->discuss_id, $this->user_id, $content);
            if (!$history)
                goto failed;

            $result = query($db,
                'UPDATE reply SET content=%s, last_update=DEFAULT
                WHERE discuss_id=%s AND user_id=%s',
                $content, $this->discuss_id, $this->user_id);
            if (!$result)
                goto failed;

            $discuss = new Discuss($this->discuss_id);
            if (!$discuss->update_time())
                goto failed;
        }
        $db->commit();
        $this->content = $content;
        return true;

    failed:
        $db->rollBack();
        return false;
    }

    /**
     * Get last update
     *
     * @return Datetime
     */
    public function _get_last_update()
    {
        $this->fill_info();
        return $this->last_update;
    }
}

?>
