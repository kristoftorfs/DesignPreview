<?php

class DesignPreview extends NetDesign {
    public function GetVersion() {
        return '1.0.0';
    }
    function HasAdmin() {
        return false;
    }

    function GetFriendlyName() {
        return 'Design Preview';
    }

    public function SuppressAdminOutput(&$request) {
        return true;
    }

    public function RegisterRoutes() {
        cms_route_manager::del_static('',$this->GetName());
        $route = new CmsRoute('/preview\/(?P<site_id>.*?)\/(?P<filename>.*?)((?!\.html))$/', $this->GetName(), array('action' => 'default', 'showtemplate' => 'false'));
        cms_route_manager::add_static($route);
        $route = new CmsRoute('/preview\/(?P<site_id>.*?)\/(?P<filename>.*?).html$/', $this->GetName(), array('action' => 'html', 'showtemplate' => 'false'));
        cms_route_manager::add_static($route);
    }

    public function Install() {
        $this->RegisterRoutes();
        return false;
    }

    public function Uninstall() {
        cms_route_manager::del_static('',$this->GetName());
        return false;
    }

    public function Error404() {
        header('Content-Type: text/plain', true, 404);
        echo "404 Not Found.";
        exit;
    }

    public function DoAction($name, $id, $params, $returnid = '') {
        $cms = cmsms();
        if ($name == 'default') {
            $path = cms_join_path($cms->GetConfig()->offsetGet('root_path'), 'netdesign', $params['site_id'], $params['filename']);
            if (!file_exists($path)) $this->Error404();
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            switch($extension) {
                case 'css': $mime = 'text/css'; break;
                case 'js': $mime = 'application/javascript'; break;
            }
            $this->SetContentType($mime);
            readfile($path);
        } else {
            require_once(__DIR__ . '/vendor/autoload.php');
            error_reporting(E_ALL);
            ini_set('display_errors', 'on');
            $html = cms_join_path($cms->GetConfig()->offsetGet('root_path'), 'netdesign', $params['site_id'], 'html', $params['filename'] . '.html');
            if (!file_exists($html)) $this->Error404();
            $_ = cms_join_path($cms->GetConfig()->offsetGet('root_path'), 'netdesign', $params['site_id'], 'preview', '_.php');
            $php = cms_join_path($cms->GetConfig()->offsetGet('root_path'), 'netdesign', $params['site_id'], 'preview', $params['filename'] . '.php');
            $dom = pQuery::parseFile($html);
            if (file_exists($_)) include($_);
            if (file_exists($php)) include($php);
            echo $this->smarty->fetch(sprintf('string:%s', $dom->html()));
        }
    }
}