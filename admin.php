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

        $this->output = 'invalid';
        if (!checkSecurityToken()) return;
        if (!is_array($_REQUEST['cmd'])) return;
        echo '<pre>' . print_r($_REQUEST, 1) . '</pre>';
        // verify valid values
        switch (key($_REQUEST['cmd'])) {
            case 'ini' :
                $this->output = 'ini';
                break;
            case 'page' :
                $this->output = 'page';
                break;                
             case 'help':              
                $this->output = shell_exec(NEWPAGETPL_CMDL  .'-h') ; 
                break;                
        }

    } 
    /**
     * output appropriate html
     */
    function html()
    {
        ptln('<p>' . preg_replace("/\n/","<br />", $this->output)) . '</p>';
        ptln('<form action="' . wl($ID) . '" method="post">');

        // output hidden values to ensure dokuwiki will return back to this plugin
        ptln('  <input type="hidden" name="do"   value="admin" />');
        ptln('  <input type="hidden" name="page" value="' . $this->getPluginName() . '" />');
        formSecurityToken();
        
        ptln($this->getLang('select-ini') .': <select name="ini_file">');
        $ini_files = $this->ini_files();
        ptln($ini_files);
        ptln('</select>&nbsp;&nbsp;');
        ptln($this->getLang('templ') .':&nbsp;<input type="textbox" name="template"/>&nbsp;&nbsp;');
        ptln($this->getLang('page') .':&nbsp;<input type="textbox" name="id"/>&nbsp;&nbsp;');
        ptln($this->getLang('userrelpl') . ':&nbsp;<input type="textbox" name="userrelpl"/>');        
        
        ptln('<div style="line-height:2"><input type="submit" name="cmd[submit]"  value="' . $this->getLang('btn_submit') . '" />');
        ptln('<input type="submit" name="cmd[help]"  value="' . $this->getLang('btn_help') . '" /></div>');
        ptln('</form>');
    }

    function ini_files()
    {
        $lib = DOKU_INC . 'lib/plugins/newpagetemplate/';
        $files = scandir($lib);
        $opt_str = "<option 'none'>".$this->getLang('no_selection')."</option>";
        foreach ($files as $file) {
            if (preg_match("/\.ini$/", $file)) {
                $opt_str .= "<option value = '$file'>$file</option>";
            }
        }
        return ($opt_str);
    }
}