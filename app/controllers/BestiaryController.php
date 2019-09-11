<?php
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Cache\Backend\File  as BackFile;
use Phalcon\Cache\Frontend\Data as FrontData;
use Phalcon\Mvc\Dispatcher;
use PHPHtmlParser\Dom;

class BestiaryController extends \Phalcon\Mvc\Controller {

    public function indexAction() {
        if ($this->request->isPost()) {
            $search = $this->request->getPost("search");
            $data   = null;

            $data = NpcsList::find([
                'conditions' => 'name LIKE ?1',
                'bind' => [ 1 => "%$search%" ]
            ]);

            $this->view->query = $this->filter->sanitize($search, "string");

            $partial = $this->view->getPartial('bestiary/npc', [
                'data' => $data
            ]);

            $this->view->partial = $partial;
            return true;
        }

        $areas = Areas::find([
            'columns' => [
                'id',
                'area_name',
                '(SELECT COUNT(*) FROM AreaNpcs WHERE AreaNpcs.area = area_name) AS in_area'
            ]
        ]);

        $partial = $this->view->getPartial('bestiary/areas', [
            'data' => $areas
        ]);

        $this->view->partial = $partial;
        return true;
    }

    public function searchNpc($name) {
        $url = "http://services.runescape.com/m=itemdb_rs/bestiary/beastSearch.json?term=";
        $json = file_get_contents($url . $name);
        $data = json_decode($json, true);
        return $data;
    }

    public function areaAction($areaName = null) {
        if ($areaName == null) {
            return $this->response->redirect("bestiary");
        }

        $areaName = $this->filter->sanitize($areaName, "string");
        $area = Areas::findFirstByAreaName($areaName);

        if (!$area) {
            return $this->response->redirect("bestiary");
        }

        $npcs = AreaNpcs::findByArea($area->area_name);

        $this->view->npcs = $npcs;
        $this->view->area = $area;
        //echo "<pre class='text-white'>".json_encode($npcs, JSON_PRETTY_PRINT)."</pre>";
        return true;
    }

    public function beastAction($bid) {
        $beast = $this->getBeastById($bid);

        if (!$beast) {
            $this->dispatcher->forward([
                'controller' => 'errors',
                'action' => 'show404'
            ]);
            return true;
        }

        $this->view->beast = $beast;

        $this->view->base_stats = [
            'Weakness' => $beast->weakness,
            'Level' => number_format($beast->level),
            'Lifepoints' => number_format($beast->lifepoints),
            'Defence' => $beast->defence,
            'Attack' => $beast->attack,
            'Magic' => $beast->magic,
            'Ranged' => $beast->ranged,
            'Exp' => number_format($beast->xp),
            'Slayer Lvl' => $beast->slayerLevel,
            'Attackable' => '<i class="fal '.($beast->attackable ? 'text-success fa-check' : 'text-danger fa-times').'">',
            'Aggressive' => '<i class="fal '.($beast->aggressive ? 'text-success fa-check' : 'text-danger fa-times').'">',
            'Poisonous' => '<i class="fal '.($beast->poisonous ? 'text-success fa-check' : 'text-danger fa-times').'">',
        ];

        $image = $this->getImage($beast->name);
        $drop_tables = $this->getDropTables($beast->name);

        if ($drop_tables)
            $this->view->drop_tables = $drop_tables;
        if ($image) {
            $this->view->imageUrl = $image['image']['imageserving'];
        }

        return true;
    }

    public function getDropTables($name) {
        $frontCache = new FrontData(['lifetime' => 86400]);// 3600
        $cache = new BackFile($frontCache, [
            'cacheDir' => '../app/compiled/bestiary/monsters/'
        ]);

        $name = htmlspecialchars(str_replace(" ", "_", ucfirst($name)));
        $key = "$name.drops.cache";

        $cache_data = $cache->get($key);

        if (!$cache_data) {
            $dom = new Dom;
            $dom->loadFromUrl("http://runescape.wikia.com/wiki/$name");
            $drop_tables = $dom->getElementsByTag('.item-drops');
            $cache_data = [];

            for($i = 0; $i < count($drop_tables); $i++) {
                $table = $drop_tables[$i];

                if ($table == null) {
                    return null;
                }

                $table->getTag()->setAttribute('class', 'table table-hover table-striped mb-0');
                $table->getTag()->setAttribute('style', 'text-align:left;');
                $table->find("tr")[0]->delete();

                $a    = $dom->find('a');
                $rows = $table->find("tr");
                $sup  = $dom->find('sup');

                foreach ($rows as $row) {
                    $columns = $row->find('td');
                    if ($columns) {
                        $columns[0]->delete();
                    }

                    foreach ($columns as $column) {
                        $colTag = $column->getTag();
                        $colTag->setAttribute("width", "200px");
                    }

                    $coltag = $columns[1]->getTag();
                    $coltag->setAttribute("width", "400px");

                    $rowtag = $row->getTag();
                    $rowtag->removeAttribute("style");
                }

                foreach ($sup as $cit) {
                    $cit->delete();
                }

                foreach ($a as $link) {
                    $linktag = $link->getTag();
                    echo $linktag->name;
                    $title = $linktag->getAttribute("title")['value'];
                    $title = str_replace(" ", "+", $title);

                    $linktag->setAttribute('href', '/item/'.$title);
                }

                $cache_data[] = $table->outerHtml();

            }

            $cache->save($key, $cache_data);
        }

        return $cache_data;
    }

    public function getBeastById($id) {
        $beast = Npcs::findFirstById($id);

        if (!$beast) {
            $beastData = $this->getBeast($id);

            if (!$beastData) {
                return false;
            }

            $beast = new Npcs();

            $beast->id          = $beastData['id'];
            $beast->name        = $beastData['name'];
            $beast->members     = $beastData['members'];

            $beast->weakness    = $this->getData("weakness", $beastData);
            $beast->level       = $this->getData("level", $beastData);
            $beast->lifepoints  = $this->getData("lifepoints", $beastData);
            $beast->defence     = $this->getData("defence", $beastData);
            $beast->attack      = $this->getData("attack", $beastData);
            $beast->magic       = $this->getData("magic", $beastData);
            $beast->ranged      = $this->getData("ranged", $beastData);
            $beast->xp          = $this->getData("xp", $beastData);
            $beast->slayerLevel = $this->getData("slayerlevel", $beastData);
            $beast->slayercat   = $this->getData("slayercat", $beastData);
            $beast->size        = $this->getData("size", $beastData);
            $beast->attackable  = $this->getData("attackable", $beastData);
            $beast->aggressive  = $this->getData("aggressive", $beastData);
            $beast->poisonous   = $this->getData("poisonous", $beastData);
            $beast->description = $this->getData("description", $beastData);

            $beast->areas       = json_encode($beastData['areas']);
            $beast->animations  = json_encode($beastData['animations']);

            if (!$beast->save()) {
                $this->flash->error($beast->getMessages());
            }
        }

        return $beast;
    }

    public function getWikiData($name) {
        $name = htmlspecialchars(str_replace(" ", "_", strtolower($name)));
        $url = "http://runescape.wikia.com/api.php?action=parse&page=$name&prop=text&format=json";

        echo $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = utf8_decode(curl_exec($ch));
        curl_close($ch);
        return json_decode($output, true);
    }

    public function getImage($name) {
        $name = htmlspecialchars(str_replace(" ", "_", ucfirst($name)));
        $name = preg_replace("/\([^)]+\)/","",$name);
        $name = str_replace(",", '%2C', $name);

        $url = "http://runescape.wikia.com/api.php?action=imageserving&wisTitle=$name&format=json";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = utf8_decode(curl_exec($ch));
        curl_close($ch);
        return json_decode($output, true);
    }

    public function getData($key, $array) {
        return array_key_exists($key, $array) ? $array[$key] : null;
    }

    public function getAreas() {
        $json = file_get_contents("http://services.runescape.com/m=itemdb_rs/bestiary/areaNames.json");
        $data = json_decode($json, true);
        return $data;
    }

    public function getBeastsInArea($areaName) {
        $area = str_replace(" ", "+", $areaName);
        $json = file_get_contents("http://services.runescape.com/m=itemdb_rs/bestiary/areaBeasts.json?identifier=$area");
        $data = json_decode($json, true);
        return $data;
    }

    public function getBeast($bid) {
        $json = file_get_contents("http://services.runescape.com/m=itemdb_rs/bestiary/beastData.json?beastid=$bid");
        $data = json_decode($json, true);
        return $data;
    }


}