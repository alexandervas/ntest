<?php

namespace app\controllers;

use Yii;
use yii\base\Security;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;


class SiteController extends Controller
{



    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->render('index');
        }else{
            $model = new LoginForm();
            return $this->render('login', [
                'model' => $model,
            ]);
        }

    }

    public function actionLogin()
    {

        $path = Yii::getAlias('@app');
        $db_file = $path."/db/users.txt";

        if(!file_exists($db_file))
        {
            $this->setDb();
        }

        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();

        $ip_ban = Yii::$app->getRequest()->getUserIP();
        $user =  $this->getBanUser($ip_ban);
        $current_time = time();


        if($user->expire > $current_time && $user->ban_count == 3){

            $expire_sec =  $user->expire - $current_time;

            Yii::$app->session->setFlash('noticeBanUser',$expire_sec);

            return $this->render('login', [
                'model' => $model,
            ]);
        }

        if ($model->load(Yii::$app->request->post()) && $model->login()) {

            $user_id = Yii::$app->getUser()->id;
            $model->ip = Yii::$app->getRequest()->getUserIP();
            $model->expire = time();
            $model->ban_count = 0;
            $this->unblockUser($user_id,$model->ip);
            return $this->goBack();

        }else{
            if($user->expire < $current_time && $user->ban_count < 3){
                $this->banUser($ip_ban);
            }

        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->redirect('/site/login');
    }

    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    public function actionAbout()
    {
        return $this->render('about');
    }


    public function setDb(){

        $path = Yii::getAlias('@app');
        $data_file = $path."/db/users.txt";

        $fh = fopen($data_file,'a+') or die('Error open file');
        rewind($fh) or die('Error lock file');

        flock($fh,LOCK_EX) or die('Error lock file');

        if(filesize($data_file) > 0)
        {
            $serialized_data = fread($fh,filesize($data_file)) or die('Error read file');
        }else{
            $serialized_data = '';
        }

        if(empty($serialized_data)){

            $data = [
                '100' => [
                    'id' => '100',
                    'username' => 'admin',
                    'password' => 'admin',
                    'ip' =>Yii::$app->getRequest()->getUserIP(),
                    'expire' => time(),
                    'ban_count' => '0',
                    'authKey' => 'test100key',
                    'accessToken' => '100-token',
                ],
                '101' => [
                    'id' => '101',
                    'username' => 'demo',
                    'password' => 'demo',
                    'ip' =>'127.0.0.2',
                    'expire' => time(),
                    'ban_count' =>'0',
                    'authKey' => 'test101key',
                    'accessToken' => '101-token',
                ],
            ];
        }else{
            $data = unserialize($serialized_data);

        }


        $serialized_data = serialize($data);

        rewind($fh) or die('Error rewind file');
        ftruncate($fh,0) or die('Error truncate file');

        if (-1 == (fwrite($fh,$serialized_data))) { die('Error write file'); }
        fflush($fh) or die('Error flush file');
        flock($fh,LOCK_UN) or die('Error lock file');
        fclose($fh) or die('Error close file');

    }


    public function banUser($ip){

        $serialized_data = $this->_getDataDb();

        $data = unserialize($serialized_data);
        //return $data;
        foreach ($data as &$user) {
            if($user['ip'] == $ip && $user['ban_count'] < 3){
                $user['ban_count']++;
            }
            if($user['ip'] == $ip && $user['ban_count'] == 3){
                $user['expire'] = time() + (60*5);
            }
        }
        unset($user);

        if($this->_setDataDb($data)){
            return true;
        }else{
            $this->redirect('index');
        }

    }


    public function unblockUser($user_id,$ip){

        $serialized_data = $this->_getDataDb();

        $data = unserialize($serialized_data);

        foreach ($data as &$user) {
            if($user['id'] == $user_id && $user['ip'] == $ip){
                $user['ban_count'] = 0;
                $user['expire'] = time();
            }
        }
        unset($user);

        if($this->_setDataDb($data)){
            return true;
        }else{
            $this->redirect('index');
        }

    }


    public function getBanUser($user_ip){

        $serialized_data = $this->_getDataDb();

        $data = unserialize($serialized_data);

        $_user = array();

        foreach ($data as &$user) {
            if($user['ip'] == $user_ip){

                $_user['id'] = $user['id'];
                $_user['username'] = $user['username'];
                $_user['ip'] = $user['ip'];
                $_user['expire'] = $user['expire'];
                $_user['ban_count'] = $user['ban_count'];
                $_user['authKey'] = $user['authKey'];
                $_user['accessToken'] = $user['accessToken'];
            }
        }
        return (object)$_user;

    }


    protected function _getDataDb(){

        $path = Yii::getAlias('@app');
        $data_file = $path."/db/users.txt";

        $fh = fopen($data_file,'a+') or die('Error open file');
        rewind($fh) or die('Error lock file');

        flock($fh,LOCK_EX) or die('Error lock file');

        if(filesize($data_file) > 0)
        {
            $serialized_data = fread($fh,filesize($data_file)) or die('Error read file');
        }else{
            $serialized_data = '';
        }
        if(fclose($fh)){
            return $serialized_data;
        }else{
            die('Error close file');
        }

    }

    public function _setDataDb($data){

        $path = Yii::getAlias('@app');
        $data_file = $path."/db/users.txt";

        $fh = fopen($data_file,'a+') or die('Error open file');
        rewind($fh) or die('Error lock file');

        flock($fh,LOCK_EX) or die('Error lock file');

        $serialized_data = serialize($data);

        rewind($fh) or die('Error rewind file');
        ftruncate($fh,0) or die('Error truncate file');

        if (-1 == (fwrite($fh,$serialized_data))) { die('Error write file'); }
        fflush($fh) or die('Error flush file');
        flock($fh,LOCK_UN) or die('Error lock file');
        if(fclose($fh)){
            return true;
        }else{
            die('Error close file');
        }

    }
}
