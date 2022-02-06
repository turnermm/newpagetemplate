<?php
define("NEWPAGETPL_CMDL", 'php ' . DOKU_INC . 'bin/plugin.php newpagetemplate ');

class admin_plugin_newpagetemplate extends DokuWiki_Admin_Plugin
{

    private $output = '';
    private $help = false;

    /**
     * handle user request
     */
    function handle()
    {

        if (!isset($_REQUEST['cmd'])) return;   // first time - nothing to do
        
        $this->output = '';
        if (!checkSecurityToken()) return;
        if (!is_array($_REQUEST['cmd'])) return;
       // echo '<pre>' . print_r($_REQUEST, 1) . '</pre>';       
       
        global $INPUT;
        $ini = $INPUT->str('ini_file','none');
        $tpl = $INPUT->str('tpl_file','none');
        $nosave = $INPUT->str('nosave','none');      
        $userepl = $INPUT->str('userrelpl','none');
        $tplns = $this->getConf('default_tplns');
        $user = $INPUT->server->str('REMOTE_USER', 'none');

        $this->help = false;
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
                    $cmdL .= " -s admin ";
                    if($nosave == 'on') {                    
                        $cmdL .= " -n true ";
                    } 
                    if($user != 'none') {
                      $cmdL .= " -c $user ";
                    }
                    $this->output = shell_exec(NEWPAGETPL_CMDL  . $cmdL) ;
                    $this->help = true; 
                }
                else if($ini != 'none') {                    
                    $cmdL = " -i $ini";     
                    if($userepl != 'none') {
                        $cmdL .= " -u \"$userepl\" ";                        
                    }   
                    $cmdL .= " -s admin ";
                    if($nosave != 'none') {                    
                        $cmdL .= " -n $nosave ";
                    }
                    if($user != 'none') {
                      $cmdL .= " -c $user ";
                    }
                    $this->output = shell_exec(NEWPAGETPL_CMDL  . $cmdL) ;
                    $this->help = true; 
                }
                $this->output = preg_replace("/\n+/","<br />", $this->output);
                break;                
             case 'help': 
                $this->help = true;               
                $this->output = shell_exec(NEWPAGETPL_CMDL  .'-h') ; 
                $this->output = preg_replace("/\n\n/","<br />", htmlentities($this->output));                 
                $this->output = preg_replace("/(-\w\,\s+--\w+)/","<span style='color:blue;'>$1</span>",$this->output);               
                $this->output = preg_replace("/browser]]/","browser]]",$this->output);
                $this->output = preg_replace("/OPTIONS:/","OPTIONS:<br />",$this->output);
                $this->output = preg_replace("/https:\/\/www.dokuwiki.org\/plugin:newpagetemplate/",
                "<a href='https://www.dokuwiki.org/plugin:newpagetemplate'>" . 
                'https://www.dokuwiki.org/plugin:newpagetemplate</a>',$this->output);
                  
                break;                
        }

    } 
    /**
     * output appropriate html
     */
    function html()
    {
    
       ptln('<div id="nptpl_howto" style="display:none;border:1px black solid;padding:12px 12px 12px 8px;height:400px;overflow:auto;">' );      
       ptln('<button  style = "float:right;" onclick="nptpl_toggle(\'#nptpl_howto\')">' . $this->getLang('close'). '</button>&nbsp;' . $this->locale_xhtml('howto'));
       ptln('<button  style = "float:right" onclick="nptpl_toggle(\'#nptpl_howto\')">' . $this->getLang('close'). '</button>&nbsp;<br /><br /></div>');
  
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
               
        ptln('<input type = "button" onclick=" nptpl_toggle(\'#nptpl_howto\')" value ="'. $this->getLang('howto') .'">&nbsp;');
        ptln('<input type="submit" name="cmd[help]"  value="' . $this->getLang('btn_help') . '" /></div>'); 
        ptln('<div>'.$this->getLang('nosave') . '&nbsp;<input type="radio" checked  name="nosave" value = "true"/>&nbsp;');
        ptln($this->getLang('overwrite') . '&nbsp;<input type="radio" name="nosave" value = "false"/>&nbsp;');        
        ptln($this->getLang('existing') . '&nbsp;<input type="radio" name="nosave" value="existing"/></div>');     

        ptln('</form>');     
 
        if($this->help) {
            $display = "block";
        }
        else $display = "none";       
       
        ptln('<br /><div id="nptpl_output" style="display:'. $display .'; border:1px black solid;padding:12px 12px 12px 8px;height:400px;overflow:auto;">' . $this->output);
        ptln('<button style = "display:'. $display .';" onclick=" nptpl_toggle(\'#nptpl_output\')">'. $this->getLang('close') .'</button>&nbsp;');        
        ptln('</div>');  
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