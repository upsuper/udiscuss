<?php

class Discuss_Controller extends Controller
{
    public function index()
    {
        $limit = 50;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = $limit * ($page - 1);
        $user_id = isset($_SESSION['user']) ? $_SESSION['user']->id : 0;

        $discusses = Discuss::get_discuss_as_user($user_id, $limit, $offset);
        $users = array_map(function ($discuss) {
            return $discuss->initiater;
        }, $discusses);
        $users = User::get_by_ids($users);

        return template('discuss_list.html', array(
            'discusses' => $discusses,
            'users' => $users
        ));
    }

    public function create()
    {
        check_login();
        if (!is_post())
            return template('discuss_create.html');

        $user = $_SESSION['user'];
        $title = get_form('title');
        $permission = intval(get_form('permission'));
        $content = get_form('content');

        $discuss = Discuss::create_discuss($user->id, $title,
            $permission, $content);
        if (!$discuss) {
            flash('Create discuss failed.', 'error');
            return redirect();
        }

        return redirect("/discuss/{$discuss->id}/");
    }

    public function __call($id, $args)
    {
        $id = intval($id);
        $action = array_shift($args);
        array_unshift($args, $id);

        switch ($action) {
        case '':
            $action = 'view';
        case 'history':
        case 'permission':
        case 'invite':
        case 'edit':
            return call_user_func_array(array($this, $action), $args);
        default:
            return not_found();
        }
    }

    /**
     * Get effective permission of current user
     *
     * @param Discuss $discuss
     * @return int Discuss::PERMISSION_*
     */
    private function get_permission($discuss, $permission)
    {
        $discuss_perm = $discuss->permission;
        if (!isset($_SESSION['user'])) {
            if ($discuss_perm > Discuss::PERMISSION_READ)
                return Discuss::PERMISSION_READ;
        } else {
            $user = $_SESSION['user'];
            if ($user->id == $initiater)
                return true;
            $perm = $user->get_user_permission($discuss->id);
            if ($perm !== false)
                return $perm;
        }
        return $discuss_perm;
    }

    private function view($id)
    {
        $user = $_SESSION['user'];
        $discuss = new Discuss($id);
        if ($this->get_permission($discuss) < Discuss::PERMISSION_READ)
            return forbidden();

        $replies = Reply::get_reply_by_discuss($id, 0);
        $users = User::get_by_ids(array_map(function ($reply) {
            return $reply->user_id;
        }, $replies));

        return template('discuss_view.html', array(
            'discuss' => $discuss,
            'replies' => $replies,
            'users' => $users
        ));
    }

    private function history($id, $user_id = 0)
    {
        $discuss = new Discuss($id);
        if ($this->get_permission($discuss) < Discuss::PERMISSION_READ)
            return forbidden();

        $limit = 50;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = $limit * ($page - 1);

        $history = History::get_history($id, $user_id, $limit, $offset);
        if ($user_id == 0) {
            $users = User::get_by_ids(array_map(function ($item) {
                return $item->user_id;
            }, $history));
            return template('discuss_history.html', array(
                'history' => $history,
                'users' => $users
            ));
        } else {
            return template('reply_history.html', array(
                'history' => $history
            ));
        }
    }

    private function permission($id)
    {
        check_login();

        $user = $_SESSION['user'];
        $discuss = new Discuss($id);
        if ($discuss->initiater != $user->id)
            return forbidden();

        if (!is_post()) {
            $permissions = $discuss->list_user_permission();
            $users = User::get_by_ids(array_keys($permissions));
            return template('discuss_permission.html', array(
                'discuss' => $discuss,
                'users' => $users,
                'permissions' => $permissions
            ));
        }

        $action = get_form('action');
        $user_id = intval(get_form('user_id'));
        $result = false;
        switch ($action) {
        case 'set':
            $permission = intval(get_form('permission'));
            $result = $discuss->set_user_permission($user_id, $permission);
            break;
        case 'remove':
            $result = $discuss->remove_user_permission($user_id);
            break;
        }

        return json(array('result' => $result));
    }

    private function invite($id)
    {
        $discuss = new Discuss($id);
        if ($this->get_permission($discuss) < Discuss::PERMISSION_INVITE)
            return forbidden();

        $user = $_SESSION['user'];
        $user_id = intval(get_form('user_id'));
        $old_perm = $discuss->get_user_permission($user_id);
        if ($old_perm !== false)
            return json(array('result' => false, 'reason' => 'exists'));

        $result = $discuss->set_user_permission($user_id,
            Discuss::PERMISSION_REPLY);
        return json(array('result' => $result));
    }

    private function edit($id)
    {
        $discuss = new Discuss($id);
        if ($this->get_permission($discuss) < Discuss::PERMISSION_REPLY)
            return forbidden();

        $user = $_SESSION['user'];
        $reply = new Reply($id, $user->id);
        try {
            $content = $reply->content;
        } catch (Exception $e) {
            $content = null;
        }

        if (!is_post()) {
            return template('reply_edit.html', array(
                'content' => $content
            ));
        }

        $new_content = get_form('content');
        if ($content === null) {
            $reply = Reply::create_reply($id, $user->id, $new_content);
        } else if ($content != $new_content) {
            if (!$reply->update_content($new_content))
                flash('Update reply failed.', 'error');
        }
        return redirect("/discuss/$id/#reply_{$user->id}");
    }
}

?>
