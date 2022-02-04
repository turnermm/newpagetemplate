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
        $browser = $this->colors->wrap('browser', 'cyan');
        $cmdLine = $this->colors->wrap('cmdLine', 'cyan');
        $browser = $this->colors->wrap('browser', 'cyan');
        $nosave = $this->colors->wrap('true', 'cyan'); 
        $false = $this->colors->wrap('false', 'cyan');        
        $existing = $this->colors->wrap('existing', 'cyan');        
        $options->setHelp(
    "[[-p|--page] page_id] | [[-i|--ini] path-to-ini|config] | [[-t|--tpl] template_id] | [[-u|usrrepl] <macros>]\n|[[-s|--screen][cmdLine|browser]]|[[--nosave|-n]true]\n" .                
        "\n\nThis plugin helps to automate the processing of pages that use new page templates. " .
            "The first command line option must be either '$pg' or '$ini'.  For a complete description see" .
            " the newpagetemplate documentation at https://www.dokuwiki.org/plugin:newpagetemplate"  
            );
        $options->registerOption('version', 'print version', 'v');
        $options->registerOption('page', 'Apply the template to the named page id', 'p');
        $options->registerOption('usrrepl', 'newpagevars: Macro/Replacent string: @MACRO@,replacement;@MACRO_2@. . . ', 'u');
        $options->registerOption('tmpl', 'Template to apply to the specified page ', 't');
        $options->registerOption('cliuser', 'User of CLI process', 'c');
        
        $options->registerOption('nosave', 
             "If this option is set to '$nosave', then none of the the pages will be saved; if set to '$existing', " .
             "then existing pages will not be over-written, but all other pages will be created and saved. " .
             " If set to '$false' all pages will saved and existing pages over-written. " .
             "In any case, the output will still be printed to the screen, " .
             "if the screen option is set to $browser.", 'n'); 
              
        $options->registerOption('screen', "Output results to screen; this option takes one of two parameters: either '$browser' or '$cmdLine'." 
        . " The command line output consists of the command line options in raw and parsed format. The browser output" .
        " consists of the parsed page data.", 's');  
        
        $options->registerOption('ini', "Name of an ini file. This file must be stored in the root directory" .
            " of the newpagetemplate plugin. The ini files make it possible to parse and save data to multiple pages. " .
            " Its format and functioning are described on the newpagetemplate" .
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
        $screen = "";
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
                case 'templ':
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
                case 'screen':
                case 's':
                     $screen =  $opts[$i + 1]; 
                     break;
                case 'nosave':
                case 'n':
                     $nosave =  $opts[$i + 1]; 
                     break;                        
            }
        }
        if (!$user) {
            $processUser = posix_getpwuid(posix_geteuid());
            if (isset($processUser) && !empty($processUser['name'])) {
                $user = $processUser['name'];
            }
        }
        if(empty($nosave)) {
            $nosave = 'true';
        }
        return array('page' => $page, 'usrrepl' => $usrrepl, 'tmpl' => $tmpl, 'user' => $user, 'ini' => $ini, 'screen' => $screen,
                     'nosave' => $nosave);
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
