<?php
if (!defined('DOKU_INC')) die();

use splitbrain\phpcli\Options;

class cli_plugin_newpagetemplate extends DokuWiki_CLI_Plugin
{
    // register options and arguments
    public $newpgdbg = false;

    protected function setup(Options $options)
    {
        // $this->colors->disable();
        $config = $this->colors->wrap('config', 'cyan');
        $pg = $this->colors->wrap('page', 'cyan');
        $ini = $this->colors->wrap('ini', 'cyan');
        $options->setHelp(
        "[[-p|--page] page_id] | [[-i|--ini] path-to-ini|config] | [[-t|--tpl] template_id] | [[-u|usrrepl] <macros>]" .                
        "\n\nThis plugin helps to automate the processing of pages that use new page templates. " .
            "The first command line option must be either '$pg' or '$ini'.  For a complete description see" .
            " the newpagetemplate documentation at https://www.dokuwiki.org/plugin:newpagetemplate\n"  
            );
        $options->registerOption('version', 'print version', 'v');
        $options->registerOption('page', 'Apply the template to the named page id', 'p');
        $options->registerOption('usrrepl', 'Macro/Replacent string: @MACRO@,replacement;@MACRO_2@. . . ', 'u');
        $options->registerOption('tmpl', 'Template to apply to the specified page ', 't');
        $options->registerOption('owner', 'User/owner of current process', 'o');
        $options->registerOption('ini', "Name of an ini file. This file must be stored in the root directory" .
            " of the newpagetemplate plugin. Its format and functioning are described on the newpagetemplate" .
            " plugin page. To use the ini file specified in the plugin's Configuration Settings, this option must be set to '$config'.", 'i');

    }

    protected function main(Options $options)
    {
        print_r($options->getArgs(), 1);

        if ($options->getOpt('page')) {
            $opts = $options->getArgs();
            $clopts = $this->get_commandLineOptions($opts);
            if (!$clopts['page']) {
                $this->commandLineErr('page');
            }
            $this->raw_commandLineOpts($opts, $clopts);
            $helper = plugin_load('helper', 'newpagetemplate', 1, $false);
            $helper->init($clopts, $options, $this);
        } else if ($options->getOpt('ini')) {
            $opts = $options->getArgs();
            $clopts = $this->get_commandLineOptions($opts, 1);
            if (!$clopts['ini']) {
                $this->commandLineErr('ini');
            }
            $this->raw_commandLineOpts($opts, $clopts);
            $helper = plugin_load('helper', 'newpagetemplate', 1, $false);
            $helper->init($clopts, $options, $this);
        } else if ($options->getOpt('version')) {
            $info = $this->getInfo();
            $this->success($info['date']);
        } else {
            echo $options->help();
        }

    }

    function get_commandLineOptions($opts, $config = false)
    {
        if (function_exists('is_countable') && !is_countable($opts)) return;

        $page = "";
        $usrrepl = "";
        $user = "";
        $ini = false;
        if ($config) {
            $ini = array_shift($opts);
        } else $page = array_shift($opts);
        for ($i = 0; $i < count($opts); $i++) {
            $cl_switch = trim($opts[$i], '-');
            switch ($cl_switch) { // leaves space for additional command line options
                case 'u':
                case 'usrrepl':
                    $usrrepl = $opts[$i + 1];
                    break;
                case 't':
                case 'utsrrepl':
                    $tmpl = $opts[$i + 1];
                    break;
                case 'o':
                case 'owner':
                    $user = $opts[$i + 1];
                    break;
                case 'cliuser':
                case 'c':
                    $user = $opts[$i + 1];
                    break;
            }
        }
        if (!$user) {
            $processUser = posix_getpwuid(posix_geteuid());
            if (isset($processUser) && !empty($processUser['name'])) {
                $user = $processUser['name'];
            }
        }

        return array('page' => $page, 'usrrepl' => $usrrepl, 'tmpl' => $tmpl, 'user' => $user, 'ini' => $ini);
    }

    public function raw_commandLineOpts($opts = "", $clopts = "", $dbg = false)
    {
        if (!$this->newpgdbg && !$dbg) return;
        global $argv;
        echo "argv = " . print_r($argv, 1);
        print_r($opts);
        print_r($clopts);
    }

    function commandLineErr($type)
    {
        switch ($type) {
            case 'page':
                $this->colors->ptln("$type error: page id required", 'cyan');
                exit;
            case 'ini':
                $this->colors->ptln("$type error: ini configuration file required", 'cyan');
                exit;
            default:
        }
        return 1;
    }

    /**
     * @param array $array
     * @param int|string $position
     * @param mixed $insert
     */
    function array_insert(&$array, $position, $insert)
    {
        if (is_int($position)) {
            array_splice($array, $position, 0, $insert);
        } else {
            $pos = array_search($position, array_keys($array));
            $array = array_merge(
                array_slice($array, 0, $pos),
                $insert,
                array_slice($array, $pos)
            );
        }
    }

}
