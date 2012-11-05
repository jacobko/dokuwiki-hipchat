<?php
/**
 * DokuWiki Plugin HipChat (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Jeremy Ebler <jebler@gmail.com> 2011-09-29
 *
 * DokuWiki log: https://github.com/cosmocode/log.git
 * @author  Adrian Lang <lang@cosmocode.de> 2010-03-28
 * 
 * Hippy: https://github.com/rcrowe/Hippy.git
 * @author Rob Crowe <rcrowe@github>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once 'lib/hippy/Hippy.php';
require_once DOKU_PLUGIN.'action.php';

class action_plugin_hipchat extends DokuWiki_Action_Plugin {

    function register(&$controller) {
       $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_action_act_preprocess');
    }

    function handle_action_act_preprocess(&$event, $param) {

        if (isset($event->data['save'])) {
            if ($event->data['save'] == 'Save') {
                $this->handle();
            }
        }
        return;
    }

    private function handle() {
    	global $SUM;
		global $INFO;
		
		$fullname = $INFO['userinfo']['name'];
		$username = $INFO['client'];
		$page     = $INFO['namespace'] . $INFO['id'];
		$summary  = $SUM;
		$minor    = (boolean) $_REQUEST['minor'];

        /* Namespace filter */
        $ns = $this->getConf('hipchat_namespaces');
        if (!empty($ns)) {
            $namespaces = explode(',', $ns);
            $current_namespace = explode(':', $INFO['namespace']);
            if (!in_array($current_namespace[0], $namespaces)) {
                return;
            }
        }

        $room = $this->getConf('hipchat_room')
        /* Namespace-Room filter */
        $nsr = $this->getConf('hipchat_namespaceroom');
        if (!empty($nsr)) {
            $namespace_room_list = explode(',', $nsr);
            $namespace_rooms = array();
            foreach ($namespace_room_list as $namespace_room_rule) {
                $namespace_room_rule = explode('="', $namespace_room_rule);
                $namespace_rooms[$namespace_room_rule[0]] = $namespace_room_rule[1];
            }

            $current_namespace = explode(':', $INFO['namespace']);
            if (array_key_exists($current_namespace[0], $namespace_rooms)) {
                $room = $namespace_rooms[$current_namespace[0]];
            }
        }

        $config = array(
                'token'      => $this->getConf('hipchat_token'),
                'room'       => $room,
                'from'       => $this->getConf('hipchat_name'));
        Hippy::config($config);

        $say = '<b>' . $fullname . '</b> updated the Wikipage <b><a href="' . $this->urlize() . '">' . $INFO['id'] . '</a></b>';
		if ($minor) $say = $say . ' [minor edit]';
        if ($summary) $say = $say . '<br /><em>' . $summary . '</em>';
		
		Hippy::speak($say, array('notify' => $minor));
    }

    /* Make our URLs! */
    private function urlize() {

        global $INFO;
        global $conf;
        $page = $INFO['id'];

        switch($conf['userewrite']) {
            case 0:
                $url = DOKU_URL . "doku.php?id=" . $page;
                break;
            case 1:
                if ($conf['useslash']) {
                    $page = str_replace(":", "/", $page);
                }
                $url = DOKU_URL . $page;
                break;
            case 2:
                if ($conf['useslash']) {
                    $page = str_replace(":", "/", $page);
                }
                $url = DOKU_URL . "doku.php/" . $page;
                break;
        }
        return $url;
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
