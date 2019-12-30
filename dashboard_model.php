<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

/*
 * Create a new user dashboard
 *
 */
class Dashboard
{
    private $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function create($userid)
    {
        $userid = (int) $userid;
        $this->mysqli->query("INSERT INTO dashboard (`userid`,`alias`) VALUES ('$userid','')");
        return $this->mysqli->insert_id;
    }

    public function delete($id)
    {
        $id = (int) $id;
        $result = $this->mysqli->query("DELETE FROM dashboard WHERE id = '$id'");
        return $result;
    }

    public function dashclone($userid, $id)
    {
        $userid = (int) $userid;
        $id = (int) $id;

        // Get content, name and description from origin dashboard
        $result = $this->mysqli->query("SELECT content,name,description,height FROM dashboard WHERE userid = '$userid' AND id='$id'");
        $row = $result->fetch_array();

        // Name for cloned dashboard
        $name = sprintf('%s %s', $row['name'], _('clone'));

        $this->mysqli->query("INSERT INTO dashboard (`userid`,`content`,`name`,`description`,`height`) VALUES ('$userid','{$row['content']}','$name','{$row['description']}','{$row['height']}')");

        return $this->mysqli->insert_id;
    }

    public function get_list($userid, $public, $published)
    {
        $userid = (int) $userid;

        $qB = ""; $qC = "";
        if ($public==true) $qB = " and public=1";
        if ($published==true) $qC = " and published=1";
        if (!$result = $this->mysqli->query("SELECT id, name, alias, description, main, published, public, showdescription, fullscreen FROM dashboard WHERE userid='$userid'".$qB.$qC)) {
          return array();
        }
        
        $list = array();
        while ($row = $result->fetch_object())
        {
        $list[] = array (
            'id' => (int) $row->id,
            'name' => $row->name,
            'alias' => $row->alias,
            'showdescription' => (bool) $row->showdescription,
            'description' => $row->description,
            'main' => (bool) $row->main,
            'published'=> (bool) $row->published,
            'public'=> (bool) $row->public
        );
        }
        return $list;
    }

    public function set_content($userid, $id, $_content, $height)
    {
        $userid = (int) $userid;
        $id = (int) $id;
        $height = (int) $height;
        
        // sudo apt-get install php-mbstring
        if (function_exists("mb_convert_encoding")) {
            $axdir = "Modules/dashboard/AntiXSS/php5";
            require_once "$axdir/Bootup.php";
            require_once "$axdir/UTF8.php";
            require_once "$axdir/AntiXSS.php";
            $antiXss = new AntiXSS();
            $content = htmlspecialchars_decode($antiXss->xss_clean($_content));
            $_content = htmlspecialchars_decode($_content);
            if ($content!=$_content) return array('success'=>false, 'message'=>'Error: Invalid dashboard content, content not saved');
        } else {
            $content = $_content;
        }

        $result = $this->mysqli->query("SELECT content FROM dashboard WHERE userid = '$userid' AND id='$id'");
        $row = $result->fetch_object();
        if ($row) {
            if ($row->content==$content) {
                return array('success'=>false, 'message'=>'Dashboard content not updated, no changes made');
            }
            
            $stmt = $this->mysqli->prepare("UPDATE dashboard SET content=?, height=? WHERE userid=? AND id=?");
            $stmt->bind_param("siii", $content, $height, $userid, $id);
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            
            if ($affected_rows>0){
                return array('success'=>true, 'message'=>'Dashboard updated');
            }
        }
        return array('success'=>false, 'message'=>'Dashboard not updated');
    }

    public function set($userid,$id,$fields)
    {
        $userid = (int) $userid;
        $id = (int) $id;
        $fields = json_decode($fields);
        if(!empty($fields->alias)){
            $fields->alias = $this->make_slug($fields->alias); // make url friendly
            $fields->alias = substr($fields->alias,0,20); // limit to 20 chars to match the db
        }
        $result = $this->mysqli->query("SELECT * FROM dashboard WHERE userid='$userid' and `id` = '$id'");
        if ($row = $result->fetch_object()) 
        {
            if (isset($fields->height)) $row->height = (int) $fields->height;
            if (isset($fields->name)) $row->name = preg_replace('/[^\p{L}_\p{N}\s\-]/u','',$fields->name);
            if (isset($fields->alias)) $row->alias = preg_replace('/[^\p{L}_\p{N}\s\-]/u','',$fields->alias);
            if (isset($fields->description)) $row->description = preg_replace('/[^\p{L}_\p{N}\s\-]/u','',$fields->description);
            if (isset($fields->backgroundcolor)) $row->backgroundcolor = preg_replace('/[^0-9a-f]/','', strtolower($fields->backgroundcolor));
            if (isset($fields->gridsize)) $row->gridsize = preg_replace('/[^0-9]/','', $fields->gridsize);
            if (isset($fields->feedmode)) $row->feedmode = preg_replace('/[^\p{L}_\p{N}\s\-]/u','',$fields->feedmode);

            if (isset($fields->main))
            {
                $main = (bool)$fields->main;
                if ($main) $this->mysqli->query("UPDATE dashboard SET main = FALSE WHERE userid='$userid' and id<>'$id'");
                $row->main = $main;
            }

            if (isset($fields->public)) $row->public = (bool) $fields->public;
            if (isset($fields->fullscreen)) $row->fullscreen = (bool) $fields->fullscreen;
            if (isset($fields->published)) $row->published = (bool) $fields->published;
            if (isset($fields->showdescription)) $row->showdescription = (bool) $fields->showdescription;
            
            if (!$stmt = $this->mysqli->prepare("UPDATE dashboard SET height=?,name=?,alias=?,description=?,backgroundcolor=?,gridsize=?,feedmode=?,main=?,public=?,published=?,showdescription=?,fullscreen=? WHERE userid=? AND id=?")) {
                return array('success'=>false, 'message'=>'Dashboard schema error, please run emoncms database update');
            }
            $stmt->bind_param("issssisiiiiiii",$row->height,$row->name,$row->alias,$row->description,$row->backgroundcolor,$row->gridsize,$row->feedmode,$row->main,$row->public,$row->published,$row->showdescription,$row->fullscreen,$userid,$id);

            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $error = $stmt->error;
            $stmt->close();
            
            if ($affected_rows>0){
                return array('success'=>true, 'message'=>'Field updated', 'id'=>$id, 'alias'=>$row->alias);
            } else {
                return array('success'=>false, 'message'=>'Nothing changed', 'id'=>$id, 'alias'=>$row->alias);
            }
        }
        return array('success'=>false, 'message'=>'Field could not be updated'. " $error");
    }

    // Return the main dashboard from $userid
    public function get_main($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT * FROM dashboard WHERE userid='$userid' and main=TRUE");
        return $result->fetch_array();
    }

    public function get($id)
    {
        $id = (int) $id;
        $result = $this->mysqli->query("SELECT * FROM dashboard WHERE id='$id'");
        return $result->fetch_array();
    }
    
    public function get_content($userid,$id)
    {
        $id = (int) $id;
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT * FROM dashboard WHERE userid='$userid' AND id='$id'");
        return $result->fetch_object();
    }

    // Returns the $id dashboard from $userid
    public function get_from_alias($userid, $alias)
    {
        $userid = (int) $userid;
        $alias = preg_replace('/[^\p{L}_\p{N}\s\-]/u','',$alias);
        $result = $this->mysqli->query("SELECT * FROM dashboard WHERE userid='$userid' and alias='$alias'");
        return $result->fetch_array();
    }
    /**
     * Get the public dashboard from $alias
     * return array of fields for found database
     * @param string $alias
     */
    public function get_from_public_alias($alias)
    {
        $alias = preg_replace('/[^\p{L}_\p{N}\s\-]/u','',$alias);
        // access to public dashboards
        if(!empty($alias)) {
            $stmt = $this->mysqli->prepare("SELECT * FROM dashboard WHERE alias=?");
            $stmt->bind_param("s",$alias);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->free_result();
            $stmt->close();
            return $result->fetch_array();
        }
    }
    public function build_menu_array($location)
    {
        global $session;
        $userid = (int) $session['userid'];

        $public = 0; $published = 0;

        if (isset($session['profile']) && $session['profile']==1) {
            $dashpath = $session['username'];
            $public = !$session['write'];
            $published = 1;
        } else {
            $dashpath = 'dashboard/'.$location;
        }

        $dashboards = $this->get_list($userid, $public, $published);
        $menu = array();
        foreach ($dashboards as $dashboard)
        {
            // Check show description
            $desc = '';
            if ($dashboard['showdescription']) {
                $desc = $dashboard['description'];
            }

            // Set URL using alias or id
            if ($dashboard['alias']) {
                $aliasurl = "/".$dashboard['alias'];
            } else {
                $aliasurl = '&id='.$dashboard['id'];
            }

            // Build the menu item
            $menu[] = array(
                'id' => $dashboard['id'],
                'name' => $dashboard['name'],
                'desc'=> $desc,
                'published'=> $dashboard['published'],
                'path' => $dashpath.$aliasurl,
                'order' => "-1".$dashboard['name']
            );
        }
        return $menu;
    }
    public function make_slug( $string, $separator = '-' ) {
        $accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
        $special_cases = array( '&' => 'and', "'" => '');
        $string = mb_strtolower( trim( $string ), 'UTF-8' );
        $string = str_replace( array_keys($special_cases), array_values( $special_cases), $string );
        $string = preg_replace( $accents_regex, '$1', htmlentities( $string, ENT_QUOTES, 'UTF-8' ) );
        $string = preg_replace("/[^a-z0-9]/u", "$separator", $string);
        $string = preg_replace("/[$separator]+/u", "$separator", $string);
        return $string;
    }
}

