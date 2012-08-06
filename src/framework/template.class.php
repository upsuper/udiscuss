<?php

class Template
{
    const VIEW_PREFIX = 'View_';
    const STATEMENT_BEGIN = '{%';
    const STATEMENT_END = '%}';
    const ECHO_BEGIN = '{{';
    const ECHO_END = '}}';
    const VIEW_PATH = '/views';

    private static $compiled = array();

    /**
     * Private construct function to prevent instantiation
     */
    private function __construct()
    {
    }

    /**
     * Return view class name of given template file
     *
     * @param string $tpl_file
     * @return string
     */
    public static function get_view_name($tpl_file)
    {
        return self::VIEW_PREFIX.md5($tpl_file);
    }

    /**
     * Translate single variable
     *
     * $.var => $this->var
     * $var.a.b => $var['a']['b']
     *
     * @param string $var
     * @return string
     */
    public static function translate_var($var)
    {
        $vars = explode('.', $var);
        if ($vars[0] == '$') {
            array_shift($vars);
            $vars[0] = '$this->'.$vars[0];
        }
        $first = array_shift($vars);
        return $first.implode(array_map(function ($v) {
            return "['$v']";
        }, $vars));
    }

    /**
     * Translate variables in code
     *
     * @see Template::translate_var
     * @param string $code
     * @return string
     */
    public static function translate_vars($code)
    {
        return preg_replace_callback(
            '/\'(\\\\.|[^\\\\])+\'|'.
            '"(\\\\.|[^\\\\])+"|'.
            '\$[A-Za-z0-9_\.]+/',
            function ($match) {
                $match = $match[0];
                switch ($match[0]) {
                case '$':
                    return Template::translate_var($match);
                case '"':
                    // TODO
                    break;
                case "'":
                    return $match;
                }
            } , $code);
    }

    /**
     * Compile template
     *
     * @param string $tpl_file Template file path relative to TPL_PATH
     * @return View class name corresponding to given template file
     */
    public static function compile($tpl_file)
    {
        $file = TPL_PATH.'/'.$tpl_file;
        $view_name = self::get_view_name($tpl_file);
        if (isset(self::$compiled[$tpl_file]))
            return $view_name;
        $dest = CACHE_PATH.self::VIEW_PATH.'/'.
            substr($view_name, strlen(self::VIEW_PREFIX)).'.php';

        // check last modified time
        if (file_exists($dest))
            $dest_stat = stat($dest);
        $file_stat = stat($file);
        if (isset($dest_stat) && $dest_stat['mtime'] >= $file_stat['mtime']) {
            self::$compiled[$tpl_file] = $view_name;
            return $view_name;
        }

        // initial compiling
        static $pat_stat = null, $pat_echo = null;
        if (!$pat_stat || !$pat_echo) {
            $pat_stat = preg_quote(self::STATEMENT_BEGIN, '/').
                '(.+?)'.
                preg_quote(self::STATEMENT_END, '/');
            $pat_echo = preg_quote(self::ECHO_BEGIN, '/').
                '(.+?)'.
                preg_quote(self::ECHO_END, '/');
        }
        $tpl = file_get_contents($file);
        $length = strlen($tpl);
        $compiled = array();
        $blocks = array();
        self::$compiled[$tpl_file] = $view_name;

        // check extends
        $pos = 0;
        $baseclass = 'View';
        if (preg_match('/^\s*'.$pat_stat.'\s*/', $tpl, $match)) {
            $content = trim($match[1]);
            $pos += strlen($match[0]);
            if (preg_match('/^extends\s+(\'|")(.+)\1$/', $content, $match)) {
                $path = $match[2];
                $basefile = realpath(dirname($file).'/'.$path);
                $len = strlen(TPL_PATH);
                if (substr($basefile, 0, $len + 1) == TPL_PATH.'/') {
                    $basefile = substr($basefile, $len + 1);
                    $baseclass = self::compile($basefile);
                } else {
                    throw new Exception('Template out of TPL_PATH');
                }
            } else {
                $pos = 0;
            }
        }

        // generate specific token
        $token = pack('LL', mt_rand(), mt_rand());
        while (strpos($tpl, $token) !== false || start_with($token, '<?php '))
            $token = pack('LL', mt_rand(), mt_rand());

        // compile blocks
        $p_stat = '/'.$pat_stat.'/';
        $p_echo = '/'.$pat_echo.'/';
        $stack = array('');
        $cur_block = '';
        while (true) {
            $next_stat = preg_match($p_stat, $tpl, $m_stat,
                PREG_OFFSET_CAPTURE, $pos);
            $next_echo = preg_match($p_echo, $tpl, $m_echo,
                PREG_OFFSET_CAPTURE, $pos);
            $pos_stat = $next_stat ? $m_stat[0][1] : $length;
            $pos_echo = $next_echo ? $m_echo[0][1] : $length;
            if ($pos_stat == $length && $pos_echo == $length) {
                $blocks[$cur_block][] = substr($tpl, $pos);
                $pos = $length;
                break;
            }

            $next_pos = min($pos_stat, $pos_echo);
            $blocks[$cur_block][] = substr($tpl, $pos, $next_pos - $pos);
            $pos = $next_pos;

            if ($next_pos == $pos_stat) {
                $s = trim($m_stat[1][0]);
                $pos += strlen($m_stat[0][0]);
                if (preg_match('/^block\s+([a-z][a-z0-9_]+)$/', $s, $match)) {
                    $blockname = $match[1];
                    $stack[] = $blockname;
                    $blocks[$blockname] = array();
                    $cur_block = $blockname;
                } elseif ($s == 'endblock') {
                    $blockname = array_pop($stack);
                    $code = "\n\tfunction $blockname()\n\t{\n?".">".
                        implode($blocks[$blockname]).
                        "<?php\n\t}\n";
                    $compiled[] = $code;
                    unset($blocks[$blockname]);

                    $cur_block = end($stack);
                    $code = "<?php \$this->$blockname(); ?".">";
                    if ($cur_block === '')
                        $code = $token.$code;
                    $blocks[$cur_block][] = $code;
                } elseif ($s == 'raw') {
                    $endraw = '/'.preg_quote(self::STATEMENT_BEGIN, '/').
                        '\s*endraw\s*'.
                        preg_quote(self::STATEMENT_END, '/').'/';
                    if (!preg_match($endraw, $tpl, $match,
                            PREG_OFFSET_CAPTURE, $pos))
                        throw new Exception('Corresponding endraw not found');
                    $next_pos = $match[0][1];
                    $blocks[$cur_block][] =
                        substr($tpl, $pos, $next_pos - $pos);
                    $pos = $next_pos + strlen($match[0][0]);
                } else {
                    $controls = '/^('.
                        'if|while|else(if)?|for(each)?|switch|case)\b/i';
                    if (substr($s, -1) != ':' && preg_match($controls, $s))
                        $s .= ':';
                    $blocks[$cur_block][] = '<?php '.
                        self::translate_vars($s).' ?'.'>';
                }
            } else {
                $s = trim($m_echo[1][0]);
                $pos += strlen($m_echo[0][0]);
                $blocks[$cur_block][] = '<?php echo '.
                    self::translate_vars($s).' ?'.'>';
            }
        }

        // check if root available
        if (count($stack) > 1)
            throw new Exception('Block "'.end($stack).'" not end');
        if (count($stack) < 1 || $cur_block !== '')
            throw new Exception('Too many "endblock"');
        $has_root = false;
        $root = array_map(function ($v) use ($token) {
            if (start_with($v, $token))
                return substr($v, strlen($token));
            if (strlen(trim($v)) != 0)
                $has_root = true;
            return $v;
        }, $blocks['']);
        if ($baseclass == 'View')
            $has_root = true;
        if ($has_root) {
            $compiled[] = "\n\tfunction _page()\n\t{\n?".">".
                implode($root).
                "<?php\n\t}\n";
        }

        // generate compiled code
        $final = "<?php\n\n".
            "class $view_name extends $baseclass\n{".
            implode($compiled).
            "}\n?".'>';
        file_put_contents($dest, $final);
        return $view_name;
    }
}

?>
