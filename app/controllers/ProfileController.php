<?php
use Phalcon\Mvc\Controller;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Cache\Backend\File as BackFile;
use Phalcon\Cache\Frontend\Data as FrontData;
use Phalcon\Mvc\Dispatcher;

class ProfileController extends Controller {

 	private static $valid_sorts   = [ "skill", "exp", "next", "rank", "level" ];
    private static $valid_filters = [ '120' => '120 Cape', 'max' => 'Max Cape', 'comp' => 'Completionist Cape'];
    private static $valid_orders  = [ SORT_ASC, SORT_DESC ];

    private $user;

    public function indexAction() {
    	if ($this->request->isPost() && $this->security->checkToken()) {
    		$resp = $this->updateProfile();
    		$this->flash->message($resp['status'], $resp['message']);
    	}

        $rsn = $this->user->rsn;
    	$searches = Searches::findFirstByUsername($rsn);

    	$this->view->rsn      = $rsn;
    	$this->view->profile_status = $this->user->profile_status;
    	$this->view->twitch_profile = $this->user->twitch_profile;
    	$this->view->youtube_profile = $this->user->youtube_profile;
    	$this->view->twitter_profile = $this->user->twitter_profile;
    	$this->view->instagram_profile = $this->user->instagram_profile;
    	$this->view->searches = $searches;
    	$this->view->user     = $this->user;
    }

    public function viewAction($username = null, $sortorder = null) {
        if ($this->request->isPost()) {
            $username = $this->request->getPost("rsn", "string");
            $fuser = htmlspecialchars(str_replace(" ", "+", $username));
            return $this->response->redirect("profile/view/".$fuser);
        }

        $this->view->username = $username;
        return true;
    }

    public function dataAction() {
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);

        if (!$this->request->isPost() || !$this->request->hasPost("username")) {
            echo 'no username?';
            return false;
        }

        $username  = $this->request->getPost("username", 'string');
        $sortorder = $this->request->getPost("sortorder", "string", null);
        $user      = Users::findFirstByRsn($username);

        if (!$username) {
            $this->dispatcher->forward([
                'controller' => 'errors',
                'action'	=> 'show404'
            ]);
            return true;
        }

        $type = $this->cookies->has("type") ? $this->cookies->get("type") : "max";

        if ($this->request->has("filter")) {
            $type = $this->request->get("filter", "string");
            if ($type != "max" && $type != "120" && $type != "comp") {
                $type = "max";
            }
            $this->cookies->set("type", $type);
        }

        $sort = null;
        $order 	= null;

        $this->view->filterType   = $type;

        if ($sortorder != null) {
            $parts = explode("-", $sortorder);

            if (count($parts) != 2) {
                $username = str_replace(" ", "+", $username);
                return $this->response->redirect("profile/view/".$username);
            }

            $sort 	= $this->filter->sanitize($parts[0], "string");
            $order  = $this->filter->sanitize($parts[1], "string");

            if ($order == "asc") {
                $order = SORT_ASC;
            } else if ($order == "desc") {
                $order = SORT_DESC;
            }

            if (!in_array($sort, self::$valid_sorts) || !in_array($order, self::$valid_orders)) {
                $username = str_replace(" ", "+", $username);
                return $this->response->redirect("profile/view/".$username);
            }
        }

        $this->view->sort  = $sort;
        $this->view->order = $order;

        $frontCache = new FrontData(['lifetime' => 3600]);// 3600
        $cache = new BackFile($frontCache, [
            'cacheDir' => '../app/compiled/userdata/'
        ]);

        $cacheKey = "user.$type.".str_replace(" ", "_", $username).".cache";

        if (file_exists('../app/compiled/userdata/'.$cacheKey)) {
            $filetime = filemtime('../app/compiled/userdata/'.$cacheKey);
            $this->view->last_update = $filetime;
            $this->view->next_update = $filetime + 3600;
        } else {
            $this->view->last_update = time();
            $this->view->next_update = time() + 3600;
        }

        $userdata  = $this->getUserData($cache, $type, $username);

        if ($userdata == null || $userdata == "invalid") {
            $this->dispatcher->forward([
                'controller' => 'errors',
                'action'	=> 'show404'
            ]);
            return true;
        }

        $search = new Searches();
        $search->save(['username' => $username]);

        $start  = strtotime(date('Y-m-d 00:00:00'));
        $end    = strtotime(date('Y-m-d 23:59:59'));

        $lastNTime = HiscoresData::query()
            ->conditions("UNIX_TIMESTAMP(entry) >= :start: AND UNIX_TIMESTAMP(entry) <= :end: AND username = :name:")
            ->orderBy("entry DESC")
            ->bind([
                'start' => $start,
                'end' => $end,
                'name' => $username
            ])->execute();

        $base = $lastNTime->getFirst();
        $last = $lastNTime->getLast();

        foreach ($userdata['skills'] as $key => $value) {
            $sk = strtolower($key);
            $difference = $base->$sk - $last->$sk;
            $userdata['skills'][$key]['difference'] = $difference != $value['exp'] ? $difference : 0;
        }

        $skillData                = $userdata['skills'];
        $this->view->username 	  = $username;
        $this->view->format_name  = str_replace(" ", "+", $username);
        $this->view->overall 	  = $skillData['Overall'];
        $this->view->combatLvl    = Functions::calculateCombatLevel($skillData);
        $this->view->filter_types = self::$valid_filters;
        $skillData = $userdata['skills'];

        if ($sort != null && $order != null) {
            $skillData = Functions::array_msort($skillData, array($sort => $order));
        }

        if ($type == "max") {
            $this->view->exp_needed = Functions::getNeededFor("max", $skillData);
        } else if ($type == "comp") {
            $this->view->exp_needed = Functions::getNeededFor("comp", $skillData);
        } else if ($type == "120") {
            $this->view->exp_needed = Functions::getNeededFor("120", $skillData);
        }

        $this->view->skillData = $skillData;
        $this->view->data = $userdata;
        $this->view->achievements = $this->getAchievements($skillData);
        return true;
    }

    public function getAchievements($data) {
        $achievements = [];

        if ($this->isMaxed($data)) {
            $achievements[] = [
                'icon' => 'maxcape',
                'description' => 'Has achieved level 99 in all available skills.',
            ];
        }

        if ($this->isCompletionist($data)) {
            $achievements[] = [
                'icon' => 'compcape',
                'description' => 'Has achieved level 99 in all skills including 120 Slayer & Dungeoneering.'
            ];
        }

        if ($this->isSkillMastery($data)) {
            $achievements[] = [
                'icon' => '120',
                'description' => 'Has achieved level 120 in at least 1 skill.'
            ];
        }

        if ($this->isPortMaster($data)) {
            $achievements[] = [
                'icon' => 'ports',
                'description' => 'Has achieved all the minimum skill requirements for Player Owned Ports.'
            ];
        }

        if ($this->isClassic($data)) {
            $achievements[] = [
                'icon' => 'rsc',
                'description' => 'Has achieved level 99 in all skills available in RuneScape Classic.'
            ];
        }

        if ($this->isf2pMaxed($data)) {
            $achievements[] = [
                'icon' => 'f2pmax',
                'description' => 'Has achieved level 99 in all skills available to free to play.'
            ];
        }

        if ($this->isSurvivalist($data)) {
            $achievements[] = [
                'icon' => 'survivalist',
                'description' => 'Has achieved level 99 in survival skills.'
            ];
        }

        if ($this->isArtisan($data)) {
            $achievements[] = [
                'icon' => 'artisan',
                'description' => 'Has achieved level 99 in artisan skills.'
            ];
        }

        if ($this->isf2pCompletionist($data)) {
            $achievements[] = [
                'icon' => 'f2pcomp',
                'description' => 'Has achieved level 99 in all skills available in free to play and 120 Dungeoneering.'
            ];
        }

        //todo blue sword
        //todo red sword

        if ($this->isGatherer($data)) {
            $achievements[] = [
                'icon' => 'gatherer',
                'description' => 'Has achieved level 99 in gathering skills.'
            ];
        }

        if ($data['Overall']['exp'] >= 25000000 && $data['Overall']['exp'] <= 49999999) {
            $achievements[] = [
                'icon' => 'xp/25m',
                'description' => 'Has achieved a minimum of 25 million experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 50000000 && $data['Overall']['exp'] <= 99999999) {
            $achievements[] = [
                'icon' => 'xp/50m',
                'description' => 'Has achieved a minimum of 50 million experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 100000000 && $data['Overall']['exp'] <= 149999999) {
            $achievements[] = [
                'icon' => 'xp/100m',
                'description' => 'Has achieved a minimum of 100 million experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 150000000 && $data['Overall']['exp'] <= 199999999) {
            $achievements[] = [
                'icon' => 'xp/150m',
                'description' => 'Has achieved a minimum of 150 million experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 200000000 && $data['Overall']['exp'] <= 249999999) {
            $achievements[] = [
                'icon' => 'xp/200m',
                'description' => 'Has achieved a minimum of 200 million experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 250000000 && $data['Overall']['exp'] <= 499999999) {
            $achievements[] = [
                'icon' => 'xp/250m',
                'description' => 'Has achieved a minimum of 250 million experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 500000000 && $data['Overall']['exp'] <= 999999999) {
            $achievements[] = [
                'icon' => 'xp/500m',
                'description' => 'Has achieved a minimum of 500 million experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 1000000000 && $data['Overall']['exp'] <= 1499999999) {
            $achievements[] = [
                'icon' => 'xp/1b',
                'description' => 'Has achieved a minimum of 1 billion experience.'
            ];
        }
        if ($data['Overall']['exp'] >= 1500000000 && $data['Overall']['exp'] <= 1999999999) {
            $achievements[] = [
                'icon' => 'xp/1.5b',
                'description' => 'Has achieved a minimum of 1.5 billion experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 2000000000 && $data['Overall']['exp'] <= 2499999999) {
            $achievements[] = [
                'icon' => 'xp/2b',
                'description' => 'Has achieved a minimum of 2 billion experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 2500000000 && $data['Overall']['exp'] <= 2999999999) {
            $achievements[] = [
                'icon' => 'xp/2.5b',
                'description' => 'Has achieved a minimum of 2.5 billion experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 3000000000 && $data['Overall']['exp'] <= 3499999999) {
            $achievements[] = [
                'icon' => 'xp/3b',
                'description' => 'Has achieved a minimum of 3 billion experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 3500000000 && $data['Overall']['exp'] <= 3999999999) {
            $achievements[] = [
                'icon' => 'xp/3.5b',
                'description' => 'Has achieved a minimum of 3.5 billion experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 4000000000 && $data['Overall']['exp'] <= 4499999999) {
            $achievements[] = [
                'icon' => 'xp/4b',
                'description' => 'Has achieved a minimum of 4 billion experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 4500000000 && $data['Overall']['exp'] <= 4999999999) {
            $achievements[] = [
                'icon' => 'xp/4.5b',
                'description' => 'Has achieved a minimum of 4.5 billion experience.'
            ];
        }

        if ($data['Overall']['exp'] >= 5000000000 && $data['Overall']['exp'] <= 5399999999) {
            $achievements[] = [
                'icon' => 'xp/5b',
                'description' => 'Has achieved a minimum of 5 billion experience.'
            ];
        }
        if ($data['Overall']['exp'] == 5400000000) {
            $achievements[] = [
                'icon' => 'xp/5.4b',
                'description' => 'Has achieved 5.4 billion experience.'
            ];
        }

        return $achievements;
    }

    /**
     * @param $cache \Phalcon\Cache\Backend
     * @param $type string
     * @param $username string
     * @return mixed|null|string
     */
	public function getUserData($cache, $type, $username) {
		$username = $this->filter->sanitize(strtolower($username), "string");

		$cacheKey = "user.$type.".str_replace(" ", "_", $username).".cache";
		$userdata = $cache->get($cacheKey);

		if ($userdata == null) {
			$userdata = $this->updatePlayer($username);

			for ($i = 0; $i < count(skills); $i++) {
				
				$rank  = $userdata == null ? 0 : Functions::getData(skills[$i], "rank", $userdata);
                $exp   = $userdata == null ? 83 : Functions::getData(skills[$i], "exp", $userdata);

				if ($type == "120") {
                    $level = $userdata == null ? 1 : Functions::getVirtualLevel(skills[$i], $exp);
                } else {
                    $level = $userdata == null ? 1 : Functions::getData(skills[$i], "level", $userdata);
                }

				if (skills[$i] == "Constitution") {
					if ($level == 1) {
						$level = 10;
						$exp  = 1154;
					}
				}

				if ($exp < 0)
					$exp = 0;
				if ($rank < 0)
					$rank = 0;

				$next = Functions::getNextLevel(skills[$i], $exp);

				if ($type == "max") {
					$max = (skills[$i] == 'Invention' ? 36073511 : 13034431);
                    if ($level >= 99) {
                        $next = 0;
                    }

                } else if ($type == "120") {
					$max = (skills[$i] == 'Invention' ? 166253312 : 104273167);
				} else {
					$max = skills[$i] == "Overall" ? 5400000000 : 200000000;
				}

				
				$percent = ($exp / $max) * 100;

				if ($percent > 100) {
					$percent = 100;
				}

				$exp_left = $exp == $max ? 0 : $max - $exp;

				if ($exp_left < 0)
					$exp_left = 0;

				$dataArray['skills'][skills[$i]] = [
					"rank"	     => $rank,
					"level"	     => $level,
					"exp"	     => $exp,
					"next"	     => skills[$i] == "Overall" ? "0" : ($next > 0 ? $next - $exp : 0),
					"exp_left"	 => $exp_left,
					"percentage" => $percent,
                    "difference" => 0
				];
			}

			$xp_track = new HiscoresData();
			$xp_track->setUsername($username);

			foreach ($dataArray['skills'] as $key => $value) {
			    $key = strtolower($key);
			    $xp_track->$key = $value['exp'];
            }

            if (!$xp_track->save()) {
			    $this->flash->error($xp_track->getmessages());
            }

            $advLogs = $this->getAdvLog($username);
			$quests  = $this->getQuests($username)['quests'];

            $dataArray['adv_logs'] = $advLogs;
            $dataArray['quests']   = $quests;

			$cache->save($cacheKey, $userdata == null ? "invalid" : $dataArray);
			$userdata = $userdata == null ? "invalid" : $dataArray;
		}

		return $userdata;
	}


    function getAvatarImage($rsn) {
        $avatarURL = "https://services.runescape.com/m=avatar-rs/" . $rsn . "/chat.png";
        $ch = curl_init($avatarURL);
        $fp = fopen('avatars/' . urldecode($rsn) . '.png', 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    function createImage($rsn) {
        $imgPng = imageCreateFromPng("../img/avatars/" . urldecode($rsn) . ".png");
        imageAlphaBlending($imgPng, true);
        imageSaveAlpha($imgPng, true);
        return $imgPng;
    }

    function getTextBetweenTags($string) {
        $pattern = "/\{(.*?)\}/";
        preg_match($pattern, $string, $matches);
        return $matches[1];
    }

    public function updatePlayer($rsn) {
        $rsn = str_replace("+", "_", $rsn);
        $ch = curl_init( "http://services.runescape.com/m=hiscore/index_lite.ws?player=$rsn");
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $status == "404" ? null : $result;
    }

    public function getUserInfo($rsn) {
        $ch = curl_init( "http://services.runescape.com/m=website-data/playerDetails.ws?names=%5B%22$rsn%22%5D&callback=jQuery000000000000000_0000000000&_=0");
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $status == "404" ? null : "[".$this->getTextBetweenTags($result)."]";
    }

    public function getAdvLog($rsn) {
        $rsn = str_replace(" ", "+", $rsn);
        $json = file_get_contents("https://apps.runescape.com/runemetrics/profile/profile?user=$rsn&activities=20");
        $arr = json_decode($json, true);
        $this->error = !empty($arr['error']) ? $arr['error'] : null;
        return $arr;
    }

    public function getQuests($rsn) {
        $rsn = str_replace(" ", "+", $rsn);
        $json = file_get_contents("https://apps.runescape.com/runemetrics/quests?user=$rsn");
        $arr = json_decode($json, true);
        $this->error = !empty($arr['error']) ? $arr['error'] : null;
        return $arr;
    }

    public function updateProfile() {
        $type = $this->request->getPost("type");

        if ($type == "pass") {
            $new 	= $this->request->getPost("new");
            $repeat = $this->request->getPost("repeat");
            $current_pass = $this->request->getPost("current");

            if (!$this->security->checkHash($current_pass, $this->user->password)) {
                $this->flash->error("Your current password is incorrect.");
                return true;
            }

            if (empty($new) || strlen($new) < 6 || strlen($new) > 20) {
                return [
                    'status' => 'error',
                    'message' => 'Password must be between 6 and 20 characters in length.',
                ];
            }

            if ($new != $repeat) {
                return [
                    'status' => 'error',
                    'message' => 'Your passwords do not match.',
                ];
            }

            $this->user->password = $this->security->hash($new);

            if ($this->user->update()) {
                return [
                    'status' => 'success',
                    'message' => 'Your account has been updated.',
                ];
            }
        } else if ($type == "email") {
            $new_email    = $this->request->getPost("email_address", "email");
            $current_pass = $this->request->getPost("current");

            if (!$this->security->checkHash($current_pass, $this->user->password)) {
                $this->flash->error("Your current password is incorrect.");
                return true;
            }

            $user = Users::findFirst([
                "conditions" => "email = ?1",
                "bind"		 => array(
                    1 => $new_email
                )
            ]);

            if ($user) {
                return [
                    'status' => 'error',
                    'message' => 'That email is already registered!',
                ];
            }

            $this->user->email = $new_email;

            if ($this->user->save()) {
                return [
                    'status' => 'success',
                    'message' => 'Your account has been updated.',
                ];
            }
        } else if ($type == "settings") {
            $profile = $this->request->hasPost("profile_public") ? 1 : 0;
            $rsn     = $this->request->hasPost("hidersn") ? 1 : 0;
            $newrsn  = $this->request->getPost("newrsn", 'string');
            $profile_status = $this->request->getPost("profile_status", "string");
            $twitch_profile = $this->request->getPost("twitch_profile", "string");
            $youtube_profile = $this->request->getPost("youtube_profile", "string");
            $twitter_profile = $this->request->getPost("twitter_profile", "string");
            $instagram_profile = $this->request->getPost("instagram_profile", "string");

            $this->user->profile_visible = $profile;
            $this->user->hidersn = $rsn;
            $this->user->rsn = $newrsn;
            $this->user->profile_status = $profile_status;
            $this->user->twitch_profile = $twitch_profile;
            $this->user->youtube_profile = $youtube_profile;
            $this->user->twitter_profile = $twitter_profile;
            $this->user->instagram_profile = $instagram_profile;

            if ($this->user->update()) {
                return [
                    'status' => 'success',
                    'message' => 'Your account has been updated.',
                ];
            } else {

                return [
                    'status' => 'error',
                    'message' => ''.$this->user->getMessages()[0],
                ];
            }
        }
        return true;
    }

    public function isMaxed($data) {
        foreach ($data as $user) {
            if ($user['level'] < 99) {
                return false;
            }
        }
        return true;
    }

    public function isCompletionist($data) {
       if ($data['Dungeoneering']['level'] != 120 || $data['Slayer']['level'] != 120) {
            return false;
        }
        foreach ($data as $user) {
            if ($user['level'] < 99) {
                return false;
            }
        }
        return true;
    }

    public function isSkillMastery($data) {

        if ($data['Attack']['exp'] >= 104273167 || $data['Defence']['exp'] >= 104273167 || $data['Strength']['exp'] >= 104273167 || $data['Constitution']['exp'] >= 104273167 || $data['Ranged']['exp'] >= 104273167 || $data['Prayer']['exp'] >= 104273167 || $data['Magic']['exp'] >= 104273167 || $data['Cooking']['exp'] >= 104273167 || $data['Woodcutting']['exp'] >= 104273167 || $data['Fletching']['exp'] >= 104273167 || $data['Fishing']['exp'] >= 104273167 || $data['Firemaking']['exp'] >= 104273167 || $data['Crafting']['exp'] >= 104273167 || $data['Smithing']['exp'] >= 104273167 || $data['Mining']['exp'] >= 104273167 || $data['Herblore']['exp'] >= 104273167 || $data['Agility']['exp'] >= 104273167 || $data['Thieving']['exp'] >= 104273167 || $data['Slayer']['exp'] >= 104273167 || $data['Farming']['exp'] >= 104273167 || $data['Runecrafting']['exp'] >= 104273167 || $data['Hunter']['exp'] >= 104273167 || $data['Construction']['exp'] >= 104273167 || $data['Summoning']['exp'] >= 104273167 || $data['Dungeoneering']['exp'] >= 104273167 || $data['Divination']['exp'] >= 104273167 || $data['Invention']['exp'] >= 80618654) {
            return true;
        }
        return false;
    }

    public function isPortMaster($data) {

        if ($data['Agility']['level'] >= 90 && $data['Construction']['level'] >= 90 && $data['Cooking']['level'] >= 90 && $data['Divination']['level'] >= 90 && $data['Dungeoneering']['level'] >= 90 && $data['Fishing']['level'] >= 90 && $data['Herblore']['level'] >= 90 && $data['Hunter']['level'] >= 90 && $data['Prayer']['level'] >= 90 && $data['Runecrafting']['level'] >= 90 && $data['Slayer']['level'] >= 90 && $data['Thieving']['level'] >= 90) {
            return true;
        }
        return false;
    }

    public function isClassic($data) {

        if ($data['Attack']['level'] == 99 && $data['Defence']['level'] == 99 && $data['Strength']['level'] == 99 && $data['Constitution']['level'] == 99 && $data['Ranged']['level'] == 99 && $data['Prayer']['level'] == 99 && $data['Magic']['level'] == 99 && $data['Cooking']['level'] == 99 && $data['Woodcutting']['level'] == 99 && $data['Fletching']['level'] == 99 && $data['Fishing']['level'] == 99 && $data['Firemaking']['level'] == 99 && $data['Crafting']['level'] == 99 && $data['Smithing']['level'] == 99 && $data['Mining']['level'] == 99 && $data['Herblore']['level'] == 99 && $data['Agility']['level'] == 99 && $data['Thieving']['level'] == 99) {
            return true;
        }
        return false;
    }

    public function isf2pMaxed($data) {

        if ($data['Attack']['level'] == 99 && $data['Strength']['level'] == 99 && $data['Defence']['level'] == 99 && $data['Ranged']['level'] == 99 && $data['Prayer']['level'] == 99 && $data['Magic']['level'] == 99 && $data['Constitution']['level'] == 99 && $data['Crafting']['level'] == 99 && $data['Mining']['level'] == 99 && $data['Smithing']['level'] == 99 && $data['Fishing']['level'] == 99 && $data['Cooking']['level'] == 99 && $data['Firemaking']['level'] == 99 && $data['Woodcutting']['level'] == 99 && $data['Runecrafting']['level'] == 99 && $data['Fletching']['level'] == 99 && in_array($data['Dungeoneering']['level'], range(99, 119))) {
            return true;
        }
        return false;
    }

    public function isSurvivalist($data) {

        if ($data['Agility']['level'] == 99 && $data['Hunter']['level'] == 99 && $data['Thieving']['level'] == 99 && $data['Slayer']['level'] >= 99) {
            return true;
        }
        return false;
    }

    public function isArtisan($data) {

        if ($data['Smithing']['level'] == 99 && $data['Crafting']['level'] == 99 && $data['Fletching']['level'] == 99 && $data['Construction']['level'] == 99 && $data['Firemaking']['level'] == 99) {
            return true;
        }
        return false;
    }

    public function isf2pCompletionist($data) {

        if ($data['Dungeoneering']['level'] != 120) {
            return false;
        }

        if ($data['Attack']['level'] == 99 && $data['Strength']['level'] == 99 && $data['Defence']['level'] == 99 && $data['Ranged']['level'] == 99 && $data['Prayer']['level'] == 99 && $data['Magic']['level'] == 99 && $data['Constitution']['level'] == 99 && $data['Crafting']['level'] == 99 && $data['Mining']['level'] == 99 && $data['Smithing']['level'] == 99 && $data['Fishing']['level'] == 99 && $data['Cooking']['level'] == 99 && $data['Firemaking']['level'] == 99 && $data['Woodcutting']['level'] == 99 && $data['Runecrafting']['level'] == 99 && $data['Fletching']['level'] == 99) {
            return true;
        }
        return false;
    }

    public function isGatherer($data) {

        if ($data['Woodcutting']['level'] == 99 && $data['Mining']['level'] == 99 && $data['Fishing']['level'] == 99 && $data['Divination']['level'] == 99) {
            return true;
        }
        return false;
    }

    public function beforeExecuteRoute(Dispatcher $dispatcher) {
		if ($this->session->has("user_auth")) {
			$this->user = Users::findFirstByUserid($this->session->get("user_auth")['id']);
			$this->view->user = $this->user;
		}
    }

}