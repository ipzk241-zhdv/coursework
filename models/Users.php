<?php

namespace models;

use classes\Core;
use classes\Model;
use Utils\ApiDeletable;
use Utils\ApiReadable;
use Utils\ApiCreatable;
use Utils\ApiUpdatable;


/**
 * @property int $id
 * @property string $name
 * @property string $lastName
 * @property string|null $patronymic
 * @property string $email
 * @property string $password
 * @property string $avatar
 * @property \DateTime $date_of_birth
 * @property string $role  // 'student', 'warden', 'admin'
 * @property int $room_id
 * @property \DateTime $created_at
 */
class Users extends Model
{
    public static $table = 'users';

    use ApiCreatable, ApiReadable, ApiUpdatable, ApiDeletable;

    public function __construct()
    {
        parent::__construct();
    }

    public static function FindByLoginAndPassword($email, $password)
    {
        $user = self::findByCondition(['email' => $email])[0] ?? null;

        if (!$user) return null;
        if (!password_verify($password, $user['password'])) return null;

        unset($user['password']);

        return $user;
    }

    public static function IsEmailExists($email, $userId = null)
    {
        $user = self::findByCondition(['email' => $email]);
        if ($userId && $user) {
            if ($user[0]['id'] == $userId) {
                return false;
            } else {
                return true;
            }
        }
        return !empty($user);
    }

    public static function ValidateNewUser($name, $lastName, $patronymic, $email, $password, $confirmPassword): array
    {
        $errorMessage = [];

        if (strlen($name) < 2 || strlen($name) > 50) {
            $errorMessage[] = 'Ім\'я повинно бути від 2 до 50 символів';
        }
        if (strlen($lastName) < 2 || strlen($lastName) > 50) {
            $errorMessage[] = 'Прізвище повинно бути від 2 до 50 символів';
        }
        // patronymic може бути null або пустим, тож тут можна додати перевірку за бажанням

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage[] = 'Некоректний email';
        }
        if (strlen($password) < 6 || strlen($password) > 20) {
            $errorMessage[] = 'Пароль повинен бути від 6 до 20 символів';
        }
        if ($password !== $confirmPassword) {
            $errorMessage[] = 'Паролі не співпадають';
        }
        if (self::IsEmailExists($email)) {
            $errorMessage[] = 'Цей email вже використовується';
        }

        return $errorMessage;
    }

    public static function ValidateUpdateUser($userId, $name, $lastName, $patronymic, $email): array
    {
        $errorMessage = [];

        if (strlen($name) < 2 || strlen($name) > 50) {
            $errorMessage[] = 'Ім\'я повинно бути від 2 до 50 символів';
        }
        if (strlen($lastName) < 2 || strlen($lastName) > 50) {
            $errorMessage[] = 'Прізвище повинно бути від 2 до 50 символів';
        }
        // patronymic — опціонально, додати валідацію при потребі

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage[] = 'Некоректний email';
        }
        if (self::IsEmailExists($email, $userId)) {
            $errorMessage[] = 'Цей email вже використовується';
        }

        return $errorMessage;
    }

    public static function RegisterUser($name, $lastName, $patronymic, $email, $password, $role = 'student')
    {
        $user = new Users();
        $user->name = $name;
        $user->lastName = $lastName;
        $user->patronymic = $patronymic;
        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->avatar = 'default.png';
        $user->role = $role;
        $user->created_at = date('Y-m-d H:i:s');
        return $user->save();
    }

    public static function UpdateUser($userId, $name, $lastName, $patronymic, $email, $avatarFile = null)
    {
        $user = new Users();
        $oldUser = self::findById($userId);

        $user->id = $userId;
        if ($name) $user->name = $name;
        if ($lastName) $user->lastName = $lastName;
        if ($patronymic !== null) $user->patronymic = $patronymic;
        if ($email) $user->email = $email;

        $user->avatar = $oldUser['avatar'];
        $user->role = $oldUser['role'];

        if ($avatarFile && isset($avatarFile['tmp_name']) && is_uploaded_file($avatarFile['tmp_name'])) {
            $avatarPath = 'images/avatars/';
            $oldAvatar = 'public/' . $user->avatar;
            $uploadDir = 'public/' . $avatarPath;
            if (file_exists($oldAvatar)) {
                unlink($oldAvatar);
            }
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = pathinfo($avatarFile['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('avatar_', true) . '.' . $extension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($avatarFile['tmp_name'], $filePath)) {
                $user->avatar = $avatarPath . $fileName;
            }
        }

        $user->save();

        $user = self::findById($userId);
        $session = Core::getInstance()->session;
        $session->set('user', $user);

        return true;
    }

    public static function ValidateChangePassword($userId, $oldPassword, $newPassword, $confirmNewPassword): array
    {
        $errorMessage = [];
        $user = self::findById($userId);
        if ($user && !password_verify($oldPassword, $user['password_hash'])) {
            $errorMessage[] = 'Поточний пароль невірний';
        }
        if (strlen($newPassword) < 6 || strlen($newPassword) > 20) {
            $errorMessage[] = 'Пароль повинен бути від 6 до 20 символів';
        }
        if ($newPassword !== $confirmNewPassword) {
            $errorMessage[] = 'Паролі не співпадають';
        }
        return $errorMessage;
    }

    public static function ChangeUserPassword($userId, $currentPassword, $newPassword)
    {
        $user = self::findById($userId);
        $updatedUser = new Users();
        $updatedUser->id = $userId;

        if ($user && password_verify($currentPassword, $user['password_hash'])) {
            $updatedUser->password = password_hash($newPassword, PASSWORD_DEFAULT);
            return $updatedUser->save();
        }
        return false;
    }

    public static function IsUserLogged()
    {
        return !empty(Core::getInstance()->session->get('user'));
    }

    public static function LoginUser($user)
    {
        Core::getInstance()->session->set('user', $user);
    }

    public static function LogoutUser()
    {
        Core::getInstance()->session->delete('user');
    }
}
