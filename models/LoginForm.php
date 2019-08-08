<?php

namespace app\models;

use Yii;
use yii\base\Exception;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' =>'Имя пользователя',
            'password' => 'Пароль',
            'rememberMe' => 'Запомнить'
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     */
    public function validatePassword($attribute)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if($user){
                // проверю, если было больше 5 неудачных попыток ввода пароля- время между попытками должно составлять не меньше 10 минут
                if($user->failed_try > 5 && $user->last_login_try > time() - 600){
                    $this->addError($attribute, 'Слишком много неверных попыток ввода пароля. Должно пройти не менее 10 минут с последней попытки');
                    return;
                }
                if($user->failed_try > 15){
                    $this->addError($attribute, 'Учётная запись заблокирована. Обратитесь к администратору для восстановления доступа');
                    return;
                }

                if(!$user->validatePassword($this->$attribute)){
                    $user->last_login_try = time();
                    $user->failed_try = ++ $user->failed_try;
                    $user->save();
                }
                else{
                    return;
                }
            }
            $this->addError($attribute, 'Неверный номер участка или пароль');
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     * @throws Exception
     */
    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            $user->failed_try = 0;
            if(!$user->access_token){
                $user->access_token = Yii::$app->getSecurity()->generateRandomString(255);
            }
            $user->save();
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
        }
        return false;
    }
    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}
