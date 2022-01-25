<?php
define("NEWPAGETPL_CMDL", 'php ' . DOKU_INC . 'bin/plugin.php newpagetemplate ');

class admin_plugin_newpagetemplate extends DokuWiki_Admin_Plugin
{

    var $output = '';

    /**
     * handle user request
     */
    function handle()
    {

        if (!isset($_REQUEST['cmd'])) return;   // first time - nothing to do
        
        $this->output = '';
        if (!checkSecurityToken()) return;
        if (!is_array($_REQUEST['cmd'])) return;
        echo '<pre>' . print_r($_REQUEST, 1) . '</pre>';
        // verify valid values
        global $INPUT;
        $ini = $INPUT->str('ini_file','none');
        $tpl = $INPUT->str('tpl_file','none');
        $userepl = $INPUT->str('userrelpl','none');
        $tplns = $this->getConf('default_tplns');
      
        switch (key($_REQUEST['cmd'])) { 
            case 'submit' :            
                $this->output = 'submit';             
                if(!empty($_REQUEST['id'])) {
                    if($tpl == 'none') {
                        $this->output = "The -p (--page) option requires a template";
                        return;
                    }                        
                    $cmdL = '-p ' .$_REQUEST['id'] .  " -t $tplns:$tpl";
                    if($userepl != 'none') {
                        $cmdL .= " -u \"$userepl\" ";
                    }
                    $this->output = shell_exec(NEWPAGETPL_CMDL  . $cmdL) ;
                }
                else if($ini != 'none') {                    
                    $cmdL = " -i $ini";     
                    if($userepl != 'none') {
                        $cmdL .= " -u \"$userepl\" ";
                        
                    }                  
                    $this->output = shell_exec(NEWPAGETPL_CMDL  . $cmdL) ;
                }
                $this->output = preg_replace("/\n+/","<br />", htmlentities($this->output));
                break;                
             case 'help':              
                $this->output = shell_exec(NEWPAGETPL_CMDL  .'-h') ; 
                $this->output = preg_replace("/\n\n/","<br />", htmlentities($this->output));                 
                $this->output = preg_replace("/(-\w\,\s+--\w+)/","<span style='color:blue;'>$1</span>",$this->output);                 
                $this->output = preg_replace("/&lt;macros&gt;]/","&lt;macros&gt;]<br />",$this->output);
                break;                
        }

    } 
    /**
     * output appropriate html
     */
    function html()
    {
        
        ptln('<form action="' . wl($ID) . '" method="post">');

        // output hidden values to ensure dokuwiki will return back to this plugin
        ptln('  <input type="hidden" name="do"   value="admin" />');
        ptln('  <input type="hidden" name="page" value="' . $this->getPluginName() . '" />');
        formSecurityToken();
        
        /*Select ini files */
        ptln($this->getLang('select-ini') .': <select name="ini_file">');
        $ini_files = $this->ini_files();
        ptln($ini_files);
        ptln('</select>&nbsp;&nbsp;');        

        /* Select Template */
        ptln($this->getLang('templ') .': <select name="tpl_file">');
        $tpls = $this->templates();
        ptln($tpls);
        ptln('</select>&nbsp;&nbsp;'); 
        
        ptln($this->getLang('page') .':&nbsp;<input type="textbox" name="id"/>&nbsp;&nbsp;');
        ptln($this->getLang('userrelpl') . ':&nbsp;<input type="textbox" name="userrelpl"/>');        
        
        ptln('<div style="line-height:2"><input type="submit" name="cmd[submit]"  value="' . $this->getLang('btn_submit') . '" />');
        ptln('<input type="submit" name="cmd[help]"  value="' . $this->getLang('btn_help') . '" /></div>');
        ptln('</form>');
         
        ptln('<br /><div  style = "overflow: scroll;height:250px;">' . $this->output . '</div>');
    }

    function ini_files()
    {
        $lib = DOKU_INC . 'lib/plugins/newpagetemplate/';
        $files = scandir($lib);
        $opt_str = "<option value = 'none'>".$this->getLang('no_selection')."</option>";
        foreach ($files as $file) {
            if (preg_match("/\.ini$/", $file)) {
                $opt_str .= "<option value = '$file'>$file</option>";
            }
        }
        return ($opt_str);
    }
    function templates() {
        $tplns = $this->getConf('default_tplns');
        $pages = DOKU_INC . 'data/pages/' . $tplns;
        $files = scandir($pages);
        $opt_str = "<option value='none'>".$this->getLang('no_selection')."</option>";
        foreach ($files as $file) {
            if (preg_match("/\.txt$/", $file)) {
                list($id,$ext) = explode('.',$file);
                $opt_str .= "<option value = '$id'>$id</option>";
            }
        }
        return ($opt_str);        
    }
    
}