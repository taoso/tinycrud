<?php
require 'Jlyu/TinyCRUD.php';

$db = new PDO('mysql:host=localhost;dbname=jovebox', 'jovebox', 'jovebox');

class User extends Jlyu\TinyCRUD {
    public static $table = 'user';
    public static $pkName = 'id';
    public function setEmail($email) {
        $this->changedFields['email'] = $email;
    }
}

User::setDb($db);

$u = new User();
$u->setPk('A1234');
$u->setEmail('hehe@hehe.cn');
$u->save();

$u = new User('A1234');
var_dump($u);
$u->setEmail('123@456.cn');
$u->save();
var_dump($u);
$u->delete();
