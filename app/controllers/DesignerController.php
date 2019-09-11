<?php
use \Phalcon\Mvc\Controller;

class DesignerController extends Controller {

    public function indexAction() {

        if ($this->request->has("vote")) {
            $cape_id = $this->request->get("vote");
            $cape = new CapeVotes();
            $cape->cape_id = $cape_id;
            $cape->user_id = $this->session->get("user_auth")['id'];
            $cape->save();
        }


        $this->view->designs =  CapeDesigns::query()
            ->conditions("user_id = :userid:")
            ->columns([
                'CapeDesigns.cape_id',
                'CapeDesigns.user_id',
                'CapeDesigns.cape_name',
                'CapeDesigns.color_data',
                'CapeDesigns.date',
                '(SELECT COUNT(*) FROM CapeVotes where CapeVotes.cape_id = CapeDesigns.cape_id) as vote_count'
            ])
            ->limit(100)
            ->orderBy("vote_count DESC")
            ->bind(['userid' => $this->session->get("user_auth")['id']])
            ->execute();
        return true;
    }

    public function statsAction() {
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);

        if (!$this->request->isAjax()) {
            return false;
        }

        $columns = [
            'CapeDesigns.cape_id',
            'CapeDesigns.user_id',
            'CapeDesigns.cape_name',
            'CapeDesigns.color_data',
            'CapeDesigns.date',
            '(SELECT COUNT(*) FROM CapeVotes where CapeVotes.cape_id = CapeDesigns.cape_id) as vote_count'
        ];

        $this->view->recent25 = CapeDesigns::query()
            ->columns($columns)
            ->limit(25)
            ->orderBy("date DESC")
            ->execute();

        $this->view->top100month = CapeDesigns::query()
            ->columns($columns)
            ->conditions("MONTH(date) = MONTH(CURDATE())")
            ->limit(100)
            ->orderBy("vote_count DESC")
            ->execute();

        $this->view->top100overall =  CapeDesigns::query()
            ->columns($columns)
            ->limit(100)
            ->orderBy("vote_count DESC")
            ->execute();
    }

    public function saveAction() {
        if ($this->request->isPost() && $this->session->has("user_auth")) {
            $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);

            $myArr =[
                'color1' => json_decode($this->request->getPost("color1_data")),
                'color2' => json_decode($this->request->getPost("color2_data")),
                'color3' => json_decode($this->request->getPost("color3_data")),
                'color4' => json_decode($this->request->getPost("color4_data"))
            ];

            $cape = new CapeDesigns();
            $cape->user_id = $this->session->get("user_auth")['id'];
            $cape->cape_name = $this->request->getPost("capename");
            $cape->color_data = stripslashes(json_encode($myArr, JSON_UNESCAPED_SLASHES));
            $cape->save();
        }

        return true;
    }

    public function voteAction() {
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);

        if ($this->request->isPost() && $this->session->has("user_auth")) {
            $cape_id = $this->request->getPost("cape_id", "int");
            $user_id = $this->session->get("user_auth")['id'];

            $design = CapeDesigns::query()
                ->conditions("user_id = :uid: AND cape_id = :cid:")
                ->bind([
                    'uid' => $user_id,
                    'cid' => $cape_id
                ])->execute()->getFirst();

            if ($design) {
                $this->flash->error("You can not vote on your own design!");
                return false;
            }

            $vote = CapeVotes::query()
                ->conditions("user_id = :uid: AND cape_id = :cid:")
                ->bind([
                    'uid' => $this->session->get("user_auth")['id'],
                    'cid' => $this->request->getPost("cape_id", "int")
                ])->execute()->getFirst();

            if ($vote) {
                $this->flash->error("You have already voted on this design!");
                return false;
            }

            $cape = new CapeVotes();
            $cape->cape_id = $this->request->getPost("cape_id", "int");
            $cape->user_id = $this->session->get("user_auth")['id'];
            if ($cape->save()) {
                $this->flash->success("Thanks for your vote!");
            } else {
                $this->flash->error("Unable to register vote: ".$cape->getMessages()[0]);
            }
        }
        return true;
    }

    public function debug($data) {
        echo "<pre>".json_encode($data, JSON_PRETTY_PRINT)."</pre>";
    }
}