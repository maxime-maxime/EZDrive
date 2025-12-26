<?php
require_once 'OwnerController.php';
require_once '../Config/config.php';

Class SecurityController{
    public static function isIpAllowed($ip, $username) : string|null{
     $pdo = Database::getConnection();
     $stmt = $pdo->prepare("SELECT * FROM loggins_attempts WHERE ip = :ip AND user_id = :user_id");
     $stmt->execute([":ip" => $ip, ":user_id" => $username]);
     $result = $stmt->fetch(PDO::FETCH_ASSOC);
     if(!$result){
        return null;
     }
     $locked_until = $result['locked_until'];
     if(time() < $locked_until){
        return $locked_until-time();
     }
     return null;
    }

    public static function lockIp($ip, $username){
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT failed_attempts, locked_until FROM loggins_attempts WHERE ip = :ip AND user_id = :user_id');
        $stmt->execute([":ip" => $ip, ":user_id" => $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$result){
            $stmt = $pdo->prepare('INSERT INTO loggins_attempts (ip, user_id, failed_attempts, locked_until) VALUES (:ip, :user_id, 1, :locked_until)');
            $stmt->execute([":ip" => $ip, ":user_id" => $username, ":locked_until" => 0]);
        }
        else if($result['failed_attempts'] < 3){
            $stmt = $pdo->prepare('UPDATE loggins_attempts SET failed_attempts = :failed_attempts, locked_until = :locked_until WHERE ip = :ip AND user_id = :user_id');
            $stmt->execute([":ip" => $ip, ":user_id" => $username, ":locked_until" => time(), ":failed_attempts" => $result['failed_attempts'] + 1]);
        }
        else{
            $delay = min(60, pow(2, max(0, $result['failed_attempts'] - 3)));
            $stmt = $pdo->prepare('UPDATE loggins_attempts SET failed_attempts = :failed_attempts, locked_until = :locked_until WHERE ip = :ip AND user_id = :user_id');
            $stmt->execute([":ip" => $ip, ":user_id" => $username, ":locked_until" => time() + $delay, ":failed_attempts" => $result['failed_attempts'] + 1]);
        }
    }

    public Static function remove($ip, $username){
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM loggins_attempts WHERE ip = :ip AND user_id = :user_id');
        $stmt->execute([":ip" => $ip, ":user_id" => $username]);
    }

    public static function checkAjax($pdo,$methode = ['GET'],$token = false):bool{
        if(!isset($_SESSION['user']['user_id'])){
            ownerController::logout($pdo);
            return false;
        }


        if (!in_array($_SERVER['REQUEST_METHOD'],$methode)){
            ownerController::logout($pdo);
            return false;
        }
    return true;

    }
}