<?php

class User_Controller extends Controller
{
    public function index()
    {
        check_login();
        $user = $_SESSION['user'];
        return redirect("/user/{$user->id}/");
    }

    public function __call($id, $args)
    {
        $id = intval($id);
        $action = array_shift($args);
        array_unshift($args, $id);

        switch ($action) {
        case '':
            $action = 'view';
        case 'discuss':
        case 'replies':
        case 'history':
            return call_user_func_array(array($this, $action), $args);
        default:
            return not_found();
        }
    }

    private function view($id)
    {
        $user = new User($id);
        $discusses = Discuss::get_discuss_by_initiater($id, 5);
        $replies = Reply::get_reply_by_user($id, 5);
        $reply_discusses = Discuss::get_by_ids(array_map(function ($reply) {
            return $reply->discuss_id;
        }, $replies));
        $history = History::get_history(0, $id, 5);

        return template('user_view.html', array(
            'user' => $user,
            'discusses' => $discusses,
            'replies' => $replies,
            'reply_discusses' => $reply_discusses,
            'history' => $history
        ));
    }

    private function discuss($id)
    {
        $limit = 50;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = $limit * ($page - 1);

        $discusses = Discuss::get_discuss_by_initiater($id, $limit, $offset);
        return template('user_discuss.html', array(
            'discusses' => $discusses
        ));
    }

    private function replies($id)
    {
        $limit = 50;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = $limit * ($page - 1);

        $replies = Reply::get_reply_by_user($id, $limit, $offset);
        $discusses = Discuss::get_by_ids(array_map(function ($reply) {
            return $reply->discuss_id;
        }, $replies);

        return template('user_reply.html', array(
            'replies' => $replies,
            'discusses' => $discusses
        ));
    }

    private function history($id)
    {
        $limit = 50;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = $limit * ($page - 1);

        $history = History::get_history(0, $id, $limit, $offset);
        $discusses = Discuss::get_by_ids(array_map(function ($item) {
            return $item->discuss_id;
        }, $history);
    }
}

?>
