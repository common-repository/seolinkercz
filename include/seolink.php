<?php

class SeolinkClient {
    var $sl_version           = '0.0.1';
    var $sl_verbose           = false;
    var $sl_charset           = 'DEFAULT';
    var $sl_use_ssl           = false;
    var $sl_server            = 'db.seolinker.cz';
    var $sl_cache_lifetime    = 3600;
    var $sl_cache_reloadtime  = 300;
    var $sl_links_db_file     = '';
    var $sl_links             = array();
    var $sl_links_page        = array();
    var $sl_links_delimiter   = '';
    var $sl_error             = '';
    var $sl_host              = '';
    var $sl_request_uri       = '';
    var $sl_fetch_remote_type = '';
    var $sl_socket_timeout    = 6;
    var $sl_force_show_code   = false;
    var $sl_multi_site        = false;
    var $sl_is_static         = false;
    var $sl_ignore_tailslash  = false;

    function SeolinkClient($options = null) {
        $host = '';

        if (is_array($options)) {
            if (isset($options['host'])) {
                $host = $options['host'];
            }
        } elseif (strlen($options) != 0) {
            $host = $options;
            $options = array();
        } else {
            $options = array();
        }

        if (strlen($host) != 0) {
            $this->sl_host = $host;
        } else {
            $this->sl_host = $_SERVER['HTTP_HOST'];
        }

        $this->sl_host = preg_replace('{^https?://}i', '', $this->sl_host);
        $this->sl_host = preg_replace('{^www\.}i', '', $this->sl_host);
        $this->sl_host = strtolower( $this->sl_host);

        if (isset($options['is_static']) && $options['is_static']) {
            $this->sl_is_static = true;
        }

        if (isset($options['ignore_tailslash']) && $options['ignore_tailslash']) {
            $this->sl_ignore_tailslash = true;
        }

        if (isset($options['request_uri']) && strlen($options['request_uri']) != 0) {
            $this->sl_request_uri = $options['request_uri'];
        } else {
            if ($this->sl_is_static) {
                $this->sl_request_uri = preg_replace( '{\?.*$}', '', $_SERVER['REQUEST_URI']);
                $this->sl_request_uri = preg_replace( '{/+}', '/', $this->sl_request_uri);
            } else {
                $this->sl_request_uri = $_SERVER['REQUEST_URI'];
            }
        }

        $this->sl_request_uri = rawurldecode($this->sl_request_uri);

        if (isset($options['multi_site']) && $options['multi_site'] == true) {
            $this->sl_multi_site = true;
        }

        if ((isset($options['verbose']) && $options['verbose']) ||
            isset($this->sl_links['__seolink_debug__'])) {
            $this->sl_verbose = true;
        }

        if (isset($options['charset']) && strlen($options['charset']) != 0) {
            $this->sl_charset = $options['charset'];
        }

        if (isset($options['fetch_remote_type']) && strlen($options['fetch_remote_type']) != 0) {
            $this->sl_fetch_remote_type = $options['fetch_remote_type'];
        }

        if (isset($options['socket_timeout']) && is_numeric($options['socket_timeout']) && $options['socket_timeout'] > 0) {
            $this->sl_socket_timeout = $options['socket_timeout'];
        }

        if ((isset($options['force_show_code']) && $options['force_show_code']) ||
            isset($this->sl_links['__seolink_debug__'])) {
            $this->sl_force_show_code = true;
        }

        if (!defined('SEOLINK_USER')) {
            return $this->raise_error("Constant SEOLINK_USER is not defined.");
        }

        $this->load_links();
    }

    function load_links() {
        if ($this->sl_multi_site) {
            $this->sl_links_db_file = dirname(__FILE__) . '/seolink.' . $this->sl_host . '.links.db';
        } else {
            $this->sl_links_db_file = dirname(__FILE__) . '/seolink.links.db';
        }

        if (!is_file($this->sl_links_db_file)) {
            if (@touch($this->sl_links_db_file, time() - $this->sl_cache_lifetime)) {
                @chmod($this->sl_links_db_file, 0666);
            } else {
                return $this->raise_error("There is no file " . $this->sl_links_db_file  . ". Fail to create. Set mode to 777 on the folder.");
            }
        }

        if (!is_writable($this->sl_links_db_file)) {
            return $this->raise_error("There is no permissions to write: " . $this->sl_links_db_file . "! Set mode to 777 on the folder.");
        }

        @clearstatcache();

        if (filemtime($this->sl_links_db_file) < (time()-$this->sl_cache_lifetime) || 
           (filemtime($this->sl_links_db_file) < (time()-$this->sl_cache_reloadtime) && filesize($this->sl_links_db_file) == 0)) {

            @touch($this->sl_links_db_file, time());

            $path = '/' . SEOLINK_USER . '/' . strtolower( $this->sl_host ) . '/' . strtoupper( $this->sl_charset);

            if ($links = $this->fetch_remote_file($this->sl_server, $path)) {
                if (substr($links, 0, 12) == 'FATAL ERROR:') {
                    $this->raise_error($links);
                } else if (@unserialize($links) !== false) {
                    $this->sl_write($this->sl_links_db_file, $links);
                } else {
                    $this->raise_error("Cann't unserialize received data.");
                }
            }
        }

        $links = $this->sl_read($this->sl_links_db_file);
        $this->sl_file_change_date = gmstrftime ("%d.%m.%Y %H:%M:%S",filectime($this->sl_links_db_file));
        $this->sl_file_size = strlen( $links);
        if (!$links) {
            $this->sl_links = array();
            $this->raise_error("Empty file.");
        } else if (!$this->sl_links = @unserialize($links)) {
            $this->sl_links = array();
            $this->raise_error("Cann't unserialize data from file.");
        }

        if (isset($this->sl_links['__seolink_delimiter__'])) {
            $this->sl_links_delimiter = $this->sl_links['__seolink_delimiter__'];
        }

        $sl_links_temp=array();
        foreach($this->sl_links as $key=>$value){
          $sl_links_temp[rawurldecode($key)]=$value;
        }
        $this->sl_links=$sl_links_temp;
        if ($this->sl_ignore_tailslash && $this->sl_request_uri[strlen($this->sl_request_uri)-1]=='/') $this->sl_request_uri=substr($this->sl_request_uri,0,-1);
	    $this->sl_links_page=array();
        if (array_key_exists($this->sl_request_uri, $this->sl_links) && is_array($this->sl_links[$this->sl_request_uri])) {
            $this->sl_links_page = array_merge($this->sl_links_page, $this->sl_links[$this->sl_request_uri]);
        }
	    if ($this->sl_ignore_tailslash && array_key_exists($this->sl_request_uri.'/', $this->sl_links) && is_array($this->sl_links[$this->sl_request_uri.'/'])) {
            $this->sl_links_page =array_merge($this->sl_links_page, $this->sl_links[$this->sl_request_uri.'/']);
        }

        $this->sl_links_count = count($this->sl_links_page);
    }

    function return_links($n = null) {
        $result = '';
        if (isset($this->sl_links['__seolink_start__']) && strlen($this->sl_links['__seolink_start__']) != 0 &&
            (in_array($_SERVER['REMOTE_ADDR'], $this->sl_links['__seolink_robots__']) || $this->sl_force_show_code)
        ) {
            $result .= $this->sl_links['__seolink_start__'];
        }

        if (isset($this->sl_links['__seolink_robots__']) && in_array($_SERVER['REMOTE_ADDR'], $this->sl_links['__seolink_robots__']) || $this->sl_verbose) {

            if ($this->sl_error != '') {
                $result .= $this->sl_error;
            }

            $result .= '<!--REQUEST_URI=' . $_SERVER['REQUEST_URI'] . "-->\n"; 
            $result .= "\n<!--\n"; 
            $result .= 'L ' . $this->sl_version . "\n"; 
            $result .= 'REMOTE_ADDR=' . $_SERVER['REMOTE_ADDR'] . "\n"; 
            $result .= 'request_uri=' . $this->sl_request_uri . "\n"; 
            $result .= 'charset=' . $this->sl_charset . "\n"; 
            $result .= 'is_static=' . $this->sl_is_static . "\n"; 
            $result .= 'multi_site=' . $this->sl_multi_site . "\n"; 
            $result .= 'file change date=' . $this->sl_file_change_date . "\n";
            $result .= 'sl_file_size=' . $this->sl_file_size . "\n";
            $result .= 'sl_links_count=' . $this->sl_links_count . "\n";
            $result .= 'left_links_count=' . count($this->sl_links_page) . "\n";
            $result .= 'n=' . $n . "\n"; 
            $result .= '-->'; 
        }

        if (is_array($this->sl_links_page)) {
            $total_page_links = count($this->sl_links_page);

            if (!is_numeric($n) || $n > $total_page_links) {
                $n = $total_page_links;
            }

            $links = array();

            for ($i = 0; $i < $n; $i++) {
                $links[] = array_shift($this->sl_links_page);
            }

            if ( count($links) > 0 && isset($this->sl_links['__seolink_before_text__']) ) {
               $result .= $this->sl_links['__seolink_before_text__'];
            }

            $result .= implode($this->sl_links_delimiter, $links);

            if ( count($links) > 0 && isset($this->sl_links['__seolink_after_text__']) ) {
               $result .= $this->sl_links['__seolink_after_text__'];
            }
        }
        if (isset($this->sl_links['__seolink_end__']) && strlen($this->sl_links['__seolink_end__']) != 0 &&
            (in_array($_SERVER['REMOTE_ADDR'], $this->sl_links['__seolink_robots__']) || $this->sl_force_show_code)
        ) {
            $result .= $this->sl_links['__seolink_end__'];
        }
        return $result;
    }

    function fetch_remote_file($host, $path) {
        $user_agent = 'Seolink Client PHP ' . $this->sl_version;

        @ini_set('allow_url_fopen', 1);
        @ini_set('default_socket_timeout', $this->sl_socket_timeout);
        @ini_set('user_agent', $user_agent);

        if (
            $this->sl_fetch_remote_type == 'file_get_contents' || (
                $this->sl_fetch_remote_type == '' && function_exists('file_get_contents') && ini_get('allow_url_fopen') == 1
            )
        ) {
            if ($data = @file_get_contents('http://' . $host . $path)) {
                return $data;
            }
        } elseif (
            $this->sl_fetch_remote_type == 'curl' || (
                $this->sl_fetch_remote_type == '' && function_exists('curl_init')
            )
        ) {
            if ($ch = @curl_init()) {
                @curl_setopt($ch, CURLOPT_URL, 'http://' . $host . $path);
                @curl_setopt($ch, CURLOPT_HEADER, false);
                @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->sl_socket_timeout);
                @curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

                if ($data = @curl_exec($ch)) {
                    return $data;
                }

                @curl_close($ch);
            }
        } else {
            $buff = '';
            $fp = @fsockopen($host, 80, $errno, $errstr, $this->sl_socket_timeout);
            if ($fp) {
                @fputs($fp, "GET {$path} HTTP/1.0\r\nHost: {$host}\r\n");
                @fputs($fp, "User-Agent: {$user_agent}\r\n\r\n");
                while (!@feof($fp)) {
                    $buff .= @fgets($fp, 128);
                }
                @fclose($fp);

                $page = explode("\r\n\r\n", $buff);

                return $page[1];
            }
        }

        return $this->raise_error("Cann't connect to server: " . $host . $path);
    }

    function sl_read($filename) {
        $fp = @fopen($filename, 'rb');
        @flock($fp, LOCK_SH);
        if ($fp) {
            clearstatcache();
            $length = @filesize($filename);
            if(get_magic_quotes_gpc()){
                $mqr = get_magic_quotes_runtime();
                set_magic_quotes_runtime(0);
            }
            if ($length) {
                $data = @fread($fp, $length);
            } else {
                $data = '';
            }
            if(isset($mqr)){
                set_magic_quotes_runtime($mqr);
            }
            @flock($fp, LOCK_UN);
            @fclose($fp);

            return $data;
        }

        return $this->raise_error("Cann't get data from the file: " . $filename);
    }

    function sl_write($filename, $data) {
        $fp = @fopen($filename, 'wb');
        if ($fp) {
            @flock($fp, LOCK_EX);
            $length = strlen($data);
            @fwrite($fp, $data, $length);
            @flock($fp, LOCK_UN);
            @fclose($fp);

            if (md5($this->sl_read($filename)) != md5($data)) {
                return $this->raise_error("Integrity was breaken while writing to file: " . $filename);
            }

            return true;
        }

        return $this->raise_error("Cann't write to file: " . $filename);
    }

    function raise_error($e) {
        $this->sl_error = '<!--ERROR: ' . $e . '-->';
        return false;
    }

    
}

?>
