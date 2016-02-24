<?php

namespace app\models;

class User extends \yii\base\Object implements \yii\web\IdentityInterface
{
    public $id;
    public $username;
    public $password;
    public $ip;
    public $expire;
    public $ban_count;
    public $authKey;
    public $accessToken;


    private static $users = [
        '100' => [
            'id' => '100',
            'username' => 'admin',
            'password' => 'admin',
            'ip' =>'',
            'expire' => '',
            'ban_count' => '',
            'authKey' => 'test100key',
            'accessToken' => '100-token',
        ],
        '101' => [
            'id' => '101',
            'username' => 'demo',
            'password' => 'demo',
            'ip' =>'',
            'expire' => '',
            'ban_count' =>'',
            'authKey' => 'test101key',
            'accessToken' => '101-token',
        ],
    ];

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return isset(self::$users[$id]) ? new static(self::$users[$id]) : null;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        foreach (self::$users as $user) {
            if ($user['accessToken'] === $token) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        foreach (self::$users as $user) {
            if (strcasecmp($user['username'], $username) === 0) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return $this->password === $password;
    }


    public static function setUsers(){

        $users_in_file = [
            '100' => [
                'id' => '100',
                'username' => 'admin',
                'password' => 'admin',
                'ip' =>'',
                'expire' => '',
                'ban_count' => '',
                'authKey' => 'test100key',
                'accessToken' => '100-token',
            ],
            '101' => [
                'id' => '101',
                'username' => 'demo',
                'password' => 'demo',
                'ip' =>'',
                'expire' => '',
                'ban_count' =>'',
                'authKey' => 'test101key',
                'accessToken' => '101-token',
            ],
        ];

        $path = Yii::getAlias('@app');

        if( !file_exists( $path."/db/users.txt" ) ) {
            $fp = fopen ($path."/db/users.txt", "w");
            $ipUser = Yii::$app->getRequest()->getUserIP();
            foreach($users_in_file as $user){
                $user['id'] = $ipUser;
                $user['expire'] = time();
                $user['password'] = Yii::$app->getSecurity()->generatePasswordHash($user['password']);
                $user_in_file = implode(',',$user);
                fwrite ($fp, $user_in_file."\r\n");
            }
            fclose($fp);
        } else {
            $fp = fopen($path."/db/users.txt", "r");
            $user_str = fread($fp, filesize($fp));
            $user_arr = explode(',',$user_str);
            return $user_arr;
        }

    }
}
