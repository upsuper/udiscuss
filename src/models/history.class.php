<?php

class History extends Model
{
    private $history_id;
    private $content;
    private $time;
    private $discuss_id;
    private $user_id;
    private $filled = false;

    /**
     * Construct function
     *
     * @param int $history_id
     */
    public function __construct($history_id)
    {
        if (!is_int($history_id) || $history_id <= 0)
            throw new Exception('Wrong history id');
        $this->history_id = $history_id;
    }

    /**
     * Fill info with given data
     * If no data given, query the database
     *
     * @param mixed $history
     */
    private function fill_info($history = NULL)
    {
        if ($history === NULL) {
            if ($this->filled)
                return;
            global $db;
            $history = query_one($db,
                'SELECT content, time, discuss_id, user_id
                FROM history WHERE history_id=%s',
                $this->history_id);
            if (!$history)
                throw new Exception('History not found');
        }
        $this->content = $history->content;
        $this->time = new Datetime($history->time);
        $this->discuss_id = intval($history->discuss_id);
        $this->user_id = intval($history->user_id);
        $this->filled = true;
    }

    /**
     * Get history items
     *
     * If discuss_id = 0, return all history items over all
     * If user_id = 0, return all history items for give discuss
     *
     * @param int $discuss_id
     * @param int $user_id
     * @param int $limit
     * @param int $offset
     * @return array of History instances
     */
    public static function get_history($discuss_id = 0, $user_id = 0,
        $limit = 20, $offset = 0)
    {
        $discuss_id = intval($discuss_id);
        $user_id = intval($user_id);
        $limit = intval($limit);
        $offset = intval($offset);

        $condition = 'WHERE 1=1';
        if ($discuss_id > 0)
            $condition .= ' AND discuss_id='.$discuss_id;
        if ($user_id > 0)
            $condition .= ' AND user_id='.$user_id;

        global $db;
        $query = query($db,
            "SELECT history_id, content, time, discuss_id, user_id
            FROM history $condition ORDER BY history_id DESC
            LIMIT $limit OFFSET $offset");

        $result = array();
        while (($row = $query->fetch()) !== false) {
            $inst = new History($row->history_id);
            $inst->fill_info($row);
            $result[] = $inst;
        }
        return $result;
    }

    /**
     * Create history
     *
     * @param int $discuss_id
     * @param int $user_id
     * @param string $content
     * @return History|false
     */
    public static function create_history($discuss_id, $user_id, $content)
    {
        global $db;
        $result = query($db,
            'INSERT INTO history (content, discuss_id, user_id) 
            VALUES (%s, %s, %s)',
            $content, $discuss_id, $user_id);
        if (!$result)
            return false;
        $history_id = $db->lastInsertId('history_history_id_seq');
        return new History(intval($history_id));
    }

    /**
     * Get history id
     *
     * @return int
     */
    public function _get_id()
    {
        return $this->history_id;
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
     * SHOULD be seldom used.
     * Since this method should be used carefully,
     * we do not declare it as setter.
     *
     * @param string $content
     * @return bool
     */
    public function update_content($content)
    {
        global $db;
        $result = query($db,
            "UPDATE history SET content=%s WHERE history_id=%s",
            $content, $this->history_id);
        if (!$result)
            return false;
        $this->content = $content;
        return true;
    }

    /**
     * Get time of history
     *
     * @return Datetime
     */
    public function _get_time()
    {
        $this->fill_info();
        return $this->time;
    }

    /**
     * Get discuss id
     *
     * @return int
     */
    public function _get_discuss_id()
    {
        $this->fill_info();
        return $this->discuss_id;
    }

    /**
     * Get user id
     *
     * @return int
     */
    public function _get_user_id()
    {
        $this->fill_info();
        return $this->user_id;
    }
}
