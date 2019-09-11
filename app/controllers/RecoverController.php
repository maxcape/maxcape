<?php
use \Phalcon\Mvc\Controller;

class RecoverController extends Controller {

    public function indexAction() {

        if ($this->request->isPost() && $this->security->checkToken()) {

            $username = $this->request->getPost("uname", "string");
            $user = Users::findFirstByUsername($username);

            if (!$user) {
                $this->security->hash(rand());
                $this->flash->error("User could not be found!");
            } else {
                $recover = new Recovery();
                $verifyRec = Recovery::findFirstByUserid($user->userid);
                if ($verifyRec) {
                    $this->flash->error("You already have an active recovery request!");
                    return true;
                }
                $recover->userid = $user->userid;
                $recover->secret_key = md5(time() . $username . rand());
                $recover->expiration = (new DateTime())->add(new DateInterval("PT2H"))->format("Y-m-d H:i:s");
                if ($recover->save()) {
                    $this->flash->success("An email has been sent to you with the necessary instructions to reset your password.");
                    $this->sendRecovery($user, $recover);
                }
                return true;
            }
        }

        return true;
    }

    public function resetAction($key = null) {

        $key = $this->filter->sanitize($key, "string");
        if (!$key) {
            return $this->response->redirect('');
        }
        $recKey = Recovery::findFirstBySecret_key($key);

        $this->view->key = $key;

        if (!$recKey) {
            $this->flash->error("The recovery key you entered does not exist!");
            return $this->response->redirect('');
        }

        if ($this->request->isPost() && $this->security->checkToken()) {

            $user = Users::findFirstByUserid($recKey->userid);

            if (!$user) {
                $this->flash->error("The user cannot be found.");
                return $this->response->redirect('');
            }
            $pass = $this->request->getPost("pass");
            $repPass = $this->request->getPost("repass");

            if ($pass != $repPass) {
                $this->flash->error("Your password does not match!");
                return true;
            }

            if ($this->security->checkHash($pass, $user->password)) {
                $this->flash->error("The password you've entered is the same as your current password");
                return true;
            }
            if (strlen($pass) < 6 || strlen($pass) > 20) {
                $this->flash->error("The password length must be a minimum of 6 characters and less than 20 characters.");
                return true;
            }

            $user->password = $this->security->hash($pass);
            $recKey->delete();
            if ($user->save()) {
                $this->flash->success("Your password has been updated. You may now login.");
            }
        }
        return true;
    }

    public function sendRecovery($user, $recovery) {
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host         = SMTP_HOST;
        $mail->SMTPAuth     = true;
        $mail->Username     = SMTP_USER;
        $mail->Password     = SMTP_PASS;
        $mail->SMTPSecure   = SMTP_SECURITY;
        $mail->Port         = SMTP_PORT;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->isHTML(SMTP_HTML);
        $mail->addAddress($user->email, $user->username);
        $mail->Subject = 'Maxcape Password Recovery';

        $mail->Body    = '<h1>Maxcape Password Recovery</h1>
        <p>Hello, <strong>'.$user->username.'</strong>,
        You\'re receiving this email because you requested a password change.
        You can reset your password by clicking the link below:</p>
           
           <p><a href="https://maxcape.com/recover/reset/'.$recovery->secret_key.'">https://maxcape.com/recover/reset/'.$recovery->secret_key.'</a></p>
        </p>';

        $mail->AltBody = 'Hello '.$user->username.',  You\'re receiving this email
        because you requested a password change. Copy the link below and paste
        into your browser to reset your password:
        https://maxcape.com/recover/reset/'.$recovery->secret_key.'';

        if(!$mail->send()) {
            return false;
        } else {
            return true;
        }
    }
}