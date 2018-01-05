<?php
namespace Base\User\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;

class UserController extends Controller {

    public function initialize(){
        parent::initialize();

        $this->loadModel('Base/User.User');
        $this->loadComponent('Auth');

        $this->viewBuilder()->layout(Configure::read('User.layout'));
    }

    public function beforeFilter(Event $event){
        parent::beforeFilter($event);

        $this->Auth->allow(['register','activate','login','logout','remind','change']);
    }

    public function index(){
    }

    public function register(){
        if(!Configure::read('User.registration')){
            $this->Flash->error(__d('user','_registration_not_allowed'));

            return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
        }

        if(!empty($this->request->data)) {
            $email = $this->request->data('email');
            $password = $this->request->data('password');
            $passwordConfirmation = $this->request->data('password_confirmation');

            if ($this->User->findByEmail($email)->isEmpty()) {
                if (!empty($password)) {
                    if ($password == $passwordConfirmation) {
                        if($this->User->register($email,$password)){
                            $this->Flash->success(__d('user','_registered'));

                            return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
                        }
                        else {
                            $this->Flash->error(__d('user','_not_registered'));
                        }
                    } else {
                        $this->Flash->error(__d('user', '_not_confirmed_password'));
                    }
                } else {
                    $this->Flash->error(__d('user', '_not_valid_password'));
                }
            } else {
                $this->Flash->error(__d('user', '_user_exists'));
            }
        }
    }

    public function activate(){
        $token=$this->request->query('token');

        if(!$this->User->activate($token)){
            $this->Flash->success(__d('user','_activated'));

            return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
        }

        $this->Flash->error(__d('user','_not_activated'));

        return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
    }

    public function login(){
        if($this->Auth->isAuthorized()){
            return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'index']));
        }

        if(!empty($this->request->data)){
            $user=$this->Auth->identify();

            if(!empty($user)){
                $this->Auth->setUser($user);

                $this->request->session()->write('Config.locale',$user['locale']);

                return($this->redirect($this->Auth->redirectUrl()));
            }

            $this->Flash->error(__d('user','_user_or_password_incorrect'));
        }
    }

    public function logout(){
        $this->Auth->logout();

        return($this->redirect('/'));
    }

    public function remind(){
        if($this->request->is('post')){
            $email=$this->request->data('email');

            if(!empty($email)){
                $this->User->remind($email);
                $this->Flash->success(__d('user','_reminded'));

                return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
            }
        }
    }

    public function change(){
        $token=$this->request->query('token');

        $this->set('token',$token);

        if($this->request->is('post')){
            $password=$this->request->data('password');
            $passwordConfirmation=$this->request->data('password_confirmation');

            if (!empty($password)) {
                if ($password == $passwordConfirmation) {
                    if($this->User->change($token,$password)){
                        $this->Flash->success(__d('user','_changed'));

                        return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
                    }
                    else {
                        $this->Flash->error(__d('user','_not_changed'));
                    }
                } else {
                    $this->Flash->error(__d('user', '_not_confirmed_password'));
                }
            } else {
                $this->Flash->error(__d('user', '_not_valid_password'));
            }
        }

        if(!$this->User->token($token)){
            $this->Flash->error(__d('user','_not_token'));

            return($this->redirect(['plugin'=>'Base/User','controller'=>'User','action'=>'login']));
        }
    }

    public function locale($locale=null){
        if(!empty($locale)){
            if(in_array($locale,array_keys(Configure::read('Setting.locale')))){
                $this->request->session()->write('Config.locale',$locale);
            }
        }

        return($this->redirect($this->referer()));
    }


}