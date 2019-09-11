<?php
use \Phalcon\Mvc\Controller;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;

class AdminController extends Controller {

    public function indexAction() {

    }

    public function graphAction() {
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);

        if (!$this->request->isPost()) {
            Functions::println(['error' => 'This page is available via post only.']);
            return;
        }

        $days = $this->request->getPost('days', 'int', 14);

        $searchData = $this->getSearchData($days);
        $lastNDays = Functions::getLastNDays($days, 'd');

        $totalNames = Searches::query()
            ->columns("COUNT(DISTINCT(username)) AS total")
            ->execute()
            ->getFirst();

        $searchesToday = Searches::query()
            ->columns("COUNT(*) AS total")
            ->conditions("DATE(date_searched) = CURDATE()")
            ->execute()->getFirst();

        $this->view->users = Users::count();
        $this->view->searches = Searches::count();
        $this->view->searchesToday = $searchesToday->total;
        $this->view->names = $totalNames->total;
        $this->view->chartData = $searchData;
        $this->view->days = $days;
        $this->view->lastNDays = $lastNDays;
    }

    public  function getSearchData($days) {
        $timeInSecs = (60 * 60 * 24 * $days);

        $searches = Searches::query()
            ->conditions("UNIX_TIMESTAMP() - UNIX_TIMESTAMP(date_searched) < $timeInSecs")
            ->execute();

        $lastNdays = Functions::getLastNDays($days, 'n j');
        $data      = array_fill_keys($lastNdays, 0);

        foreach ($searches as $search) {
            $date = strtotime($search->date_searched); // minus 18000 because of SQL timezone difference.
            $day  = date("n j", $date);

            if (!isset($data[$day])) {
                $data[$day] = 0;
            }

            $data[$day] += 1;
        }

        foreach ($data as $key => $value) {
            $data[$key] = round($value, 2);
        }

        return $data;
    }

    public function newsAction($currentPage = 1) {

        $paginator = new PaginatorModel([
            'data'  => Posts::find(array("order" => "date DESC")),
            'limit' => 5,
            'page'  => $currentPage,
        ]);

        $this->view->posts = $paginator->getPaginate();
    }

    public function addNewsAction() {

        $config = HTMLPurifier_Config::createDefault();
        $config->set("HTML.Allowed", 'b,u,i,a[href],img[src],h1,h2,h3,h4,h5,p,strong,em,ul,ol,li');
        $purifier = new HTMLPurifier($config);
        $user = Users::findFirstByUserid($this->session->get("user_auth")['id']);
        if ($this->request->isPost()) {
            $title = $this->filter->sanitize($this->request->getPost("title", "striptags"), "string");
            $content = $this->request->getPost("content");

            $post = new Posts();

            $post->title = $title;
            $post->content = $purifier->purify($content);
            $post->author_id = $user->userid;
            $post->author = $user->username;

            if (!$post->save()) {
                $this->flash->error($post->getMessages());
            }
        }
    }

    public function editNewsAction($postid)
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set("HTML.Allowed", 'b,u,i,a[href],img[src],h1,h2,h3,h4,h5,p,strong,em,ul,ol,li');
        $purifier = new HTMLPurifier($config);

        $post = Posts::findFirstByPostid($postid);

        if (!$post || $postid < 1 || $postid == null) {
            $this->response->redirect("admin/news");
            return false;
        }

        $this->view->post = $post;

        if ($this->request->isPost()) {
            $post->title = $this->filter->sanitize($this->request->getPost("title", "striptags"), "string");
            $post->content = $purifier->purify($this->request->getPost("content"));

            if (!$post->update()) {
                $this->flash->error($post->getMessages());
            } else {
                return $this->response->redirect('admin/editnews/' . $post->postid);
            }

        }
        return true;
    }

    public function deleteNewsAction($postid) {
        $post = Posts::findFirstByPostid($postid);
        if ($post) {
            $post->delete();
        }
        return $this->response->redirect("admin/news");
    }

    public function paymentsAction($currentPage = 1) {

        $paginator = new PaginatorModel([
            'data'  => Donations::find(array("order" => "date DESC")),
            'limit' => 5,
            'page'  => $currentPage,
        ]);

        $this->view->payments = $paginator->getPaginate();

        return true;
    }

    public function usersAction($currentPage = 1) {

        $paginator = new PaginatorModel([
            'data'  => Users::find(array("order" => "userid ASC")),
            'limit' => 25,
            'page'  => $currentPage,
        ]);

        $this->view->userList = $paginator->getPaginate();
        return true;
    }

}