<?php

/**
 * Valid email address
 *
 * @see http://www.linuxjournal.com/article/9585
 *
 * @param string $email
 * @return bool
 */
function is_valid_email($email)
{
    // whether exists @
    $at_index = strrpos($email, '@');
    if ($at_index === false)
        return false;
    // seperate information
    $local = substr($email, 0, $at_index);
    $domain = substr($email, $at_index + 1);
    $local_len = strlen($local);
    $domain_len = strlen($domain);
    // check if valid
    if ($local_len < 1 || $local_len > 64)
        return false;
    if ($domain_len < 1 || $domain_len > 255)
        return false;
    if ($local[0] == '.' || $local[$local_len - 1] == '.')
        return false;
    if (strpos($local, '..') !== false)
        return false;
    if (strpos($domain, '..') !== false)
        return false;
    if (!preg_match('/^[A-Za-z0-9.-]+[A-Za-z0-9]$/', $domain))
        return false;
    if (!preg_match('/^"[^"]+"$/', $local)) {
        if (!preg_match('/^[A-Za-z0-9!#$%&\'*+\\/=?^_`{|}~.-]+$/',
                substr($local, 1, -1)))
            return false;
    }
    if (!(checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A')))
        return false;
    return true;
}

/**
 * Send email
 *
 * @param string $from
 * @param string $to
 * @param string $subject
 * @param string $message
 * @return bool
 */
function send_mail($from, $to, $subject, $message, $reply_to = false)
{
    preg_match('/<(.+)>/', $from, $from_data);
    $from_email = $from_data ? $from_data[1] : $from;
    if ($reply_to === false)
        $reply_to = $from_email;
    $headers = <<<EOH
From: $from
Reply-To: $reply_to
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
EOH;
    $subject = '=?UTF-8?B?'.base64_encode($subject).'?=';
    return mail($to, $subject, $message, $headers, '-f '.$from_email);
}

?>
