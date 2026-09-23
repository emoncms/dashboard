<?php

/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/

defined('EMONCMS_EXEC') or die('Restricted access');

function dashboard_controller()
{
    global $mysqli, $session, $user, $route, $path;

    require "Modules/dashboard/dashboard_model.php";
    $dashboard = new Dashboard($mysqli);
    // id, userid, content, height, name, alias, description, main, public, published, showdescription, fullscreen

    $result = false;
    $submenu = '';

    if ($route->format == 'html') {
        if ($route->action == "list" && $session['write']) {
            load_language_files("Modules/dashboard/locale", "dashboard_messages");
            $result = view("Modules/dashboard/Views/dashboard_list.php", [
                'path' => $path
            ]);
        } elseif ($route->action == "view") {
            // 4 access modes:
            // - as a session user either login or apikey
            // - with a readkey, does not create a session
            // - via public dashboard username
            // - via dashboard id for public dashboard
            // - via dashboard alias for public dashboard with public feeds
            $result = EMPTY_ROUTE;
            $userid = false;
            $apikey = "";

            // $owner_context is true when $userid identifies the *requester* and so
            // proves ownership: an interactive/apikey session, or a readkey (the
            // owner's own read apikey). It is false on the public-profile path, where
            // $userid is the *content owner* being browsed, not the requester - there
            // ownership must never be inferred from $userid and only public
            // dashboards may be shown.
            $owner_context = false;

            if (isset($session['read']) && $session['read']) {
                $userid = $session['userid'];
                $owner_context = true;
                if (isset($_GET['apikey'])) {
                    $apikey = $user->get_apikey_read($session['userid']);
                }
            } elseif (isset($_GET['readkey'])) {
                if ($userid = $user->get_id_from_apikey($_GET['readkey'])) {
                    $apikey = $user->get_apikey_read($userid);
                    $owner_context = true;
                }
            } elseif ($session['public_userid']) {
                $userid = (int) $session['public_userid'];
            }

            $dashid = (int) get('id');
            if ($dashid) {
                $dash = $dashboard->get($dashid);
            } elseif ($route->subaction && $userid) {
                $dash = $dashboard->get_from_alias($userid, $route->subaction);
            } elseif ($userid) {
                $dash = $dashboard->get_main($userid);
            } elseif (!$userid and $route->subaction) {
                $dash = $dashboard->get_from_public_alias($route->subaction);
            }

            if (isset($dash)) {
                $public_userid = 0;
                if (!$session['read'] && $dash['public']) {
                    $public_userid = $dash['userid'];
                }

                // Access control. A dashboard is shown if it is public, or - only in
                // an owner context (session or the owner's own readkey) - if the
                // requester owns it. On the public-profile path $userid is the content
                // owner, not the requester, so the ownership clause is gated behind
                // $owner_context; otherwise browsing /<username>/dashboard/view?id=N
                // would expose every one of that user's dashboards, private included.
                // Note: $apikey is NOT an authorisation signal here - it is the read
                // key injected into the page so the feed widgets can load data, and in
                // the logged-in branch it is the requester's own key. Testing it here
                // previously let any key holder open any dashboard by id.
                $owner = ($owner_context && $userid && $dash['userid'] == $userid);

                if ($dash['public'] || $owner) {
                    // A dashboard is meant to sit in an iframe on another
                    // site, so it takes the embed frame policy. That includes
                    // a private one: the way an owner embeds it is to put a
                    // key in the iframe url, and a key in the url is not the
                    // ambient authority a session cookie is. The site doing
                    // the framing had to know the key to write the url, and
                    // knowing it already grants everything framing the page
                    // could reach. set_frame_policy in core.php holds the
                    // relaxation back for a page the session cookie
                    // authenticated, which is the clickjacking case, so a
                    // logged in visitor is never relaxed whichever dashboard
                    // this is.
                    allow_public_embed();

                    $result = view("Modules/dashboard/Views/dashboard_view.php", [
                        'dashboard' => $dash,
                        'page_html' => $dashboard->content_html($dash),
                        'apikey' => $apikey,
                        'public_userid' => $public_userid,
                        'owner' => $owner
                    ]);
                }
            }
        } elseif ($route->action == "edit" && $session['write']) {
            // The editor only ever opens the requester's own dashboard. It was
            // loaded by id alone, so any writer could read the content of a
            // private dashboard by asking for its id.
            $dash = false;
            if ($route->subaction) {
                $dash = $dashboard->get_from_alias($session['userid'], $route->subaction);
            } elseif (isset($_GET['id'])) {
                $dash = $dashboard->get_owned($session['userid'], get('id'));
            }

            if (!$dash) {
                $result = EMPTY_ROUTE;
            } else {
                // Rendered for the first paint. The document is read after,
                // so it is the one the page was drawn from.
                $page_html = $dashboard->content_html($dash);
                $result = view("Modules/dashboard/Views/dashboard_edit_view.php", [
                    'dashboard' => $dash,
                    'page_html' => $page_html,
                    'document' => $dashboard->document($dash['id'])
                ]);
                $result .= view("Modules/dashboard/Views/dashboard_config.php", [
                    'dashboard' => $dash
                ]);

                $submenu = view("Modules/dashboard/Views/dashboard_menu.php", ['id' => $dash['id'],'type' => "edit"]);
            }
        }
    } elseif ($route->format == 'json') {
        if ($session['read']) {
            if ($route->action == 'list') {
                $result = $dashboard->get_list($session['userid'], false, false);
            }
        }

        if ($session['write']) {
            if ($route->action == 'set') {
                $result = $dashboard->set($session['userid'], prop('id'), prop('fields'));
            } elseif ($route->action == 'setcontent') {
                // Read directly as post() strips the backslashes of JSON escapes.
                $document = isset($_POST['document']) ? rawurldecode((string) $_POST['document']) : null;
                $result = $dashboard->set_content($session['userid'], post('id'), $document, post('height'));
            } elseif ($route->action == 'create') {
                $result = $dashboard->create($session['userid']);
            } elseif ($route->action == 'delete') {
                $result = $dashboard->delete($session['userid'], get('id'));
            } elseif ($route->action == 'clone') {
                $result = $dashboard->dashclone($session['userid'], get('id'));
            }
        }
    }
    return ['content' => $result, 'submenu' => $submenu];
}
