<?php
if(!defined('DOKU_INC')) die();
use splitbrain\phpcli\Options;
 
class cli_plugin_newpagetemplate extends DokuWiki_CLI_Plugin {
    // register options and arguments
    protected function setup(Options $options) {
       // $this->colors->disable();
        $config =  $this->colors->wrap('config','cyan') ;

        $options->setHelp('This plugin helps to automate the processing of pages that use new page templates. ' .
            'The first command line option must be either page or ini.  For a complete description see ' .
            'the newpagetemplate documentation at https://www.dokuwiki.org/plugin:newpagetemplate');
        $options->registerOption('version', 'print version', 'v');
        $options->registerOption('page', 'Apply the template to the named page', 'p');  
        $options->registerOption('usrrepl', 'Macro/Replacent string: @MACRO@,replacement;@MACRO_2@. . . ', 'u');           
        $options->registerOption('tmpl', 'Template to apply to the specified page ', 't');   
        $options->registerOption('owner', 'User/owner of current process', 'o');
        $options->registerOption('ini', "Use ini file. This file must be stored in  the root directory" .
        " of the newpagetemplate plugin. Its format is described on the newpagetemplate" .
        " plugin page. To use the ini file specified in the plugin's Configuration Settings, this must be $config.", 'i');        
        
    }
    
    protected function main(Options $options) {
        print_r($options->getArgs(),1); 
        //print_r($options->readPHPArgv());
      //  global $argv;
       // print_r($argv);
        if($options->getOpt('page')) {          
            $opts = $options->getArgs();          
            $clopts = $this->get_commandLineOptions($opts);               
            $helper = plugin_load('helper','newpagetemplate',1,$false);            
            $helper->init($clopts,$options);
        } 
        else if ($options->getOpt('ini')) {
            $opts = $options->getArgs();           
            $clopts = $this->get_commandLineOptions($opts,1);
            $helper = plugin_load('helper','newpagetemplate',1,$false);
            $helper->init($clopts,$options);
        }
      
        else if ($options->getOpt('version')) {
            $info = $this->getInfo();    
            $this->success($info['date']);
        }  else {
            echo $options->help();
        }

    }
    
    function get_commandLineOptions($opts,$config = false) {
        if(function_exists('is_countable') &&!is_countable($opts)) return;
      echo "opts = " .print_r($opts,1) . "\n";
        $page=""; $usrrepl="";$user = "";$ini = false;
        if($config) {
           $ini = array_shift($opts);          
        }
        else $page = array_shift($opts);
        for($i=0; $i<count($opts); $i++) {
            $cl_switch = trim($opts[$i],'-');
            switch ($cl_switch) { // leaves space for additional command line options
            case 'u':
            case 'usrrepl':
                $usrrepl =  $opts[$i+1];         
                break;
            case 't':
            case 'utsrrepl':
                $tmpl =  $opts[$i+1];         
                break;        
            case 'o':
            case 'owner':
               $user =  $opts[$i+1];         
                break;
            case 'cliuser':
            case 'c':
               $user = $opts[$i+1];
               break; 
            }
        }
        if(!$user) {
            $processUser = posix_getpwuid(posix_geteuid());
            if(isset($processUser) && !empty($processUser['name'])) {
                $user =  $processUser['name'];
            }
        }
                   
          return array('page'=>$page, 'usrrepl'=>$usrrepl,'tmpl'=>$tmpl, 'user'=>$user, 'ini'=>$ini);
        }      
}
    


