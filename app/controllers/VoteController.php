<?php
use \Phalcon\Mvc\Controller;

class VoteController extends Controller {

    public function indexAction() {
        $data = UserSuggestions::query()
            ->columns([
                'UserSuggestions.id',
                'UserSuggestions.username',
                'UserSuggestions.userid',
                'UserSuggestions.title',
                'UserSuggestions.content',
                'UserSuggestions.completed',
                'UserSuggestions.date',
                '(SELECT COUNT(*) FROM SuggestionVotes where SuggestionVotes.suggestion_id = UserSuggestions.id) as vote_count',
                'u.rsn',
                'u.privilege_level'
            ])
            ->limit(25)
            ->leftJoin("Users", "u.userid = UserSuggestions.userid", 'u')
            ->orderBy("vote_count DESC, date ASC")
            ->execute();

        $this->view->recent25 = $data;
        return true;
    }

    public function saveAction() {
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);

        if (!$this->request->isPost()) {
            return false;
        }

        $suggestion = new UserSuggestions([
            'username' => $this->session->get("user_auth")['username'],
            'userid'   => $this->session->get("user_auth")['id'],
            'title'    => $this->filter->sanitize($this->request->getPost("name", "striptags"), "string"),
            'content'  => $this->request->getPost("content", "string"),
        ]);

        if (!$suggestion->save()) {
            $this->flash->error("".$suggestion->getMessages()[0]);
            return false;
        }

        $this->flash->success("added");
        return true;
    }

    public function voteAction() {
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);

        if (!$this->request->isPost()) {
            $this->printStatus(false, "This page is available via post only.");
            return false;
        }

        /** @var UserSuggestions $suggestion */
        $suggestion = UserSuggestions::query()
            ->conditions("id = :cid:")
            ->bind([
                'cid' => $this->request->getPost("suggestion_id", "int")
            ])->execute()->getFirst();

        if (!$suggestion) {
            $this->printStatus(false, "Invalid suggestion.");
            return false;
        }

        if ($suggestion->getUserid() == $this->session->get("user_auth")['id']) {
            $this->printStatus(false, "You can not vote on your own suggestion!");
            return false;
        }

        $vote = SuggestionVotes::query()
            ->conditions("user_id = :uid: AND suggestion_id = :cid:")
            ->bind([
                'uid' => $this->session->get("user_auth")['id'],
                'cid' => $suggestion->getId()
            ])->execute()->getFirst();

        if ($vote) {
            $this->printStatus(false, "You have already voted on this suggestion!");
            return false;
        }

        $vote = new SuggestionVotes([
            'suggestion_id' => $suggestion->getId(),
            'user_id' => $this->session->get("user_auth")['id']
        ]);

        if (!$vote->save()) {
            $this->printStatus(false, "Unable to register vote: ".$vote->getMessages()[0]);
            return false;
        }

        $count = SuggestionVotes::count(['conditions' => 'suggestion_id = '.$suggestion->getId()]);

        echo json_encode([
            'success' => true,
            'message' => 'Thanks for your vote!',
            'votes'   => $count
        ]);
        return true;
    }

    public function completeAction($id)
    {

        $suggestion = UserSuggestions::findFirstById($id);
        if (!$suggestion) {
            $this->flash->error("Invalid suggestion id.");
        }
        $suggestion->completed = 1;
        $suggestion->update();
        $this->response->redirect('vote');
        return true;
    }

    public function deleteAction($id)
    {

        $suggestion = UserSuggestions::findFirstById($id);
        if (!$suggestion) {
            $this->flash->error("Invalid suggestion id.");
        }
            $suggestion->delete();

        $this->response->redirect('vote');
        return true;
    }
    public function printStatus($success, $message) {
        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
    }
}