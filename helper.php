<?php
if(!defined('DOKU_INC')) die();

class helper_plugin_newpagetemplate extends DokuWiki_Plugin {
    public $template;
    function init($opts,$options) {      
      //  echo print_r($opts,1) . "\n"; return; 
        $page=$opts['page'];
        $usrreplace=$opts['usrrepl'];
        $template=$opts['tmpl'];
        $overwrite=$opts['overwr'];
        $ini=$opts['ini'];
        $user = $opts['user'];
      //  echo "$user // ini $ini" . "\n"; exit;
        if(!empty($template)) {
            $template = wikiFN($template);
            echo "Template: $template \n";
            $tpl = $this->pagefromtemplate($opts['tmpl'],$opts['page'],$opts['usrrepl'],$opts['user']);
           //$this->writePage($opts['page'],$tpl);
            echo $tpl . "\n";        
        }   
        else if(isset($opts['ini'])) {
             $this->output_ini($user);
        }
       //$this->writePage($page,$tpl);
    

     }
    
    function writePage($page,$tpl) {
       $file = wikiFn($page);   
       io_saveFile($file, $tpl, false);
       chown($file, 'www-data');       
       chgrp($file, 'www-data');
       chmod($file, 0664);
    }

    function pagefromtemplate($template,$page,$newpagevars,$user = "") {      
     
          global $conf;
          global $INFO;
          $ID = $page;
        
          $tpl = io_readFile(wikiFN($template));
     
          if($this->getConf('userreplace')) {
            $stringvars =
                array_map(function($v) { return explode(",",$v,2);}, explode(';',$newpagevars)); 
            foreach($stringvars as $value) {
                 $tpl = str_replace(trim($value[0]),hsc(trim($value[1])),$tpl);
            }
         }

          if($this->getConf('standardreplace')) {           
            $file = noNS($ID);       
            $page = cleanID($file) ;
            if($this->getConf('prettytitles')) {        
                $title= str_replace('_',' ',$page);
            }
           else {
               $title = $page;
           }
             if(class_exists('\dokuwiki\\Utf8\PhpString')) {
                $ucfirst = '\dokuwiki\Utf8\PhpString::ucfirst';
                $ucwords = '\dokuwiki\\Utf8\PhpString::ucwords';
                $ucupper = '\dokuwiki\\Utf8\PhpString::strtoupper';
              
            }
           else {
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
                                  $event->name ,
                               ), $tpl);
     
            // we need the callback to work around strftime's char limit
            $tpl = preg_replace_callback('/%./',create_function('$m','return strftime($m[0]);'),$tpl);
          }
          if($this->getConf('skip_unset_macros')) {
              $tpl = preg_replace("/@.*?@/ms","",$tpl);
          }
          return $tpl;
      }

    function output_ini($opts) {
        $ini_file = DOKU_INC .'lib/plugins/newpagetemplate/newpage.ini';
        $ini = parse_ini_file($ini_file,1);
        $templ = array_keys($ini);
        foreach($templ AS $t) {           
            $pages = $ini[$t]['page'];
            $newpagevars = $ini[$t]['newpagevars'];
            if(is_array($newpagevars)) {
                echo "$t\n";
                $this->process_array($pages,$newpagevars, $t,$user);
            }
            else { 
                echo "$t\n";
                $this->process_single($pages,$newpagevars, $t,$user);
            }
        }
    }
    function process_array($pages,$newpagevars, $tpl,$user) {       
          for($i = 0; $i < count($pages); $i++) {
              $res = $this->pagefromtemplate($tpl,$pages[$i],$newpagevars[$i],$user); 
              echo "TEMPLATE: ". $res . "\n";
              echo "\n===================\n";
          }  

      }
      
    function process_single($pages,$newpagevars, $tpl,$user) {  
          for($i = 0; $i < count($pages); $i++) {
              $res = $this->pagefromtemplate($tpl,$pages[$i],$newpagevars,$user); 
              echo "TEMPLATE: ". $res . "\n";
              echo "\n===================\n";
          }
      }

}