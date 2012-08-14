<?php

/**
 * Check if logined
 * if not logined, redirect to login page
 *
 * MAY not return
 */
function check_login()
{
    if (isset($_SESSION['user']))
        return;
    return redirect('/login/?redirect_to='.urlencode($_SERVER['PATH_INFO']));
}

class _Controller extends Controller
{
    public function index()
    {
        return redirect('/discuss/');
    }

    public function register()
    {
        if (!is_post())
            return template('register.html');

        $username = trim(get_form('username'));
        if (!$username) {
            flash('Please input username.', 'fail');
            return redirect();
        }
        $email = trim(get_form('email'));
        $password = get_form('password');

        $user = User::create_user($username, $email);
        if (!($user instanceof User)) {
            switch ($user) {
            case User::CREATE_EMAIL_INVALID:
                flash('Please input a valid email.', 'fail');
                break;
            case User::CREATE_USERNAME_EXISTS:
                flash('Username exists.', 'fail');
                break;
            case User::CREATE_EMAIL_EXISTS:
                flash('Email exists, login directly please.', 'fail');
                break;
            case User::CREATE_UNKNOWN:
                flash('Unknown error occurred, please try again.', 'error');
                break;
            }
            if ($user == User::CREATE_EMAIL_EXISTS)
                return redirect('/login/?username='.urlencode($email));
            else
                return redirect();
        }
        $user->password = $password;

        $_SESSION['user'] = $user;
        flash('Register success.', 'success');
        return redirect('/');
    }

    public function login()
    {
        if (!is_post())
            return template('login.html');

        $username = trim(get_form('username'));
        $password = get_form('password');

        $user = User::get_user($username);
        if (!$user) {
            flash('User doesn\'t exist.', 'fail');
            return redirect();
        }
        if (!$user->check_password($password)) {
            flash('Password is wrong.', 'fail');
            return redirect('?username='.urlencode($username));
        }

        $_SESSION['user'] = $user;
        $redirect_to = get_form('redirect_to');
        if (!$redirect_to)
            $redirect_to = '/';
        return redirect($redirect_to);
    }

    public function logout()
    {
        unset($_SESSION['user']);
        return redirect('/');
    }

    public function profile()
    {
        check_login();

        $user = $_SESSION['user'];
        if (!is_post()) {
            return template('profile.html', array(
                'username' => $user->username,
                'email' => $user->email
            ));
        }

        try {
            $user->username = trim(get_form('username'));
            $user->email = trim(get_form('email'));
            $newpassword = get_form('newpassword');
            if ($newpassword) {
                $password = get_form('password');
                if (!$user->check_password($password)) {
                    flash('Password is wrong.', 'fail');
                    redirect();
                }
                $user->password = $newpassword;
            }
        } catch (Exception $e) {
            flash('Invalid username or email.', 'fail');
            return redirect();
        }
        if (!$user->put_info()) {
            flash('Fail to save profile.', 'error');
            return redirect();
        }

        flash('Profile updated successfully.', 'success');
        return redirect('/user/');
    }
}

?>
