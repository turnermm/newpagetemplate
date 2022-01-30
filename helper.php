<?php
if (!defined('DOKU_INC')) die();

class helper_plugin_newpagetemplate extends DokuWiki_Plugin
{
    public $template;
    private $debug;

    function init($opts, $options, $cli)
    {
        $page = $opts['page'];
        $usrreplace = $opts['usrrepl'];
        $template = $opts['tmpl'];
        $overwrite = $opts['overwr'];
        $ini = $opts['ini'];
        $user = $opts['user'];
        $ini = $opts['ini'];

        if ($this->debug) {
            $cli_opts = $options->getArgs();
            $cli->raw_commandLineOpts($cli_opts, $opts, 1);
            return;
        }

        if (!empty($template)) {
            $template = wikiFN($template);
            if ($this->debug) { 
                echo "\n$t\n\n";
                echo "Template: $template \n";
            }
            $tpl = $this->pagefromtemplate($opts['tmpl'], $opts['page'], $opts['usrrepl'], $opts['user']); 
            if ($this->debug) { 
                echo "\n$t\n\n";
                echo $tpl . "\n";
            }    
        } else if (isset($opts['ini'])) {
            $this->output_ini($user, $ini,$usrreplace);
        }
        $this->writePage($page,$tpl);
    }

    function writePage($page, $tpl)
    {
        $file = wikiFn($page); 
        $summary = 'pagetemplate cli';
        saveWikiText($page, $tpl, $summary, $minor = false);
        $this->setOwnership($file);        
        
        $ns = getNS($page);
        $ns = preg_replace("/\.txt$/","",wikiFn($ns));
        $this->setOwnership($ns,true);       
        
        $meta = metaFN($page,'.meta');
        $this->setOwnership($meta);  
        
        $meta = metaFN($page,'.changes');
        $this->setOwnership($meta);                         
    }

    function setOwnership($page, $ns = false) {
        $owner = $this->getConf('web_owner');
        $group = $this->getConf('web_group');
        chown($page, $owner);
        chgrp($page, 'www-data');
        if($ns) return;
        chmod($page, 0664); 
    }

    function pagefromtemplate($template, $page, $newpagevars, $user = "")
    {

        global $conf;
        global $INFO;
        $ID = $page;

        $tpl = io_readFile(wikiFN($template));

        if ($this->getConf('userreplace')) {
            $stringvars =
                array_map(function ($v) {
                    return explode(",", $v, 2);
                }, explode(';', $newpagevars));
            foreach ($stringvars as $value) {
                $tpl = str_replace(trim($value[0]), hsc(trim($value[1])), $tpl);
            }
        }

        if ($this->getConf('standardreplace')) {
            $file = noNS($ID);
            $page = cleanID($file);
            if ($this->getConf('prettytitles')) {
                $title = str_replace('_', ' ', $page);
            } else {
                $title = $page;
            }
            if (class_exists('\dokuwiki\\Utf8\PhpString')) {
                $ucfirst = '\dokuwiki\Utf8\PhpString::ucfirst';
                $ucwords = '\dokuwiki\\Utf8\PhpString::ucwords';
                $ucupper = '\dokuwiki\\Utf8\PhpString::strtoupper';

            } else {
                $ucfirst = 'utf8_ucfirst';
                $ucwords = 'utf8_ucwords';
                $ucupper = 'utf8_strtoupper';

            }

            $tpl = str_replace(array(
                '@ID@',
                '@NS@',
                '@CURNS@',
                '@!CURNS@',
                '@!!CURNS@',
                '@!CURNS!@',
                '@FILE@',
                '@!FILE@',
                '@!FILE!@',
                '@PAGE@',
                '@!PAGE@',
                '@!!PAGE@',
                '@!PAGE!@',
                '@USER@',
                '@DATE@',
                '@EVENT@'
            ),
                array(
                    $ID,
                    getNS($ID),
                    curNS($ID),
                    $ucfirst(curNS($ID)),
                    $ucwords(curNS($ID)),
                    $ucupper(curNS($ID)),
                    $file,
                    $ucfirst($file),
                    $ucupper($file),
                    $page,
                    $ucfirst($title),
                    $ucwords($title),
                    $ucupper($title),
                    $user,
                    $conf['dformat'],
                    $event->name,
                ), $tpl);

            // we need the callback to work around strftime's char limit
            $tpl = preg_replace_callback('/%./', create_function('$m', 'return strftime($m[0]);'), $tpl);
        }
        if ($this->getConf('skip_unset_macros')) {
            $tpl = preg_replace("/@.*?@/ms", "", $tpl);
        }
        return $tpl;
    }

    function output_ini($user="", $ini, $usrreplace)
    {
        if ($ini == 'config') {
            $ini_file = $this->getConf('default_ini');
            $ini_file = DOKU_INC . "lib/plugins/newpagetemplate/$ini_file";            
        } else {
            $ini_file = DOKU_INC . "lib/plugins/newpagetemplate/$ini";
        }

        $ini = parse_ini_file($ini_file, 1);
        $templ = array_keys($ini);
        foreach ($templ as $t) {
            $pages = $ini[$t]['page'];
            $newpagevars = $ini[$t]['newpagevars'];
            if (is_array($newpagevars)) {
               if ($this->debug) echo "\n$t\n\n";
               $this->process_array($pages, $newpagevars, $t, $user,$usrreplace);
            } else {
                if ($this->debug) echo "\n$t\n\n";
                $this->process_single($pages, $newpagevars, $t, $user,$usrreplace);
            }
        }
    }

    function process_array($pages, $newpagevars, $tpl, $user="",$usrreplace)
    {
        for ($i = 0; $i < count($pages); $i++) {
            if(!empty($usrreplace)) {
                $newpagevars[$i] .= ";$usrreplace";
            }
            $res = $this->pagefromtemplate($tpl, $pages[$i], $newpagevars[$i], $user);
            $this->writePage($pages[$i], $res);
            if ($this->debug) {            
                echo "Output: " . "\n" . $res . "\n";
                echo "\n===================\n";
            }
        }

    }

    function process_single($pages, $newpagevars, $tpl, $user="",$usrreplace)
    {
        /* handles mutiple pages but single instance of newpagevars */
        if(!empty($usrreplace)) {
            $newpagevars .= ";$usrreplace";
        }
        for ($i = 0; $i < count($pages); $i++) {
            $res = $this->pagefromtemplate($tpl, $pages[$i], $newpagevars, $user);
            $this->writePage($pages[$i], $res);
            if ($this->debug) {            
                echo "Output: " . "\n" . $res . "\n";
                echo "\n===================\n";
            }
        }
    }

}