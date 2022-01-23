<?php
define("NEWPAGETPL_CMDL", 'php ' . DOKU_INC . 'bin/plugin.php newpagetemplate ');

class admin_plugin_newpagetemplate extends DokuWiki_Admin_Plugin
{

    var $output = 'world';

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
            case 'hello' :
                $this->output = 'again';
                break;
            case 'goodbye' :
                $this->output = 'goodbye';
                break;
        }
        
        $this->output = shell_exec(NEWPAGETPL_CMDL  .'-h') ;
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
        ptln('Select ini file<select name="slect-ini">');
        $ini_files = $this->ini_files();
        ptln($ini_files);
        ptln('</select>');
        ptln('  <input type="submit" name="cmd[hello]"  value="' . $this->getLang('btn_hello') . '" />');
        ptln('  <input type="submit" name="cmd[goodbye]"  value="' . $this->getLang('btn_goodbye') . '" />');
        ptln('</form>');
    }

    function ini_files()
    {
        $lib = DOKU_INC . 'lib/plugins/newpagetemplate/';
        $files = scandir($lib);
        $opt_str = "<option 'none'>none</option>";
        foreach ($files as $file) {
            if (preg_match("/\.ini$/", $file)) {
                $opt_str .= "<option value = '$file'>$file</option>";
            }
        }
        return ($opt_str);
    }
}