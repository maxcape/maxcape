<?php

class DonateController extends \Phalcon\Mvc\Controller {

    public function indexAction() {
        $items = StoreItems::find(['conditions' => 'is_visible = 1']);
        $this->view->items = $items;
    }

    public function processAction()
    {
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);

        if (!$this->request->isPost() || !$this->request->hasPost("postdata")) {
            return false;
        }

        $pp_data = $this->request->getPost("postdata");

        $email = $pp_data['payer']['payer_info']['email'];
        $first_name = $pp_data['payer']['payer_info']['first_name'];
        $last_name = $pp_data['payer']['payer_info']['last_name'];
        $country = $pp_data['payer']['payer_info']['country_code'];
        $acc_status = $pp_data['payer']['status'];
        $item = $pp_data['transactions'][0]['item_list']['items'][0];
        $username = $pp_data['transactions'][0]['custom'];
        $state = $pp_data['transactions'][0]['related_resources'][0]['sale']['state'];
        $currency = $pp_data['transactions'][0]['related_resources'][0]['sale']['amount']['currency'];
        $trans_id = $pp_data['transactions'][0]['related_resources'][0]['sale']['id'];
        $paid = $pp_data['transactions'][0]['related_resources'][0]['sale']['amount']['total'];

        $don = new Donations;
        $don->username = $username;
        $don->email = $email;
        $don->acc_status = $acc_status;
        $don->product_id = $item['sku'];
        $don->product_name = $item['name'];
        $don->paid = $paid;
        $don->payment_status = $state;
        $don->currency = $currency;
        $don->transaction_id = $trans_id;

        if ($don->save()) {
            $rank = StoreItems::findFirst([
                'conditions' => 'id = ?1',
                'bind' => [
                    1 => $item['sku']
                ]
            ]);

            if (!$rank) {
                $don->payment_status = "invalid";
                $don->update();
                $this->flash->error("Invalid product purchased. Please contact admin for an immediate ban. Thank you.");
                return true;
            }

            /** @var Users $user */
            $user = Users::findFirst([
                'conditions' => 'username = ?1',
                'bind' => [
                    1 => $username
                ]
            ]);

            if (!$user) {
                $this->flash->error("Invalid user. Could not update rank.");
                return true;
            }

            $user->total_donated += $paid;

            $items = StoreItems::find(['order' => 'price DESC']);
            $updated = false;

            /** @var StoreItems $item */
            foreach ($items as $item) {
                $required = $item->price;

                if ($user->total_donated >= $required) {
                    if ($user->donator != $item->id) {
                        $user->donator = $item->id;
                        $updated = true;
                        break;
                    }
                    break;
                }
            }

            if ($updated) {
                if ($user->update()) {
                    $this->flash->success("Thank you for your donation! Your rank has been updated!");
                } else {
                    $this->flash->error("An error occurred applying your rank: " . $user->getMessages()[0]);
                }
                return true;
            }

            $this->flash->success("Thank you for your donation!");
            return true;
        }

        $this->flash->error("An error occurred saving your payment: ".$don->getMessages()[0]);
        return true;
    }
}